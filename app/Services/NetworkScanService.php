<?php

namespace App\Services;

use App\Models\Device;

/**
 * Discovers reachable hosts on the server's local subnet.
 *
 * Auto-detects the subnet from the server's primary interface, runs a parallel
 * ICMP ping sweep, reads the ARP table for MAC addresses (needed for
 * Wake-on-LAN), and probes TCP 4352 to flag PJLink projectors. Every host the
 * server can reach on layer 2 is returned as an importable candidate.
 */
class NetworkScanService
{
    /**
     * Largest host count we are willing to sweep in one pass (a /22 minus the
     * network + broadcast addresses). Wider prefixes are clamped to the host's
     * own /24 so a misconfigured /16 cannot launch a 65k-host scan.
     */
    private const MAX_HOSTS = 1022;

    /**
     * Per-host timeout (seconds) for the NetBIOS name probe. Non-responders
     * (most phones) cost this long, so it is kept tight.
     */
    private const NAME_TIMEOUT = 1;

    private readonly PjlinkService $pjlink;

    private readonly MacVendorLookup $vendors;

    public function __construct(
        private readonly ReachabilityService $reachability,
        private readonly int $concurrency = 64,
        ?PjlinkService $pjlink = null,
        ?MacVendorLookup $vendors = null,
    ) {
        $this->pjlink = $pjlink ?? app(PjlinkService::class);
        $this->vendors = $vendors ?? app(MacVendorLookup::class);
    }

    /**
     * Detect the subnet, sweep it, and return importable candidates.
     *
     * @return array{
     *     subnet: string|null,
     *     interface_ip: string|null,
     *     host_count: int,
     *     alive_count: int,
     *     candidates: list<array{ip: string, mac: string|null, vendor: string|null, type: string, name: string, supports_pjlink: bool, existing: bool}>,
     *     error: string|null,
     * }
     */
    public function scan(): array
    {
        $subnet = $this->detectSubnet();

        if ($subnet === null) {
            return [
                'subnet' => null,
                'interface_ip' => null,
                'host_count' => 0,
                'alive_count' => 0,
                'candidates' => [],
                'error' => 'Could not detect the server subnet. Check the host network configuration.',
            ];
        }

        $hosts = $this->enumerateHosts($subnet['network'], $subnet['broadcast']);
        $alive = $this->pingSweep($hosts);
        $arp = $this->arpTable();
        $existing = Device::query()->pluck('ip')->flip();

        $candidates = [];
        foreach ($alive as $ip) {
            $mac = $arp[$ip] ?? null;
            $isProjector = $this->reachability->isPortOpen($ip, 4352);
            $vendor = $this->vendors->lookup($mac);

            $candidates[] = [
                'ip' => $ip,
                'mac' => $mac,
                'vendor' => $vendor,
                'type' => $isProjector ? 'projector' : 'other',
                'name' => $this->resolveName($ip, $mac, $isProjector, $vendor),
                'supports_pjlink' => $isProjector,
                'existing' => $existing->has($ip),
            ];
        }

        return [
            'subnet' => $subnet['cidr'],
            'interface_ip' => $subnet['ip'],
            'host_count' => count($hosts),
            'alive_count' => count($alive),
            'candidates' => $candidates,
            'error' => null,
        ];
    }

    /**
     * Resolve the best available human name for a host, in priority order:
     * PJLink projector name → reverse DNS hostname → MAC vendor → IP-octet
     * placeholder.
     */
    private function resolveName(string $ip, ?string $mac, bool $isProjector, ?string $vendor): string
    {
        $octet = (int) substr(strrchr($ip, '.') ?: '.0', 1);

        if ($isProjector) {
            $pjlink = $this->pjlinkName($ip);
            if ($pjlink !== null && $pjlink !== '') {
                return $pjlink;
            }
        }

        $netbios = $this->netbiosName($ip);
        if ($netbios !== null && $netbios !== '') {
            return $netbios;
        }

        $hostname = $this->reverseDns($ip);
        if ($hostname !== null && $hostname !== '') {
            return $hostname;
        }

        if ($vendor !== null) {
            return "{$vendor} {$octet}";
        }

        return $isProjector ? "Projector {$octet}" : "Device {$octet}";
    }

    /**
     * Query a projector for its configured name (or manufacturer + model) over
     * PJLink. Protected so tests can stub the network call.
     */
    protected function pjlinkName(string $ip): ?string
    {
        $device = new Device(['ip' => $ip, 'pjlink_port' => 4352]);

        return $this->pjlink->resolveDisplayName($device);
    }

    /**
     * Resolve a host's NetBIOS computer name with a native NBSTAT (UDP 137)
     * node-status request. Catches the DHCP-style names Windows and many Android
     * devices broadcast, which reverse DNS misses. Protected so tests can stub
     * the network call.
     */
    protected function netbiosName(string $ip): ?string
    {
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            return null;
        }

        try {
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => self::NAME_TIMEOUT, 'usec' => 0]);

            // NBSTAT node-status request for the wildcard name "*".
            $request = "\x13\x37\x00\x00\x00\x01\x00\x00\x00\x00\x00\x00";
            $encoded = '';
            foreach (str_split('*'.str_repeat("\x00", 15)) as $char) {
                $byte = ord($char);
                $encoded .= chr(ord('A') + (($byte >> 4) & 0xF)).chr(ord('A') + ($byte & 0xF));
            }
            $request .= "\x20".$encoded."\x00\x00\x21\x00\x01";

            if (@socket_sendto($socket, $request, strlen($request), 0, $ip, 137) === false) {
                return null;
            }

            $buffer = '';
            $from = '';
            $port = 0;
            if (@socket_recvfrom($socket, $buffer, 2048, 0, $from, $port) === false || strlen($buffer) < 57) {
                return null;
            }

            return $this->parseNbstat($buffer);
        } finally {
            socket_close($socket);
        }
    }

    /**
     * Extract the unique workstation name (suffix 0x00, not a group) from an
     * NBSTAT response's name list.
     */
    private function parseNbstat(string $buffer): ?string
    {
        $count = ord($buffer[56]);
        $offset = 57;

        for ($i = 0; $i < $count; $i++) {
            if ($offset + 18 > strlen($buffer)) {
                break;
            }

            $name = rtrim(substr($buffer, $offset, 15));
            $suffix = ord($buffer[$offset + 15]);
            $flags = (ord($buffer[$offset + 16]) << 8) | ord($buffer[$offset + 17]);
            $isGroup = ($flags & 0x8000) !== 0;

            if (! $isGroup && $suffix === 0x00 && $name !== '') {
                return $name;
            }

            $offset += 18;
        }

        return null;
    }

    /**
     * Reverse-DNS hostname for an IP, or null when no PTR record exists (the
     * lookup returns the IP unchanged). Protected so tests can stub it.
     */
    protected function reverseDns(string $ip): ?string
    {
        $host = gethostbyaddr($ip);

        if ($host === false || $host === $ip) {
            return null;
        }

        // Use the leftmost label so "proj-a1.local" reads as "proj-a1".
        return strtok($host, '.') ?: null;
    }

    /**
     * Detect the server's primary IPv4 address and subnet prefix.
     *
     * @return array{ip: string, prefix: int, network: int, broadcast: int, cidr: string}|null
     */
    public function detectSubnet(): ?array
    {
        $ip = $this->primaryIp();

        if ($ip === null) {
            return null;
        }

        // Clamp to a /24 minimum so an unusually wide prefix cannot blow past
        // MAX_HOSTS; the host's own /24 is the useful discovery scope anyway.
        $prefix = max($this->prefixFor($ip) ?? 24, 24);

        $ipLong = ip2long($ip);
        $mask = $prefix === 0 ? 0 : (~0 << (32 - $prefix)) & 0xFFFFFFFF;
        $network = $ipLong & $mask;
        $broadcast = $network | ((~$mask) & 0xFFFFFFFF);

        return [
            'ip' => $ip,
            'prefix' => $prefix,
            'network' => $network,
            'broadcast' => $broadcast,
            'cidr' => long2ip($network)."/{$prefix}",
        ];
    }

    /**
     * The server's primary outbound IPv4, found by inspecting the local end of
     * a UDP socket "connected" to a public address. No packets are sent.
     */
    protected function primaryIp(): ?string
    {
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            return null;
        }

        try {
            if (! @socket_connect($socket, '8.8.8.8', 53)) {
                return null;
            }

            $ip = '';
            $port = 0;
            if (! @socket_getsockname($socket, $ip, $port) || $ip === '' || $ip === '0.0.0.0') {
                return null;
            }

            return $ip;
        } finally {
            socket_close($socket);
        }
    }

    /**
     * Read the subnet prefix length for the given IP from the OS, falling back
     * to null (caller defaults to /24) when it cannot be parsed.
     */
    protected function prefixFor(string $ip): ?int
    {
        $quoted = preg_quote($ip, '/');

        // Linux: `ip -o -f inet addr show` → "... inet 192.168.1.5/24 ..."
        $output = $this->run($this->resolveBinary(['ip', '/sbin/ip', '/usr/sbin/ip']).' -o -f inet addr show 2>/dev/null');
        if (preg_match('/inet\s+'.$quoted.'\/(\d{1,2})\b/', $output, $m)) {
            return (int) $m[1];
        }

        // macOS/BSD: `ifconfig` → "inet 192.168.1.5 netmask 0xffffff00 ..."
        $output = $this->run($this->resolveBinary(['ifconfig', '/sbin/ifconfig', '/usr/sbin/ifconfig']).' 2>/dev/null');
        if (preg_match('/inet\s+'.$quoted.'\s+netmask\s+0x([0-9a-fA-F]{8})/', $output, $m)) {
            return $this->maskHexToPrefix($m[1]);
        }

        return null;
    }

    /**
     * Enumerate the usable host addresses between the network and broadcast,
     * capping the count at MAX_HOSTS.
     *
     * @return list<string>
     */
    private function enumerateHosts(int $network, int $broadcast): array
    {
        $hosts = [];
        for ($host = $network + 1; $host < $broadcast && count($hosts) < self::MAX_HOSTS; $host++) {
            $hosts[] = long2ip($host);
        }

        return $hosts;
    }

    /**
     * Ping every host in parallel and return the addresses that answered.
     *
     * @param  list<string>  $ips
     * @return list<string>
     */
    protected function pingSweep(array $ips): array
    {
        $ping = $this->resolveBinary(['ping', '/sbin/ping', '/bin/ping', '/usr/bin/ping']);
        $alive = [];
        $queue = $ips;
        /** @var array<string, array{proc: resource, pipes: array<int, resource>}> $running */
        $running = [];

        while ($queue !== [] || $running !== []) {
            while ($queue !== [] && count($running) < $this->concurrency) {
                $ip = array_shift($queue);
                $command = preg_replace('/^ping/', $ping, $this->reachability->buildPingCommand($ip), 1).' 2>&1';
                $proc = @proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);

                if (is_resource($proc)) {
                    fclose($pipes[0]);
                    $running[$ip] = ['proc' => $proc, 'pipes' => $pipes];
                }
            }

            foreach ($running as $ip => $handle) {
                $status = proc_get_status($handle['proc']);
                if ($status['running']) {
                    continue;
                }

                fclose($handle['pipes'][1]);
                fclose($handle['pipes'][2]);
                proc_close($handle['proc']);

                if ($status['exitcode'] === 0) {
                    $alive[] = $ip;
                }

                unset($running[$ip]);
            }

            if ($running !== []) {
                usleep(20000);
            }
        }

        sort($alive, SORT_NATURAL);

        return $alive;
    }

    /**
     * Read the ARP cache into an IP → normalized-MAC map.
     *
     * @return array<string, string>
     */
    protected function arpTable(): array
    {
        $output = $this->run($this->resolveBinary(['arp', '/usr/sbin/arp', '/sbin/arp', '/usr/bin/arp']).' -a 2>&1');
        $table = [];

        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            if (preg_match('/\(?(\d{1,3}(?:\.\d{1,3}){3})\)?\s+at\s+([0-9a-fA-F:\-]+)/', $line, $m)) {
                $mac = $this->normalizeMac($m[2]);
                if ($mac !== null) {
                    $table[$m[1]] = $mac;
                }
            }
        }

        return $table;
    }

    /**
     * Normalize a MAC to upper-case colon form, padding single-digit octets
     * (macOS `arp` prints "0:1a:..." rather than "00:1a:...").
     */
    private function normalizeMac(string $raw): ?string
    {
        $parts = preg_split('/[:\-]/', trim($raw)) ?: [];

        if (count($parts) !== 6) {
            return null;
        }

        $octets = [];
        foreach ($parts as $part) {
            if (! preg_match('/^[0-9a-fA-F]{1,2}$/', $part)) {
                return null;
            }
            $octets[] = strtoupper(str_pad($part, 2, '0', STR_PAD_LEFT));
        }

        return implode(':', $octets);
    }

    /**
     * Convert a hex netmask (e.g. "ffffff00") to a prefix length.
     */
    private function maskHexToPrefix(string $hex): int
    {
        return substr_count(str_pad(decbin((int) hexdec($hex)), 32, '0', STR_PAD_LEFT), '1');
    }

    /**
     * Resolve the first executable path from the candidates, else the first
     * (bare command, relying on PATH). Mirrors StatusController's handling of
     * PHP-FPM's minimal PATH that often omits /sbin.
     *
     * @param  list<string>  $candidates
     */
    private function resolveBinary(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if (str_contains($candidate, '/') && is_executable($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    /**
     * Run a shell command and return its combined output. Protected so tests
     * can stub OS interaction.
     */
    protected function run(string $command): string
    {
        $output = [];
        @exec($command, $output);

        return implode("\n", $output);
    }
}

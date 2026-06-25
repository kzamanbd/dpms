<?php

namespace App\Services;

use App\Models\Device;

/**
 * Determines whether a device is reachable on the network.
 *
 * Primary check is an ICMP echo (ping). Because ICMP is blocked on some
 * segments, a TCP connect to the device's monitor_port acts as a fallback
 * (PRD hypothesis H1).
 */
class ReachabilityService
{
    public function __construct(
        private readonly int $timeout = 1,
    ) {}

    public function isReachable(Device $device): bool
    {
        if ($this->pingIcmp($device->ip)) {
            return true;
        }

        if ($device->monitor_port !== null && $this->checkTcp($device->ip, $device->monitor_port)) {
            return true;
        }

        return false;
    }

    /**
     * Build the platform-appropriate ping command for a single echo request.
     */
    public function buildPingCommand(string $ip): string
    {
        $ip = escapeshellarg($ip);

        return match (PHP_OS_FAMILY) {
            'Windows' => "ping -n 1 -w {$this->timeout}000 {$ip}",
            'Darwin' => "ping -c 1 -t {$this->timeout} {$ip}",
            default => "ping -c 1 -w {$this->timeout} {$ip}",
        };
    }

    /**
     * Run a single ICMP echo. Protected so tests can stub the shell call.
     */
    protected function pingIcmp(string $ip): bool
    {
        $output = [];
        $exitCode = 1;

        exec($this->buildPingCommand($ip).' 2>&1', $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * Attempt a TCP connection as an ICMP fallback. Protected for stubbing.
     */
    protected function checkTcp(string $ip, int $port): bool
    {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($ip, $port, $errno, $errstr, $this->timeout);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}

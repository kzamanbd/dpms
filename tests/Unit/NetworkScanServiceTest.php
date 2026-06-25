<?php

use App\Models\Device;
use App\Services\NetworkScanService;
use App\Services\ReachabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * A NetworkScanService with every OS-touching seam stubbed, so the pure
 * detection / parsing / candidate logic can be tested deterministically.
 */
function fakeScanner(array $alive, string $arpOutput, array $openPorts, array $netbios = []): NetworkScanService
{
    $reachability = new class extends ReachabilityService
    {
        /** @var list<string> */
        public array $open = [];

        public function isPortOpen(string $ip, int $port): bool
        {
            return in_array($ip, $this->open, true);
        }
    };
    $reachability->open = $openPorts;

    return new class($reachability, $alive, $arpOutput, $netbios) extends NetworkScanService
    {
        public function __construct(
            ReachabilityService $reachability,
            private array $alive,
            private string $arpOutput,
            private array $netbios,
        ) {
            parent::__construct($reachability);
        }

        protected function primaryIp(): ?string
        {
            return '192.168.1.10';
        }

        protected function prefixFor(string $ip): ?int
        {
            return 24;
        }

        protected function pingSweep(array $ips): array
        {
            return $this->alive;
        }

        protected function run(string $command): string
        {
            return $this->arpOutput;
        }

        protected function pjlinkName(string $ip): ?string
        {
            // Stand in for a real PJLink NAME query on the projector.
            return $ip === '192.168.1.20' ? 'Boardroom Projector' : null;
        }

        protected function netbiosName(string $ip): ?string
        {
            return $this->netbios[$ip] ?? null;
        }

        protected function reverseDns(string $ip): ?string
        {
            return null; // No PTR records in the test environment.
        }
    };
}

test('detectSubnet derives the network and broadcast from the host IP', function () {
    $subnet = fakeScanner([], '', [])->detectSubnet();

    expect($subnet)
        ->cidr->toBe('192.168.1.0/24')
        ->ip->toBe('192.168.1.10')
        ->prefix->toBe(24);

    expect(long2ip($subnet['network']))->toBe('192.168.1.0');
    expect(long2ip($subnet['broadcast']))->toBe('192.168.1.255');
});

test('scan flags PJLink projectors and resolves MACs from the ARP table', function () {
    $arp = implode("\n", [
        '? (192.168.1.20) at 0:1a:2b:3c:4d:5e on en0 ifscope [ethernet]',
        '? (192.168.1.30) at aa:bb:cc:dd:ee:ff on en0 ifscope [ethernet]',
        '? (192.168.1.40) at 00:0c:29:11:22:33 on en0 ifscope [ethernet]',
        '? (192.168.1.99) at (incomplete) on en0',
    ]);

    $result = fakeScanner(
        alive: ['192.168.1.20', '192.168.1.30', '192.168.1.40'],
        arpOutput: $arp,
        openPorts: ['192.168.1.20'],
    )->scan();

    expect($result['subnet'])->toBe('192.168.1.0/24');
    expect($result['alive_count'])->toBe(3);
    expect($result['error'])->toBeNull();

    // Projector: PJLink name wins over every other source.
    $projector = collect($result['candidates'])->firstWhere('ip', '192.168.1.20');
    expect($projector)
        ->type->toBe('projector')
        ->supports_pjlink->toBeTrue()
        ->mac->toBe('00:1A:2B:3C:4D:5E') // single-digit octet padded
        ->name->toBe('Boardroom Projector')
        ->existing->toBeFalse();

    // Randomized (locally administered) MAC → no vendor → placeholder name.
    $randomized = collect($result['candidates'])->firstWhere('ip', '192.168.1.30');
    expect($randomized)
        ->type->toBe('other')
        ->supports_pjlink->toBeFalse()
        ->mac->toBe('AA:BB:CC:DD:EE:FF')
        ->vendor->toBeNull()
        ->name->toBe('Device 30');

    // Globally-unique MAC with a known OUI → vendor-labelled name.
    $vendorNamed = collect($result['candidates'])->firstWhere('ip', '192.168.1.40');
    expect($vendorNamed)
        ->vendor->toBe('VMware')
        ->name->toBe('VMware 40');
});

test('scan names a host from its NetBIOS name when it has one', function () {
    $arp = '? (192.168.1.50) at 00:0c:29:44:55:66 on en0 ifscope [ethernet]';

    $result = fakeScanner(
        alive: ['192.168.1.50'],
        arpOutput: $arp,
        openPorts: [],
        netbios: ['192.168.1.50' => 'Zaman'],
    )->scan();

    // NetBIOS name wins over the vendor-derived placeholder.
    expect($result['candidates'][0]['name'])->toBe('Zaman');
});

test('scan marks hosts already registered as devices', function () {
    Device::factory()->create(['ip' => '192.168.1.20']);

    $result = fakeScanner(['192.168.1.20'], '', [])->scan();

    expect($result['candidates'][0]['existing'])->toBeTrue();
});

test('scan returns an error when the subnet cannot be detected', function () {
    $scanner = new class(new ReachabilityService) extends NetworkScanService
    {
        protected function primaryIp(): ?string
        {
            return null;
        }
    };

    $result = $scanner->scan();

    expect($result['error'])->not->toBeNull();
    expect($result['candidates'])->toBe([]);
});

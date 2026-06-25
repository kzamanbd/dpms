<?php

use App\Models\Device;
use App\Services\WakeOnLanService;

/**
 * Build a WoL service that records the sendPacket arguments instead of opening
 * a UDP socket.
 */
function wolSpy(bool $sent = true): WakeOnLanService
{
    return new class($sent) extends WakeOnLanService
    {
        /** @var array<string, mixed> */
        public array $lastSend = [];

        public function __construct(private readonly bool $sent)
        {
            //
        }

        protected function sendPacket(string $packet, string $broadcast, int $port): bool
        {
            $this->lastSend = compact('packet', 'broadcast', 'port');

            return $this->sent;
        }
    };
}

it('builds a 102-byte magic packet', function () {
    $packet = (new WakeOnLanService)->buildMagicPacket('1C:1B:0D:11:22:33');

    expect(strlen($packet))->toBe(102)
        ->and(substr($packet, 0, 6))->toBe(str_repeat("\xFF", 6))
        ->and(substr_count($packet, pack('H*', '1C1B0D112233')))->toBe(16);
});

it('accepts MAC addresses in any delimiter format', function () {
    $service = new WakeOnLanService;

    expect($service->buildMagicPacket('1c-1b-0d-11-22-33'))
        ->toBe($service->buildMagicPacket('1C1B0D112233'));
});

it('rejects an invalid MAC address', function () {
    (new WakeOnLanService)->buildMagicPacket('not-a-mac');
})->throws(InvalidArgumentException::class);

it('uses the limited broadcast for a same-subnet device', function () {
    $service = wolSpy();
    $service->wake(new Device(['mac' => '1C:1B:0D:11:22:33', 'wol_port' => 9]));

    expect($service->lastSend['broadcast'])->toBe('255.255.255.255')
        ->and($service->lastSend['port'])->toBe(9);
});

it('uses the configured directed broadcast for a cross-VLAN device', function () {
    $service = wolSpy();
    $result = $service->wake(new Device([
        'mac' => '1C:1B:0D:77:88:99',
        'wol_broadcast' => '192.168.20.255',
        'wol_port' => 9,
    ]));

    expect($result['success'])->toBeTrue()
        ->and($result['broadcast'])->toBe('192.168.20.255');
});

it('fails to wake a device without a MAC address', function () {
    $result = wolSpy()->wake(new Device(['mac' => null]));

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('MAC');
});

it('reports a transport failure', function () {
    $result = wolSpy(sent: false)->wake(new Device(['mac' => '1C:1B:0D:11:22:33', 'wol_port' => 9]));

    expect($result['success'])->toBeFalse();
});

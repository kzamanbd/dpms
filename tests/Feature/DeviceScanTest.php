<?php

use App\Models\Device;
use App\Models\User;
use App\Services\NetworkScanService;

test('guests cannot scan or import devices', function () {
    $this->get(route('devices.scan'))->assertRedirect(route('login'));
    $this->post(route('devices.import'))->assertRedirect(route('login'));
});

test('the scan endpoint returns discovered candidates as JSON', function () {
    $this->mock(NetworkScanService::class)
        ->shouldReceive('scan')
        ->once()
        ->andReturn([
            'subnet' => '192.168.1.0/24',
            'interface_ip' => '192.168.1.10',
            'host_count' => 254,
            'alive_count' => 1,
            'candidates' => [[
                'ip' => '192.168.1.20',
                'mac' => '00:1A:2B:3C:4D:5E',
                'type' => 'projector',
                'name' => 'Projector 20',
                'supports_pjlink' => true,
                'existing' => false,
            ]],
            'error' => null,
        ]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('devices.scan'))
        ->assertOk()
        ->assertJsonPath('subnet', '192.168.1.0/24')
        ->assertJsonPath('candidates.0.ip', '192.168.1.20')
        ->assertJsonPath('candidates.0.type', 'projector');
});

test('importing selected candidates creates devices and skips existing IPs', function () {
    Device::factory()->create(['ip' => '192.168.1.30', 'name' => 'Existing']);

    $this->actingAs(User::factory()->create())
        ->post(route('devices.import'), [
            'devices' => [
                ['name' => 'Projector 20', 'type' => 'projector', 'ip' => '192.168.1.20', 'mac' => '00:1A:2B:3C:4D:5E'],
                ['name' => 'Renamed', 'type' => 'other', 'ip' => '192.168.1.30', 'mac' => null],
            ],
        ])
        ->assertRedirect();

    expect(Device::where('ip', '192.168.1.20')->where('type', 'projector')->exists())->toBeTrue();
    // The pre-existing device is untouched, not renamed.
    expect(Device::where('ip', '192.168.1.30')->value('name'))->toBe('Existing');
    expect(Device::count())->toBe(2);
});

test('import rejects an invalid MAC address', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('devices.import'), [
            'devices' => [
                ['name' => 'Bad', 'type' => 'other', 'ip' => '192.168.1.20', 'mac' => 'nope'],
            ],
        ])
        ->assertSessionHasErrors('devices.0.mac');
});

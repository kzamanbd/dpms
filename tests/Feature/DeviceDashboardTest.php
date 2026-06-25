<?php

use App\Models\ActionLog;
use App\Models\Device;
use App\Models\User;
use App\Services\PjlinkResult;
use App\Services\PjlinkService;
use App\Services\WakeOnLanService;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the device dashboard', function () {
    $this->get(route('devices.index'))->assertRedirect(route('login'));
});

test('the device dashboard lists devices', function () {
    $device = Device::factory()->projector()->create(['name' => 'Hall Projector']);

    $this->actingAs(User::factory()->create())
        ->get(route('devices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('devices/index')
            ->has('devices', 1)
            ->where('devices.0.name', 'Hall Projector')
            ->where('devices.0.supports_pjlink', true)
        );
});

test('power on succeeds on a projector and is logged', function () {
    $device = Device::factory()->projector()->create();

    $this->mock(PjlinkService::class)
        ->shouldReceive('powerOn')
        ->once()
        ->andReturn(PjlinkResult::ok(['power' => 'on']));

    $this->actingAs(User::factory()->create())
        ->post(route('devices.power-on', $device))
        ->assertRedirect();

    expect(ActionLog::where('device_id', $device->id)->where('action', 'power_on')->where('result', 'success')->exists())
        ->toBeTrue();
});

test('pjlink actions are rejected for non-projectors', function () {
    $device = Device::factory()->pc()->create();

    $this->mock(PjlinkService::class)->shouldNotReceive('powerOn');

    $this->actingAs(User::factory()->create())
        ->post(route('devices.power-on', $device))
        ->assertRedirect();

    expect(ActionLog::where('device_id', $device->id)->exists())->toBeFalse();
});

test('status reads telemetry into a logged summary', function () {
    $device = Device::factory()->projector()->create();

    $this->mock(PjlinkService::class, function ($mock) {
        $mock->shouldReceive('getPowerStatus')->andReturn(PjlinkResult::ok(['power' => 'on']));
        $mock->shouldReceive('getLampHours')->andReturn(PjlinkResult::ok(['hours' => 8262, 'lamps' => [['hours' => 8262, 'on' => true]]]));
        $mock->shouldReceive('getErrors')->andReturn(PjlinkResult::ok(['errors' => ['fan' => 'normal', 'lamp' => 'normal', 'temperature' => 'normal', 'cover' => 'normal', 'filter' => 'normal', 'other' => 'normal']]));
        $mock->shouldReceive('getTemperature')->andReturn(PjlinkResult::ok(['temperature' => 'normal']));
    });

    $this->actingAs(User::factory()->create())
        ->post(route('devices.status', $device))
        ->assertRedirect();

    $log = ActionLog::where('device_id', $device->id)->where('action', 'get_status')->first();

    expect($log)->not->toBeNull()
        ->and($log->result)->toBe('success')
        ->and($log->detail)->toContain('8262h');
});

test('wake sends a magic packet and logs the broadcast path', function () {
    $device = Device::factory()->pc()->create([
        'mac' => '1C:1B:0D:77:88:99',
        'wol_broadcast' => '192.168.20.255',
    ]);

    $this->mock(WakeOnLanService::class)
        ->shouldReceive('wake')
        ->once()
        ->andReturn(['success' => true, 'broadcast' => '192.168.20.255', 'error' => null]);

    $this->actingAs(User::factory()->create())
        ->post(route('devices.wake', $device))
        ->assertRedirect();

    $log = ActionLog::where('device_id', $device->id)->where('action', 'wake')->first();

    expect($log->result)->toBe('success')
        ->and($log->detail)->toContain('192.168.20.255');
});

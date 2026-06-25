<?php

use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a device can be created', function () {
    $this->post(route('devices.store'), [
        'name' => 'New Projector',
        'type' => 'projector',
        'ip' => '192.168.10.99',
        'pjlink_port' => 4352,
        'wol_port' => 9,
    ])->assertRedirect();

    expect(Device::where('name', 'New Projector')->where('ip', '192.168.10.99')->exists())->toBeTrue();
});

test('creating a device validates required fields', function () {
    $this->post(route('devices.store'), [
        'name' => '',
        'type' => 'projector',
        'ip' => 'not-an-ip',
        'pjlink_port' => 4352,
        'wol_port' => 9,
    ])->assertSessionHasErrors(['name', 'ip']);

    expect(Device::count())->toBe(0);
});

test('creating a device rejects a malformed MAC address', function () {
    $this->post(route('devices.store'), [
        'name' => 'Bad MAC PC',
        'type' => 'pc',
        'ip' => '192.168.10.60',
        'mac' => 'ZZ:ZZ:ZZ',
        'pjlink_port' => 4352,
        'wol_port' => 9,
    ])->assertSessionHasErrors('mac');
});

test('creating a device rejects an invalid type', function () {
    $this->post(route('devices.store'), [
        'name' => 'Weird',
        'type' => 'toaster',
        'ip' => '192.168.10.61',
        'pjlink_port' => 4352,
        'wol_port' => 9,
    ])->assertSessionHasErrors('type');
});

test('a device can be updated', function () {
    $device = Device::factory()->pc()->create(['name' => 'Old Name']);

    $this->put(route('devices.update', $device), [
        'name' => 'Updated Name',
        'type' => 'pc',
        'ip' => $device->ip,
        'mac' => '1C:1B:0D:AA:BB:CC',
        'vlan' => '42',
        'pjlink_port' => 4352,
        'wol_port' => 9,
    ])->assertRedirect();

    $device->refresh();

    expect($device->name)->toBe('Updated Name')
        ->and($device->mac)->toBe('1C:1B:0D:AA:BB:CC')
        ->and($device->vlan)->toBe('42')
        ->and($device->type)->toBe(DeviceType::Pc);
});

test('a device can be deleted', function () {
    $device = Device::factory()->create();

    $this->delete(route('devices.destroy', $device))->assertRedirect();

    expect(Device::find($device->id))->toBeNull();
});

test('guests cannot create devices', function () {
    auth()->logout();

    $this->post(route('devices.store'), [
        'name' => 'Nope',
        'type' => 'pc',
        'ip' => '192.168.10.62',
        'pjlink_port' => 4352,
        'wol_port' => 9,
    ])->assertRedirect(route('login'));

    expect(Device::count())->toBe(0);
});

<?php

use App\Enums\DeviceStatus;
use App\Jobs\CheckDeviceReachabilityJob;
use App\Models\Device;
use App\Models\PocActionLog;
use App\Services\ReachabilityService;
use Illuminate\Support\Facades\Queue;

test('the monitor command queues a check per device', function () {
    Queue::fake();
    Device::factory()->count(3)->create();

    $this->artisan('devices:monitor')->assertSuccessful();

    Queue::assertPushed(CheckDeviceReachabilityJob::class, 3);
});

test('a reachable device is marked online and the transition is logged', function () {
    $device = Device::factory()->offline()->create();

    $this->mock(ReachabilityService::class)
        ->shouldReceive('isReachable')
        ->andReturnTrue();

    (new CheckDeviceReachabilityJob($device))->handle(app(ReachabilityService::class));

    $device->refresh();

    expect($device->status)->toBe(DeviceStatus::Online)
        ->and($device->last_seen)->not->toBeNull()
        ->and(PocActionLog::where('device_id', $device->id)->where('action', 'monitor')->exists())->toBeTrue();
});

test('an unchanged status does not create a log entry', function () {
    $device = Device::factory()->online()->create();

    $this->mock(ReachabilityService::class)
        ->shouldReceive('isReachable')
        ->andReturnTrue();

    (new CheckDeviceReachabilityJob($device))->handle(app(ReachabilityService::class));

    expect(PocActionLog::where('device_id', $device->id)->exists())->toBeFalse();
});

<?php

namespace App\Jobs;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\PocActionLog;
use App\Services\ReachabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Checks one device's reachability and records online/offline transitions.
 */
class CheckDeviceReachabilityJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Device $device,
    ) {}

    public function handle(ReachabilityService $reachability): void
    {
        $previous = $this->device->status;
        $current = $reachability->isReachable($this->device)
            ? DeviceStatus::Online
            : DeviceStatus::Offline;

        $this->device->status = $current;

        if ($current === DeviceStatus::Online) {
            $this->device->last_seen = now();
        }

        $this->device->save();

        if ($previous !== $current) {
            PocActionLog::create([
                'device_id' => $this->device->id,
                'action' => 'monitor',
                'result' => $current->value,
                'detail' => "Status changed {$previous->value} → {$current->value}.",
            ]);
        }
    }
}

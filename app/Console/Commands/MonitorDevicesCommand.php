<?php

namespace App\Console\Commands;

use App\Jobs\CheckDeviceReachabilityJob;
use App\Models\Device;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('devices:monitor')]
#[Description('Queue a reachability check for every device (runs on a ~30s loop).')]
class MonitorDevicesCommand extends Command
{
    public function handle(): int
    {
        $count = 0;

        Device::query()->each(function (Device $device) use (&$count): void {
            CheckDeviceReachabilityJob::dispatch($device);
            $count++;
        });

        $this->info("Queued reachability checks for {$count} device(s).");

        return self::SUCCESS;
    }
}

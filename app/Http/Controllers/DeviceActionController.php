<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PocActionLog;
use App\Services\PjlinkResult;
use App\Services\PjlinkService;
use App\Services\WakeOnLanService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DeviceActionController extends Controller
{
    public function powerOn(Device $device, PjlinkService $pjlink): RedirectResponse
    {
        return $this->runPjlink($device, 'power_on', fn () => $pjlink->powerOn($device));
    }

    public function powerOff(Device $device, PjlinkService $pjlink): RedirectResponse
    {
        return $this->runPjlink($device, 'power_off', fn () => $pjlink->powerOff($device));
    }

    /**
     * Read power, lamp hours, errors and temperature in one pass (PRD H2).
     */
    public function status(Device $device, PjlinkService $pjlink): RedirectResponse
    {
        if (! $device->supportsPjlink()) {
            return $this->rejectNonProjector();
        }

        $power = $pjlink->getPowerStatus($device);

        if (! $power->success) {
            return $this->finish($device, 'get_status', false, $power->error ?? 'PJLink status failed.');
        }

        $lamp = $pjlink->getLampHours($device);
        $errors = $pjlink->getErrors($device);
        $temperature = $pjlink->getTemperature($device);

        $detail = $this->summariseStatus($power, $lamp, $errors, $temperature);

        return $this->finish($device, 'get_status', true, $detail);
    }

    public function wake(Device $device, WakeOnLanService $wol): RedirectResponse
    {
        if ($device->mac === null) {
            return $this->finish($device, 'wake', false, 'Device has no MAC address to wake.');
        }

        $result = $wol->wake($device);

        $detail = $result['success']
            ? "Magic packet sent to {$device->mac} via {$result['broadcast']}."
            : ($result['error'] ?? 'Wake failed.');

        return $this->finish($device, 'wake', $result['success'], $detail);
    }

    /**
     * Guard PJLink-only actions, then run and record the result.
     */
    private function runPjlink(Device $device, string $action, callable $command): RedirectResponse
    {
        if (! $device->supportsPjlink()) {
            return $this->rejectNonProjector();
        }

        /** @var PjlinkResult $result */
        $result = $command();

        $detail = $result->success
            ? 'Command accepted by projector.'
            : ($result->error ?? 'PJLink command failed.');

        return $this->finish($device, $action, $result->success, $detail);
    }

    private function summariseStatus(PjlinkResult $power, PjlinkResult $lamp, PjlinkResult $errors, PjlinkResult $temperature): string
    {
        $parts = ['Power: '.($power->value['power'] ?? 'unknown')];

        if ($lamp->success) {
            $parts[] = 'Lamp: '.$lamp->value['hours'].'h';
        }

        if ($errors->success) {
            $faults = array_filter($errors->value['errors'], fn (string $severity) => $severity !== 'normal');
            $parts[] = 'Errors: '.($faults === []
                ? 'none'
                : implode(', ', array_map(fn ($k, $v) => "{$k} {$v}", array_keys($faults), $faults)));
        }

        if ($temperature->success) {
            $parts[] = 'Temp: '.$temperature->value['temperature'];
        }

        return implode(' · ', $parts);
    }

    /**
     * Record the action, flash a toast, and bounce back to the dashboard.
     */
    private function finish(Device $device, string $action, bool $success, string $detail): RedirectResponse
    {
        PocActionLog::create([
            'device_id' => $device->id,
            'action' => $action,
            'result' => $success ? 'success' : 'failure',
            'detail' => $detail,
        ]);

        Inertia::flash('toast', [
            'type' => $success ? 'success' : 'error',
            'message' => "{$device->name}: {$detail}",
        ]);

        return back();
    }

    private function rejectNonProjector(): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'PJLink actions are only available for projectors.',
        ]);

        return back();
    }
}

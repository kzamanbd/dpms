<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\PocActionLog;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    /**
     * The single POC device dashboard: live status plus action controls.
     */
    public function index(): Response
    {
        $devices = Device::query()
            ->orderBy('name')
            ->with(['actionLogs' => fn ($query) => $query->latest('id')->limit(1)])
            ->get()
            ->map(fn (Device $device): array => [
                'id' => $device->id,
                'name' => $device->name,
                'type' => $device->type->value,
                'type_label' => $device->type->label(),
                'ip' => $device->ip,
                'mac' => $device->mac,
                'vlan' => $device->vlan,
                'status' => $device->status->value,
                'last_seen' => $device->last_seen?->toIso8601String(),
                'supports_pjlink' => $device->supportsPjlink(),
                'can_wake' => $device->mac !== null,
                'is_cross_vlan' => $device->wol_broadcast !== null,
                'last_action' => $device->actionLogs->first()?->only(['action', 'result', 'detail', 'created_at']),
            ]);

        $recentLogs = PocActionLog::query()
            ->with('device:id,name')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (PocActionLog $log): array => [
                'id' => $log->id,
                'device' => $log->device->name,
                'action' => $log->action,
                'result' => $log->result,
                'detail' => $log->detail,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('devices/index', [
            'devices' => $devices,
            'recentLogs' => $recentLogs,
        ]);
    }
}

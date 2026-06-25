<?php

namespace App\Http\Controllers;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\PocActionLog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $devices = Device::query()->get();

        $stats = [
            'total' => $devices->count(),
            'online' => $devices->where('status', DeviceStatus::Online)->count(),
            'offline' => $devices->where('status', DeviceStatus::Offline)->count(),
            'unknown' => $devices->where('status', DeviceStatus::Unknown)->count(),
            'projectors' => $devices->where('type', DeviceType::Projector)->count(),
            'wakeable' => $devices->whereNotNull('mac')->count(),
        ];

        $recentLogs = PocActionLog::query()
            ->with('device:id,name')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (PocActionLog $log): array => [
                'id' => $log->id,
                'device' => $log->device->name,
                'action' => $log->action,
                'result' => $log->result,
                'detail' => $log->detail,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'recentLogs' => $recentLogs,
        ]);
    }
}

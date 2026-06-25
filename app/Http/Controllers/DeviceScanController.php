<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDevicesRequest;
use App\Models\Device;
use App\Services\NetworkScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DeviceScanController extends Controller
{
    /**
     * Discover reachable hosts on the server's subnet. Returns JSON so the scan
     * modal can show a loading state while the sweep runs.
     */
    public function scan(NetworkScanService $scanner): JsonResponse
    {
        return response()->json($scanner->scan());
    }

    /**
     * Bulk-create the devices the user selected from the scan results. Existing
     * IPs are skipped so re-importing is idempotent.
     */
    public function import(ImportDevicesRequest $request): RedirectResponse
    {
        $imported = 0;

        foreach ($request->validated()['devices'] as $candidate) {
            $device = Device::firstOrCreate(
                ['ip' => $candidate['ip']],
                [
                    'name' => $candidate['name'],
                    'type' => $candidate['type'],
                    'mac' => $candidate['mac'] ?? null,
                ],
            );

            if ($device->wasRecentlyCreated) {
                $imported++;
            }
        }

        $skipped = count($request->validated()['devices']) - $imported;
        $message = $imported === 1 ? '1 device imported.' : "{$imported} devices imported.";
        if ($skipped > 0) {
            $message .= " {$skipped} already existed.";
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}

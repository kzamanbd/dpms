<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceActionController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ToolsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
    Route::put('devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
    Route::delete('devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
    Route::post('devices/{device}/power-on', [DeviceActionController::class, 'powerOn'])->name('devices.power-on');
    Route::post('devices/{device}/power-off', [DeviceActionController::class, 'powerOff'])->name('devices.power-off');
    Route::post('devices/{device}/status', [DeviceActionController::class, 'status'])->name('devices.status');
    Route::post('devices/{device}/wake', [DeviceActionController::class, 'wake'])->name('devices.wake');

    Route::get('tools', [ToolsController::class, 'index'])->name('tools.index');
    Route::get('docs', [DocsController::class, 'index'])->name('docs.index');
});

require __DIR__.'/settings.php';

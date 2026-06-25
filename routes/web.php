<?php

use App\Http\Controllers\DeviceActionController;
use App\Http\Controllers\DeviceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::post('devices/{device}/power-on', [DeviceActionController::class, 'powerOn'])->name('devices.power-on');
    Route::post('devices/{device}/power-off', [DeviceActionController::class, 'powerOff'])->name('devices.power-off');
    Route::post('devices/{device}/status', [DeviceActionController::class, 'status'])->name('devices.status');
    Route::post('devices/{device}/wake', [DeviceActionController::class, 'wake'])->name('devices.wake');
});

require __DIR__.'/settings.php';

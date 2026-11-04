<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Plain JSON endpoint for the camera scan page (bypasses Livewire).
Route::post('/scan/process', [ScanController::class, 'process'])
    ->middleware('auth')
    ->name('scan.process');

Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');

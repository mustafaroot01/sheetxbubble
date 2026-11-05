<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Plain JSON endpoints for the camera scan page (bypass Livewire).
// process = correct + preview (no save); save = persist after confirm.
Route::post('/scan/process', [ScanController::class, 'process'])
    ->middleware('auth')
    ->name('scan.process');
Route::post('/scan/save', [ScanController::class, 'save'])
    ->middleware('auth')
    ->name('scan.save');
Route::get('/scan/exam-info', [ScanController::class, 'examInfo'])
    ->middleware('auth')
    ->name('scan.exam-info');
Route::post('/scan/save-realtime', [ScanController::class, 'saveRealtime'])
    ->middleware('auth')
    ->name('scan.save-realtime');

Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');

<?php

use App\Http\Controllers\Api\OwnerMeetingController;
use Illuminate\Support\Facades\Route;

// Semua endpoint dilindungi API Key
// Wajib kirim header: X-API-KEY:...
Route::middleware('apikey')->prefix('owner')->group(function () {
    Route::get('/meetings',                           [OwnerMeetingController::class, 'index']);
    Route::get('/meetings/{id_mom}',                  [OwnerMeetingController::class, 'show']);
    Route::put('/meetings/{id_mom}/verify/{item_id}', [OwnerMeetingController::class, 'verify']);
});
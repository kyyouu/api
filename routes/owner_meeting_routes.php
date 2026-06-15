<?php

use App\Http\Controllers\Api\OwnerMeetingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Owner Meeting Routes
|--------------------------------------------------------------------------
| Tambahkan ke routes/api.php:
|
|   require __DIR__.'/owner_meeting_routes.php';
|
*/

Route::middleware('auth:sanctum')->prefix('owner')->group(function () {

    // Ambil semua meeting tipe Owner beserta MOM
    Route::get('/meetings', [OwnerMeetingController::class, 'index']);

    // Detail 1 meeting Owner by id_mom (contoh: MOM231218003)
    Route::get('/meetings/{id_mom}', [OwnerMeetingController::class, 'show']);

    // Owner approve / reject item MOM
    Route::put('/meetings/{id_mom}/verify/{item_id}', [OwnerMeetingController::class, 'verify']);
});
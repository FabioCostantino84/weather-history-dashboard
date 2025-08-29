<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CityController;

// Homepage
Route::view('/', 'welcome');

Route::post('/cities', [CityController::class, 'store'])->name('cities.store');
Route::get('/cities/{city}/stats', [CityController::class, 'stats'])->name('cities.stats');

Route::get('/test-stats', function () {
    return view('city.stats', ['city' => (object) [
        'name' => 'Roma',
        'country' => 'IT'
    ]]);
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CityController;

// Homepage -> form di ricerca
Route::view('/', 'city.search');

// Salva/ricerca città
Route::post('/cities', [CityController::class, 'store'])->name('cities.store');

// Statistiche per una città
Route::get('/cities/{city}/stats', [CityController::class, 'stats'])->name('cities.stats');
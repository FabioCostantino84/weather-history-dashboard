<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CityController;

// Una sola pagina: form città + range + risultati
Route::get('/', [CityController::class, 'dashboard'])->name('dashboard');
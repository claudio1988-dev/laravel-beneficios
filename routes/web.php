<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BeneficioController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/api/beneficios-filtrados', [BeneficioController::class, 'filtrados']);

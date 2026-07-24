<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthCheckController;

Route::get('/up', HealthCheckController::class)->name('health');

Route::view('/', 'landing-en')->name('home');
Route::view('/fa', 'landing')->name('home.fa');
Route::redirect('/en', '/', 301);

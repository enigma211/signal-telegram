<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'landing-en')->name('home');
Route::view('/fa', 'landing')->name('home.fa');
Route::redirect('/en', '/', 301);

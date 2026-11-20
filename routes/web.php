<?php

use App\Http\Controllers\Common\Language\LanguageController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;


Route::get('/', [LandingController::class, 'index'])
    ->where('vue', '[\/\w\.-]*')
    ->name('landing-page');


Route::get('languages/{locale}', [LanguageController::class, 'index']);


Route::get('register', function () {
    return view('app');
})->where('vue', '[\/\w\.-]*')->name('subscriber.register');


Route::get('login', function () {
    return view('app');
})->where('vue', '[\/\w\.-]*')->name('login');



Route::get('{any}', function () {
    return view('app');
})->where('any', '.*');

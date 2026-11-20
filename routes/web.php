<?php

declare(strict_types=1);

use App\Http\Controllers\Common\Language\LanguageController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])
    ->where('vue', '[\/\w\.-]*')
    ->name('landing-page');

Route::get('languages/{locale}', [LanguageController::class, 'index']);

Route::get('register', fn () => view('app'))->where('vue', '[\/\w\.-]*')->name('subscriber.register');

Route::get('login', fn () => view('app'))->where('vue', '[\/\w\.-]*')->name('login');

Route::get('{any}', fn () => view('app'))->where('any', '.*');

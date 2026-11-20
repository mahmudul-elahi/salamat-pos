<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\EntryJobController;
use App\Http\Controllers\Pos\JobApplicationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos', 'as' => 'pos.'], function () {
    Route::apiResource('entry-jobs', EntryJobController::class);
    Route::apiResource('applicants', JobApplicationController::class);
});

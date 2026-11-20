<?php

namespace App\Http\Controllers\Common\Language;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

class LanguageController extends Controller
{
    public function index(string $locale): JsonResponse
    {

        session(['locale' => $locale]);
        App::setLocale(session('locale')); // Update laravel app locale

        if (Lang::get('default')) {
            return response()->json(['locale' => App::currentLocale(), 'languages' => Lang::get('default')], 200); // Send languages
        }

        return response()->json(['locale' => App::currentLocale(), 'languages' => []], 200); // Send languages
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class LandingController extends Controller
{
    public function index()
    {
        return redirect('login');

    }
}

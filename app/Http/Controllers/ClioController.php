<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class ClioController extends Controller
{
    public function index () {
        return Socialite::driver('clio')->redirect();
    }

    public function callback (Request $request) {
        dd($request);
    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;
use App\ClioApiTokens;

class ClioController extends Controller
{
    public function index () {
        return Socialite::driver('clio')->redirect();
    }

    public function callback (Request $request) {
        $user = Socialite::driver('clio')->user();
        dump($request);
        dd($user);
    }

}

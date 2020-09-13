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
        $tokens = ClioApiTokens::firstOrCreate();
        $tokens->name = $user->name;
        $tokens->email = $user->email;
        $tokens->user_id = $user->id;

        $tokens->token_type = $user->accessTokenResponseBody['token_type'];
        $tokens->access_token = $user->token;
        $tokens->expires_in = $user->expiresIn;
        $tokens->refresh_token = $user->refreshToken;
        $tokens->save();
        dump($tokens);
        dump($request);
        dd($user);
    }

}

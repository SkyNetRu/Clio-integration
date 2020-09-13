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

    public function callback () {
        $user = Socialite::driver('clio')->user();
        $tokens = ClioApiTokens::firstOrNew(['id' => 1]);
        $tokens->name = $user->name;
        $tokens->email = $user->email;
        $tokens->user_id = $user->id;

        $tokens->token_type = $user->accessTokenResponseBody['token_type'];
        $tokens->access_token = $user->token;
        $tokens->expires_in = $user->expiresIn;
        $tokens->refresh_token = $user->refreshToken;
        $tokens->save();
        dump($tokens);

        return Socialite::driver('clio')
            ->with(["grant_type" => "refresh_token", 'refresh_token' => $tokens->refresh_token])
            ->redirect();
        dd($test);
    }

}

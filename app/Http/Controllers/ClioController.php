<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use App\ClioApiTokens;
use Carbon\Carbon;

class ClioController extends Controller
{
    public function index () {
        $tokens = ClioApiTokens::find(1);
//        $current = Carbon::now();
//        $expired = Carbon::parse($tokens->updated_at)
//            ->addSeconds($tokens->expires_in);
//        if ($tokens AND true ){
//            $url = env('CLIO_BASE_URL', 'https://app.clio.com').'/oauth/token';
//            $params = [
//                "client_id" => env('CLIO_APP_KEY'),
//                "client_secret" => env('CLIO_APP_SECRET'),
//                "grant_type" => "refresh_token",
//                "refresh_token" => $tokens->refresh_token,
//            ];
//            $response = Http::post($url, $params);
//            dump($url);
//            dump($params);
//            dd($response);
//        }
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
    }

}

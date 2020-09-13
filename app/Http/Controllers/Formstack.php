<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ClioApiTokens;

class Formstack extends Controller
{
    public function handleForm (Request $request) {
        $input = json_decode($request->getContent());
        if ($input->HandshakeKey == env('FORMSTACK_KEY')) {
            $tokens = ClioApiTokens::find(1);
            $url = env('CLIO_API_URL').'contacts.json';
            $response = Http::withToken($tokens->access_token)->get($url);
            dump($response);
        }
        dd($input);
    }
}

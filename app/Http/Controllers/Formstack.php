<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Formstack extends Controller
{
    public function handleForm (Request $request) {
        $input = json_decode($request->getContent());
        dd($input);
    }
}

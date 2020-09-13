<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Formstack extends Controller
{
    public function handleForm (Request $request) {
        $input = $request->getContent();
        dump(file_get_contents("php://input"));
        dd($input);
    }
}

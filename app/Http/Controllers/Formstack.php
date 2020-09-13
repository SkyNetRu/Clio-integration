<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ClioApiTokens;
use Illuminate\Support\Facades\Http;

class Formstack extends Controller
{
    public function handleForm(Request $request)
    {
        $input = json_decode($request->getContent());
        if ($input->HandshakeKey == env('FORMSTACK_KEY')) {
            $tokens = ClioApiTokens::find(1);
            $url = env('CLIO_API_URL') . 'contacts.json';
            $getContacts = Http::withToken($tokens->access_token)->get($url);
            dump($getContacts->json());
            $data = \GuzzleHttp\json_encode(
                [
                    'data' => [
                        "first_name" => $input->name->value->first,
                        "middle_name" => $input->name->value->middle,
                        "last_name" => $input->name->value->last,
                        "email_addresses" => [
                            [
                                "name" => "Other",
                                "address" => $input->email->value,
                                "default_email" => true
                            ]
                        ],
                        "phone_numbers" => [
                            [
                                "name" => "Other",
                                "number" => $input->phone->value,
                                "default_number" => true
                            ]
                        ],
                    ]
                ]
            );
            dump($data);
            $createContact = Http::withToken($tokens->access_token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, ['body' => $data]);
            dump($createContact->json());
        }
        dd($input);
    }
}

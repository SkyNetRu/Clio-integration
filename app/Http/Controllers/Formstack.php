<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ClioApiTokens;
use Illuminate\Support\Facades\Http;

class Formstack extends Controller
{
    public $tokens = null;

    public function __construct()
    {
        $this->tokens = ClioApiTokens::find(1);
    }

    public function handleForm(Request $request)
    {
        $input = json_decode($request->getContent());
        if ($input->HandshakeKey == env('FORMSTACK_KEY')) {

            $url = env('CLIO_API_URL') . 'contacts.json';
            $getContacts = Http::withToken($this->tokens->access_token)
                ->withOptions(['query' => ['query' => $input->email->value]])
                ->get($url);

            dump($getContacts->json());
//            $data = ['data' =>
//                [
//                    "first_name" => $input->name->value->first,
//                    "middle_name" => $input->name->value->middle,
//                    "last_name" => $input->name->value->last,
//                    "email_addresses" => [
//                        [
//                            "name" => "Other",
//                            "address" => $input->email->value,
//                            "default_email" => true
//                        ]
//                    ],
//                    "phone_numbers" => [
//                        [
//                            "name" => "Other",
//                            "number" => $input->phone->value,
//                            "default_number" => true
//                        ]
//                    ],
//                    "type" => "Person",
//                ]
//            ];
//            dump($data);
//            $createdContact = $this->createContact($data);
//            dump($createdContact);
//
//            //Associated contact
//            $data = ['data' =>
//                [
//                    "first_name" => $input->associated_name->value->first,
//                    "middle_name" => $input->associated_name->value->middle,
//                    "last_name" => $input->associated_name->value->last,
//                    "type" => "Person",
//                ]
//            ];
//            $createdContact = $this->createContact($data);
//            dump($createdContact);
        }
        dd($input);
    }


    /**
     * Create Contact in Clio, return created contact
     *
     * @param  array
     * @return array
     */
    public function createContact ($data) {
        $url = env('CLIO_API_URL') . 'contacts.json';
        $createContact = Http::withToken($this->tokens->access_token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['json' => $data])
            ->post($url);
        return $createContact->json();
    }
}

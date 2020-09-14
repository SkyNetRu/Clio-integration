<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ClioApiTokens;
use Illuminate\Support\Facades\Http;

class Formstack extends Controller
{
    public $tokens = null;
    public $url_contact = '';
    public $url_matters = '';

    public function __construct()
    {
        $this->tokens = ClioApiTokens::find(1);
        $this->url_contact = env('CLIO_API_URL') . 'contacts.json';
        $this->url_matters = env('CLIO_API_URL') . 'matters.json';
    }

    public function handleForm(Request $request)
    {
        $input = json_decode($request->getContent());
        if ($input->HandshakeKey != env('FORMSTACK_KEY')) {
            return response()->json(['error' => 'Invalid Form Key'],401);
        }

        $contact = $this->getContactByEmail($input->email->value);
        dump($contact);

        if ($contact['meta']['records'] == 0) {
            $data = [
                'data' =>
                    [
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
                        "type" => "Person",
                    ]
            ];
            dump($data);
            $contact = $this->createContact($data);
            dump($contact);
        } else {
            $contact = $contact['data'][0];
        }

        $matter = $this->getMattersByContactID($contact['id']);
        if ($matter['meta']['records'] == 0) {
            $data = [
                'data' =>
                    [
                        "client" => [
                            'id' => $contact['id']
                        ],
                        "description" => 'description'
                    ]
            ];
            $matter = $this->createMatter($data)['data'];
        }
        dump($matter);

        $associatedContact = $this->getContactByEmail($input->associated_email->value);
        if ($associatedContact['meta']['records'] == 0) {
            $data = [
                'data' =>
                    [
                        "first_name" => $input->associated_name->value->first,
                        "middle_name" => $input->associated_name->value->middle,
                        "last_name" => $input->associated_name->value->last,
                        "email_addresses" => [
                            [
                                "name" => "Other",
                                "address" => $input->associated_email->value,
                                "default_email" => true
                            ]
                        ],
                        "type" => "Person",
                    ]
            ];
            $associatedContact = $this->createContact($data);
            dump($associatedContact);
        } else {
            $associatedContact = $associatedContact['data'][0];
        }

        $data = [
            'data' =>
                [
                    "relationships" => [
                        [
                            "description" => "Associated contact",
                            "contact" => [
                                'id' => $associatedContact['id']
                            ],
                        ]
                    ],
                ]
        ];

        $matter = $this->updateMatter($data, $matter['id']);
        $matterAssoc = $this->getMattersByContactID($associatedContact['id']);
        dump($matter);
        dump($matterAssoc);
        dd($input);
    }


    /**
     * Create Contact in Clio, return created contact
     *
     * @param array
     * @return array
     */
    public function createContact($data)
    {
        return Http::withToken($this->tokens->access_token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['json' => $data])
            ->post($this->url_contact)->json()['data'];
    }

    /**
     * Create Matter in Clio, return created matter
     *
     * @param array
     * @return array
     */
    public function createMatter($data)
    {
        return Http::withToken($this->tokens->access_token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['json' => $data])
            ->post($this->url_matters)->json()['data'];
    }

    /**
     * Update Matter in Clio, return created matter
     *
     * @param array
     * @return array
     */
    public function updateMatter($data, $id)
    {
        $url = env('CLIO_API_URL') . 'matters/'.$id.'.json';
        return Http::withToken($this->tokens->access_token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['json' => $data])
            ->patch($url)->json()['data'];
    }

    /**
     * Get Contact by email from Clio API
     *
     * @param string
     * @return array
     */
    public function getContactByEmail($email)
    {
        return Http::withToken($this->tokens->access_token)
            ->withOptions(['query' => ['query' => $email]])
            ->get($this->url_contact)->json();
    }

    /**
     * Get Matters by Contact ID from Clio API
     *
     * @param string|integer
     * @return array
     */
    public function getMattersByContactID($contact_id)
    {
        return Http::withToken($this->tokens->access_token)
            ->withOptions(['query' => ['client_id' => $contact_id]])
            ->get($this->url_matters)->json();
    }
}

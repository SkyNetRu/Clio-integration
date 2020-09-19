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
    public $contacts_fields = 'id,etag,phone_numbers,email_addresses,addresses,name,first_name,middle_name,last_name';
    public $matters_fields = 'id,etag,relationships';

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

        $contact = $this->getByQuery(['query' => $input->email->value, 'fields' => $this->contacts_fields], 'contacts');
        dd($contact);

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
            $contact = $this->create($data, ['fields' => $this->contacts_fields], 'contacts')['data'];
        } else {
            $contact = $contact['data'][0];
        }

        $matter = $this->getByQuery(['client_id' => $contact['id'], 'fields' => $this->matters_fields], 'matters');
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
            $matter = $this->create($data, ['fields' => $this->matters_fields], 'matters');
        } else {
            $matter = $matter['data'][0];
        }

        $associatedContact = $this->getByQuery(['query' => $input->associated_email->value, 'fields' => $this->contacts_fields], 'contacts');
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
            $associatedContact = $this->create($data, ['fields' => $this->contacts_fields], 'contacts')['data'];
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
        $this->update($data, $matter['id'], ['fields' => $this->matters_fields],'matters');

        dump($contact);
        dump($associatedContact);

        $matter = $this->getByQuery(['client_id' => $contact['id'], 'fields' => $this->matters_fields], 'matters')['data'];
        $matterAssoc = $this->getByQuery(['query' => $associatedContact['name'], 'fields' => $this->matters_fields], 'matters')['data'];


        dump($matter);
        dump($matterAssoc);
    }


    /**
     * Create instance Clio, return created instance
     *
     * @param $data array
     * @param $query array
     * @param $type string
     * @return array
     */
    public function create($data, $query, $type)
    {
        $url = env('CLIO_API_URL') .$type.'.json';
        return Http::withToken($this->tokens->access_token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['json' => $data] + $query)
            ->post($url)->json()['data'];
    }

    /**
     * Update instance in Clio, return updated instance
     *
     * @param $data array
     * @param $id string|integer
     * @param $query array
     * @param $type string
     * @return array
     */
    public function update($data, $id, $query, $type)
    {
        $url = env('CLIO_API_URL') . $type. '/'.$id.'.json';
        return Http::withToken($this->tokens->access_token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['json' => $data] + $query)
            ->patch($url)->json()['data'];
    }

    /**
     * Get instance from Clio, return instance
     *
     * @param $query array
     * @param $type string
     * @return array
     */
    public function getByQuery ($query, $type) {
        $url = env('CLIO_API_URL') .$type.'.json';
        return Http::withToken($this->tokens->access_token)
            ->withOptions(['query' => $query])
            ->get($url)->json();
    }
}

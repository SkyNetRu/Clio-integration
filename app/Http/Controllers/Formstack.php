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
    public $matters_fields = 'id,etag,relationships,client';
    public $relationships_fields = 'id,etag,description,matter,contact';
    public $clio_grow = false;

    public function __construct()
    {
        $this->tokens = ClioApiTokens::find(1);
        $this->url_contact = env('CLIO_API_URL') . 'contacts.json';
        $this->url_matters = env('CLIO_API_URL') . 'matters.json';
        if (env('CLIO_GROW_TOKEN', null)) {
            $this->clio_grow = true;
        }
    }

    public function handleForm(Request $request)
    {
        $input = json_decode($request->getContent());
        if ($input->HandshakeKey != env('FORMSTACK_KEY')) {
            return response()->json(['error' => 'Invalid Form Key'],401);
        }

        $contact = $this->getByQuery(['query' => $input->email->value, 'fields' => $this->contacts_fields], 'contacts');

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
            $contact = $this->create($data, ['fields' => $this->contacts_fields], 'contacts');
        } else {
            $data = [
                'data' =>
                    [
                        "first_name" => $input->name->value->first,
                        "middle_name" => $input->name->value->middle,
                        "last_name" => $input->name->value->last,
                        "phone_numbers" => [
                            [
                                "name" => "Other",
                                "number" => $input->phone->value
                            ]
                        ],
                    ]
            ];
            $contact = $contact['data'][0];
            $contact = $this->update($data, $contact['id'], ['fields' => $this->contacts_fields],'contacts');

        }

        $matters = $this->searchMattersWithContact($contact['id']);
        if (count($matters) == 0) {
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
            $matter = $this->getByQuery(['id' => $matters[0], 'fields' => $this->matters_fields], 'matters')['data'][0];
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
            $associatedContact = $this->create($data, ['fields' => $this->contacts_fields], 'contacts');
        } else {
            $data = [
                'data' =>
                    [
                        "first_name" => $input->associated_name->value->first,
                        "middle_name" => $input->associated_name->value->middle,
                        "last_name" => $input->associated_name->value->last,
                    ]
            ];
            $associatedContact = $associatedContact['data'][0];
            $associatedContact = $this->update($data, $associatedContact['id'], ['fields' => $this->contacts_fields],'contacts');
        }

        $matter_assoc_contact = false;
        if (isset($matter['relationships'])) {
            foreach ($matter['relationships'] as $relationship) {
                if ($relationship['contact']['id'] == $associatedContact['id']) {
                    $matter_assoc_contact = true;
                    break;
                }
            }
        }

        if (!$matter_assoc_contact AND $matter) {
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
        }
        $matters = $this->getByQuery(['fields' => $this->matters_fields], 'matters');

        if ($this->clio_grow) {
            $data = [
                "from_first" => $input->name->value->first,
                "from_last" => $input->name->value->last,
                "from_message" => "New Formstack Lead",
                "from_email" => $input->email->value,
                "from_phone" => $input->phone->value,
                "from_source" => "Formstack Form"
            ];
            $this->sendGrowLeadInbox($data);
        }
        dump($matters);
    }


    /**
     * Search Matters with Contact ID
     *
     * @param $contact_id int
     * @return array
     */
    public function searchMattersWithContact ($contact_id) {
        $matters = $this->getByQuery(['fields' => $this->matters_fields], 'matters')['data'];
        $matters_arr = [];
        if ($matters) {
            foreach ($matters as $matter) {
                foreach ($matter['relationships'] as $relationship) {
                    if ($relationship['contact']['id'] == $contact_id) {
                        $matters_arr[] = $matter['id'];
                        break;
                    }
                }
                if ($matter['client']['id'] == $contact_id) {
                    $matters_arr[] = $matter['id'];
                }
            }
        }
        return array_unique($matters_arr);
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

    public function sendGrowLeadInbox($inbox_lead) {
        $data = [
            'inbox_lead' => $inbox_lead,
            'inbox_lead_token' => env('CLIO_GROW_TOKEN')
        ];
        return Http::withHeaders(['Content-Type' => 'application/json'])
            ->post('https://grow.clio.com/inbox_leads', $data)->json();
    }
}

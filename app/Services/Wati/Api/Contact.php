<?php

namespace App\Services\Wati\Api;

use GuzzleHttp\Client;

class Contact extends WatiApi
{
    public function create()
    {
        # https://docs.wati.io/reference/post_-serverurl-api-v1-addcontact-whatsappnumber
        $client = new Client([
            'verify' => false
        ]);

        $url = $this->baseUrl . '/api/v1/addContact/' . $this->business->phone_number;

        $bodyRequest = [
            'name' => $this->business->name,
            'customParams' => [
                [
                    'name' => 'business_id',
                    'value' => $this->business->getKey(),
                ],
                [
                    'name' => 'business_email',
                    'value' => $this->business->email,
                ],
                [
                    'name' => 'created_at',
                    'value' => $this->business->created_at
                ]
            ]
        ];

        $response = $client->request("POST", $url, [
            'body' => json_encode($bodyRequest),
            'headers' => [
                'Content-Type' => 'application/json-patch+json',
                'Authorization' => 'Bearer ' . $this->accessToken
            ]
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        if (!$responseBody->result) {
            throw new \Exception($responseBody->info);
        }

        return $responseBody;
    }
}

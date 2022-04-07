<?php

namespace App\Services\Wati\Api;

use GuzzleHttp\Client;

class TemplateMessage extends WatiApi
{
    public function send($templateName, $broadcastName)
    {
        # https://docs.wati.io/reference/post_-serverurl-api-v1-sendtemplatemessage
        $client = new Client([
            'verify' => false
        ]);

        $url = $this->baseUrl . '/api/v1/sendTemplateMessage?whatsappNumber=' . $this->business->phone_number;

        $bodyRequest = [
            'template_name' => $templateName,
            'broadcast_name' => $broadcastName,
            'parameters' => []
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

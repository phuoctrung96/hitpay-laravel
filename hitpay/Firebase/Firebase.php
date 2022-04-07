<?php

namespace HitPay\Firebase;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Firebase
{
    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param \HitPay\Firebase\Message $message
     * @param array|string $token
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMessage(Message $message, $token)
    {
        if (app()->environment() == 'local') {
            Log::info("\n"
                ."\n[Firebase]"
                ."\n=========="
                ."\nTokens  : ".(is_array($token) ? count($token).' tokens' : $token)
                ."\nTitle   : ".$message->title
                ."\nMessage : ".$message->message);

            return true;
        }

        $jsonBody = array_merge([
            'notification' => [
                'title' => $message->title,
                'text' => $message->message,
                'icon' => 'notification',
                'sound' => 'default',
            ],
            'priority' => $message->priority,
            'delay_while_idle' => true,
            'time_to_live' => 108,
        ], $message->data);

        if (is_array($token)) {
            $jsonBody['registration_ids'] = array_values($token);
        } else {
            $jsonBody['to'] = $token;
        }

        $http = new Client([
            'base_uri' => 'https://fcm.googleapis.com/fcm/send',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'key='.$this->secret,
            ],
            'json' => $jsonBody,
        ]);

        $result = $http->request('post');
        $contents = $result->getBody()->getContents();
        $contents = json_decode($contents, true);

        return boolval($contents['success']);
    }
}

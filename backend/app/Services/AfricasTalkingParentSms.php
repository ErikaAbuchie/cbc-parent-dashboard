<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use RuntimeException;
use Throwable;

class AfricasTalkingParentSms implements ParentSmsSender
{
    public function send(string $phone, string $message): void
    {
        try {
            $client = new class(config('services.africastalking.username'), config('services.africastalking.key')) extends AfricasTalking {
                public function __construct($username, $apiKey)
                {
                    parent::__construct($username, $apiKey);
                    // Bound network waits even on Windows workers without pcntl timeouts.
                    $this->client = new \GuzzleHttp\Client([
                        'base_uri' => $this->baseUrl, 'timeout' => 15, 'connect_timeout' => 5,
                        'headers' => ['apikey' => $apiKey, 'Accept' => 'application/json',
                            'Content-Type' => 'application/x-www-form-urlencoded'],
                    ]);
                }
            };
            $result = $client->sms()->send(['to' => $phone, 'message' => $message]);
            $recipients = $result['data']->SMSMessageData->Recipients ?? [];
            if (count($recipients) !== 1 || (int) ($recipients[0]->statusCode ?? 0) !== 101) {
                throw new RuntimeException('SMS not accepted.');
            }
        } catch (Throwable) {
            // Provider exceptions can contain the SMS body; never propagate or log them.
            throw new RuntimeException('SMS submission failed. Check the sandbox configuration and recipient.');
        }
    }
}

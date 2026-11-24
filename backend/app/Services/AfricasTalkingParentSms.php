<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Facades\Log;
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
                Log::warning('Parent SMS rejected by provider', [
                    'recipient_count' => count($recipients),
                    'provider_status_code' => (int) ($recipients[0]->statusCode ?? 0),
                ]);
                throw new RuntimeException('SMS not accepted.');
            }
        } catch (Throwable $error) {
            $diagnostic = ['exception_type' => get_class($error)];
            if ($error instanceof \GuzzleHttp\Exception\RequestException) {
                $diagnostic['http_status'] = $error->getResponse()?->getStatusCode();
                // Classify only known phrases. Never log the response body:
                // providers and intermediaries may echo credentials or SMS text.
                $body = strtolower((string) $error->getResponse()?->getBody());
                $diagnostic['reason_category'] = 'unrecognised';
                foreach ([
                    'invalid_api_key' => '/invalid.{0,20}api.?key|api.?key.{0,20}invalid/',
                    'authentication' => '/authentication|unauthorized|invalid username|invalid credentials/',
                    'ip_restriction' => '/ip address|ip not|whitelist|allowlist/',
                    'account_restriction' => '/suspend|account.{0,30}disabled|account.{0,30}inactive/',
                    'insufficient_balance' => '/insufficient|not enough credit/',
                    'edge_security' => '/cloudflare|captcha|access denied|request blocked/',
                    'invalid_recipient' => '/invalid.{0,20}phone|invalid.{0,20}recipient/',
                ] as $category => $pattern) {
                    if (preg_match($pattern, $body)) {
                        $diagnostic['reason_category'] = $category;
                        break;
                    }
                }
            }
            if ($error instanceof \GuzzleHttp\Exception\ConnectException) {
                $diagnostic['curl_code'] = (int) ($error->getHandlerContext()['errno'] ?? 0);
            }
            Log::warning('Parent SMS submission diagnostic', $diagnostic);
            // Provider exceptions can contain the SMS body; never propagate or log them.
            throw new RuntimeException('SMS submission failed. Check the sandbox configuration and recipient.');
        }
    }
}

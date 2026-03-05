<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaVerifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $secretKey
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->secretKey !== '';
    }

    public function verify(?string $token, ?string $remoteIp): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if (!is_string($token) || trim($token) === '') {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp ?? '',
                ],
            ]);

            /** @var array{success?: bool} $payload */
            $payload = $response->toArray(false);

            return ($payload['success'] ?? false) === true;
        } catch (\Throwable) {
            return false;
        }
    }
}

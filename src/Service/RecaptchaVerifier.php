<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaVerifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $secretKey,
        private readonly string $environment,
    ) {
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        // In non-production environments (dev, test), always allow to simplify local development.
        if ($this->environment !== 'prod') {
            return true;
        }

        if (!$token || $this->secretKey === '' || $this->secretKey === 'your_secret_key_here') {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => array_filter([
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $ip,
                ]),
            ]);

            if (200 !== $response->getStatusCode()) {
                return false;
            }

            $data = $response->toArray(false);

            return isset($data['success']) && $data['success'] === true;
        } catch (\Throwable) {
            return false;
        }
    }
}



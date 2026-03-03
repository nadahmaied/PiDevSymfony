<?php

namespace App\Service;

class SmsService
{
    /** @var mixed */
    private $client;
    private string $from;

    public function __construct(
        string $twilioSid,
        string $twilioToken,
        string $twilioFrom
    ) {
        $clientClass = 'Twilio\\Rest\\Client';
        if (!class_exists($clientClass)) {
            throw new \RuntimeException(
                'Missing Twilio SDK. Run "composer require twilio/sdk".'
            );
        }

        $this->client = new $clientClass($twilioSid, $twilioToken);
        $this->from = $twilioFrom;
    }

    public function sendRappel(string $to, string $medecin, string $date, string $heure): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => "Rappel VitalTech\n" .
                    "RDV demain avec {$medecin}\n" .
                    "Date : {$date} a {$heure}\n" .
                    "Bonne preparation ! - VitalTech",
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

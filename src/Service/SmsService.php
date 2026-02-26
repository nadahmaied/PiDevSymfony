<?php

namespace App\Service;

use Twilio\Rest\Client;

class SmsService
{
    private Client $client;
    private string $from;

    public function __construct(
        string $twilioSid,
        string $twilioToken,
        string $twilioFrom
    ) {
        $this->client = new Client($twilioSid, $twilioToken);
        $this->from    = $twilioFrom;
    }

    public function sendRappel(string $to, string $medecin, string $date, string $heure): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => "🔔 Rappel VitalTech\n" .
                          "RDV demain avec {$medecin}\n" .
                          "Date : {$date} à {$heure}\n" .
                          "Bonne préparation ! — VitalTech"
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
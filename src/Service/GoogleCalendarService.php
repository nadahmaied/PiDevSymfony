<?php

namespace App\Service;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventReminder;
use Google\Service\Calendar\EventReminders;

class GoogleCalendarService
{
    private Calendar $service;
    private string   $calendarId;

    public function __construct(
        private string $credentialsPath,
        string $calendarId
    ) {
        $this->calendarId = $calendarId;
        $this->service    = $this->buildService();
    }

    // ─────────────────────────────────────────────
    // Build Google Client avec Service Account
    // ─────────────────────────────────────────────
    private function buildService(): Calendar
    {
        $client = new Client();
        $client->setApplicationName('VitalTech RDV');
        $client->setScopes([Calendar::CALENDAR]);
        $client->setAuthConfig($this->credentialsPath);

        return new Calendar($client);
    }

    // ─────────────────────────────────────────────
    // Récupérer les events d'un jour donné
    // ─────────────────────────────────────────────
    public function getEventsDuJour(\DateTime $date): array
    {
        $debut = clone $date;
        $debut->setTime(0, 0, 0);

        $fin = clone $date;
        $fin->setTime(23, 59, 59);

        $params = [
            'timeMin'      => $debut->format(\DateTime::RFC3339),
            'timeMax'      => $fin->format(\DateTime::RFC3339),
            'singleEvents' => true,
            'orderBy'      => 'startTime',
            'timeZone'     => 'Africa/Tunis',
        ];

        try {
            $events = $this->service->events->listEvents($this->calendarId, $params);
            return $events->getItems();
        } catch (\Exception $e) {
            return [];
        }
    }

    // ─────────────────────────────────────────────
    // Récupérer toutes les heures bloquées ce jour
    // (créneaux de 30 min déjà réservés)
    // ─────────────────────────────────────────────
    public function getHeuresBloquees(\DateTime $date): array
    {
        $events = $this->getEventsDuJour($date);
        $heures = [];

        foreach ($events as $event) {
            $startDt = $event->getStart()->getDateTime();
            if (!$startDt) continue;

            $start   = new \DateTime($startDt);
            $endDt   = $event->getEnd()->getDateTime();
            $end     = $endDt ? new \DateTime($endDt) : null;

            // Bloquer tous les créneaux de 30 min couverts par l'event
            $current = clone $start;
            while ($end && $current < $end) {
                $heures[] = $current->format('H:i');
                $current->modify('+30 minutes');
            }
        }

        return array_unique($heures);
    }

    // ─────────────────────────────────────────────
    // Créer un event = RÉSERVER un créneau
    // ─────────────────────────────────────────────
    public function createRdvEvent(array $data): string
    {
        $startStr = $data['date'] . 'T' . $data['heure'] . ':00';
        $startDt  = new \DateTime($startStr, new \DateTimeZone('Africa/Tunis'));
        $endDt    = clone $startDt;
        $endDt->modify('+30 minutes');

        $event = new Event();
        $event->setSummary('RDV - ' . $data['medecin'] . ' / ' . $data['patient']);
        $event->setDescription(
            "👤 Patient : " . $data['patient'] . "\n" .
            "📞 Tél : " . ($data['telephone'] ?? '') . "\n" .
            "📧 Email : " . ($data['email'] ?? '') . "\n" .
            "🏥 Spécialité : " . ($data['specialite'] ?? '') . "\n" .
            "📋 Motif : " . ($data['motif'] ?? 'Consultation')
        );

        $start = new EventDateTime();
        $start->setDateTime($startDt->format(\DateTime::RFC3339));
        $start->setTimeZone('Africa/Tunis');
        $event->setStart($start);

        $end = new EventDateTime();
        $end->setDateTime($endDt->format(\DateTime::RFC3339));
        $end->setTimeZone('Africa/Tunis');
        $event->setEnd($end);

        // Participant (email patient)
        if (!empty($data['email'])) {
            $attendee = new EventAttendee();
            $attendee->setEmail($data['email']);
            $event->setAttendees([$attendee]);
        }

        // Rappel email 60 min avant
        $reminder = new EventReminder();
        $reminder->setMethod('email');
        $reminder->setMinutes(60);

        $reminders = new EventReminders();
        $reminders->setUseDefault(false);
        $reminders->setOverrides([$reminder]);
        $event->setReminders($reminders);

        $created = $this->service->events->insert($this->calendarId, $event);

        return $created->getId();
    }

    // ─────────────────────────────────────────────
    // Annuler un event Google Calendar
    // ─────────────────────────────────────────────
    public function cancelEvent(string $eventId): void
    {
        try {
            $this->service->events->delete($this->calendarId, $eventId);
        } catch (\Exception $e) {
            // Event déjà supprimé ou inexistant
        }
    }

    // ─────────────────────────────────────────────
    // Jours qui ont au moins 1 RDV dans le mois
    // (pour colorier le calendrier front)
    // ─────────────────────────────────────────────
    public function getJoursOccupesDuMois(int $year, int $month): array
    {
        $debut = new \DateTime("$year-$month-01", new \DateTimeZone('Africa/Tunis'));
        $fin   = (clone $debut)->modify('last day of this month')->setTime(23, 59, 59);

        $params = [
            'timeMin'      => $debut->format(\DateTime::RFC3339),
            'timeMax'      => $fin->format(\DateTime::RFC3339),
            'singleEvents' => true,
            'orderBy'      => 'startTime',
            'maxResults'   => 500,
        ];

        try {
            $events = $this->service->events->listEvents($this->calendarId, $params);
            $jours  = [];
            foreach ($events->getItems() as $event) {
                $startDt = $event->getStart()->getDateTime();
                if ($startDt) {
                    $jours[] = (new \DateTime($startDt))->format('Y-m-d');
                }
            }
            return array_unique($jours);
        } catch (\Exception $e) {
            return [];
        }
    }
}
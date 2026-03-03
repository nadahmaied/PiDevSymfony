<?php

namespace App\Service;

class GoogleCalendarService
{
    /** @var mixed */
    private $service;
    private string $calendarId;

    public function __construct(
        private string $credentialsPath,
        string $calendarId
    ) {
        $this->calendarId = $calendarId;
        $this->service = $this->buildService();
    }

    /** @return mixed */
    private function buildService()
    {
        $client = $this->newGoogleObject('Google\\Client');
        $client->setApplicationName('VitalTech RDV');
        $client->setScopes(['https://www.googleapis.com/auth/calendar']);
        $client->setAuthConfig($this->credentialsPath);

        return $this->newGoogleObject('Google\\Service\\Calendar', [$client]);
    }

    /** @return list<mixed> */
    public function getEventsDuJour(\DateTime $date): array
    {
        $debut = clone $date;
        $debut->setTime(0, 0, 0);

        $fin = clone $date;
        $fin->setTime(23, 59, 59);

        $params = [
            'timeMin' => $debut->format(\DateTime::RFC3339),
            'timeMax' => $fin->format(\DateTime::RFC3339),
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'timeZone' => 'Africa/Tunis',
        ];

        try {
            $events = $this->service->events->listEvents($this->calendarId, $params);
            return $events->getItems();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return list<string> */
    public function getHeuresBloquees(\DateTime $date): array
    {
        $events = $this->getEventsDuJour($date);
        $heures = [];

        foreach ($events as $event) {
            $startDt = $event->getStart()->getDateTime();
            if (!$startDt) {
                continue;
            }

            $start = new \DateTime($startDt);
            $endDt = $event->getEnd()->getDateTime();
            $end = $endDt ? new \DateTime($endDt) : null;

            $current = clone $start;
            while ($end && $current < $end) {
                $heures[] = $current->format('H:i');
                $current->modify('+30 minutes');
            }
        }

        return array_values(array_unique($heures));
    }

    /**
     * @param array{date: string, heure: string, medecin: string, patient: string, telephone?: string, email?: string, specialite?: string, motif?: string} $data
     */
    public function createRdvEvent(array $data): string
    {
        $startStr = $data['date'] . 'T' . $data['heure'] . ':00';
        $startDt = new \DateTime($startStr, new \DateTimeZone('Africa/Tunis'));
        $endDt = clone $startDt;
        $endDt->modify('+30 minutes');

        $event = $this->newGoogleObject('Google\\Service\\Calendar\\Event');
        $event->setSummary('RDV - ' . $data['medecin'] . ' / ' . $data['patient']);
        $event->setDescription(
            "Patient : " . $data['patient'] . "\n" .
            "Tel : " . ($data['telephone'] ?? '') . "\n" .
            "Email : " . ($data['email'] ?? '') . "\n" .
            "Specialite : " . ($data['specialite'] ?? '') . "\n" .
            "Motif : " . ($data['motif'] ?? 'Consultation')
        );

        $start = $this->newGoogleObject('Google\\Service\\Calendar\\EventDateTime');
        $start->setDateTime($startDt->format(\DateTime::RFC3339));
        $start->setTimeZone('Africa/Tunis');
        $event->setStart($start);

        $end = $this->newGoogleObject('Google\\Service\\Calendar\\EventDateTime');
        $end->setDateTime($endDt->format(\DateTime::RFC3339));
        $end->setTimeZone('Africa/Tunis');
        $event->setEnd($end);

        if (!empty($data['email'])) {
            $attendee = $this->newGoogleObject('Google\\Service\\Calendar\\EventAttendee');
            $attendee->setEmail($data['email']);
            $event->setAttendees([$attendee]);
        }

        $reminder = $this->newGoogleObject('Google\\Service\\Calendar\\EventReminder');
        $reminder->setMethod('email');
        $reminder->setMinutes(60);

        $reminders = $this->newGoogleObject('Google\\Service\\Calendar\\EventReminders');
        $reminders->setUseDefault(false);
        $reminders->setOverrides([$reminder]);
        $event->setReminders($reminders);

        $created = $this->service->events->insert($this->calendarId, $event);

        return (string) $created->getId();
    }

    public function cancelEvent(string $eventId): void
    {
        try {
            $this->service->events->delete($this->calendarId, $eventId);
        } catch (\Throwable $e) {
        }
    }

    /** @return list<string> */
    public function getJoursOccupesDuMois(int $year, int $month): array
    {
        $debut = new \DateTime("$year-$month-01", new \DateTimeZone('Africa/Tunis'));
        $fin = (clone $debut)->modify('last day of this month')->setTime(23, 59, 59);

        $params = [
            'timeMin' => $debut->format(\DateTime::RFC3339),
            'timeMax' => $fin->format(\DateTime::RFC3339),
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'maxResults' => 500,
        ];

        try {
            $events = $this->service->events->listEvents($this->calendarId, $params);
            $jours = [];

            foreach ($events->getItems() as $event) {
                $startDt = $event->getStart()->getDateTime();
                if ($startDt) {
                    $jours[] = (new \DateTime($startDt))->format('Y-m-d');
                }
            }

            return array_values(array_unique($jours));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @param list<mixed> $arguments
     *  @return mixed
     */
    private function newGoogleObject(string $className, array $arguments = [])
    {
        if (!class_exists($className)) {
            throw new \RuntimeException(sprintf(
                'Missing Google API dependency: class "%s" not found. Run "composer require google/apiclient".',
                $className
            ));
        }

        return new $className(...$arguments);
    }
}

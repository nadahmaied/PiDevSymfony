<?php

namespace App\Tests;

use App\Entity\MissionVolunteer;
use App\Entity\User;
use App\Service\MissionVolunteerBusinessService;
use PHPUnit\Framework\TestCase;

class MissionVolunteerBusinessServiceTest extends TestCase
{
    public function testApplicationWindowIsOpenWhenMissionIsOuverteAndDateInRange(): void
    {
        $service = new MissionVolunteerBusinessService();
        $mission = $this->buildMission('Ouverte', '2026-03-01', '2026-03-31');

        $result = $service->isApplicationWindowOpen($mission, new \DateTimeImmutable('2026-03-15'));

        $this->assertTrue($result);
    }

    public function testApplicationWindowIsClosedWhenStatusIsNotOuverte(): void
    {
        $service = new MissionVolunteerBusinessService();
        $mission = $this->buildMission('Fermee', '2026-03-01', '2026-03-31');

        $result = $service->isApplicationWindowOpen($mission, new \DateTimeImmutable('2026-03-15'));

        $this->assertFalse($result);
    }

    public function testVolunteerCanApplyWhenRulesAreSatisfied(): void
    {
        $service = new MissionVolunteerBusinessService();
        $mission = $this->buildMission('Ouverte', '2026-03-01', '2026-03-31');
        $mission->setRequiredSkills('secourisme, communication');
        $mission->setCriticalPeriods('matin, soir');

        $user = new User();
        $user->setSkillsProfile('communication, logistique');
        $user->setAvailabilityProfile('soir, weekend');

        $result = $service->canVolunteerApply($mission, $user, new \DateTimeImmutable('2026-03-20'));

        $this->assertTrue($result);
    }

    public function testVolunteerCannotApplyWhenNoSkillMatches(): void
    {
        $service = new MissionVolunteerBusinessService();
        $mission = $this->buildMission('Ouverte', '2026-03-01', '2026-03-31');
        $mission->setRequiredSkills('secourisme, communication');
        $mission->setCriticalPeriods('matin, soir');

        $user = new User();
        $user->setSkillsProfile('informatique, design');
        $user->setAvailabilityProfile('soir, weekend');

        $result = $service->canVolunteerApply($mission, $user, new \DateTimeImmutable('2026-03-20'));

        $this->assertFalse($result);
    }

    private function buildMission(string $statut, string $dateDebut, string $dateFin): MissionVolunteer
    {
        $mission = new MissionVolunteer();
        $mission->setStatut($statut);
        $mission->setDateDebut(new \DateTime($dateDebut));
        $mission->setDateFin(new \DateTime($dateFin));

        return $mission;
    }
}

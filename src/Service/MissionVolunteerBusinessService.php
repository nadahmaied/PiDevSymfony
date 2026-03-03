<?php

namespace App\Service;

use App\Entity\MissionVolunteer;
use App\Entity\User;

class MissionVolunteerBusinessService
{
    public function isApplicationWindowOpen(MissionVolunteer $mission, ?\DateTimeInterface $now = null): bool
    {
        $now = $now ?? new \DateTimeImmutable();

        if ($mission->getStatut() !== 'Ouverte') {
            return false;
        }

        $start = $mission->getDateDebut();
        $end = $mission->getDateFin();
        if ($start === null || $end === null) {
            return false;
        }

        $today = (new \DateTimeImmutable($now->format('Y-m-d')))->setTime(0, 0, 0);
        $startDay = (new \DateTimeImmutable($start->format('Y-m-d')))->setTime(0, 0, 0);
        $endDay = (new \DateTimeImmutable($end->format('Y-m-d')))->setTime(23, 59, 59);

        return $today >= $startDay && $today <= $endDay;
    }

    public function canVolunteerApply(MissionVolunteer $mission, User $user, ?\DateTimeInterface $now = null): bool
    {
        if (!$this->isApplicationWindowOpen($mission, $now)) {
            return false;
        }

        $requiredSkills = $mission->requiredSkillsAsArray();
        $userSkills = $user->skillsProfileAsArray();
        if ($requiredSkills !== [] && $this->hasIntersection($requiredSkills, $userSkills) === false) {
            return false;
        }

        $criticalPeriods = $mission->criticalPeriodsAsArray();
        $userAvailability = $user->availabilityProfileAsArray();
        if ($criticalPeriods !== [] && $this->hasIntersection($criticalPeriods, $userAvailability) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     */
    private function hasIntersection(array $left, array $right): bool
    {
        return array_intersect($left, $right) !== [];
    }
}

<?php

namespace App\Entity;

use App\Repository\MissionRatingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MissionRatingRepository::class)]
class MissionRating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'missionRatings')]
    private ?MissionVolunteer $mission = null;

    #[ORM\ManyToOne(inversedBy: 'missionRatings')]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMission(): ?MissionVolunteer
    {
        return $this->mission;
    }

    public function setMission(?MissionVolunteer $mission): static
    {
        $this->mission = $mission;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }
}

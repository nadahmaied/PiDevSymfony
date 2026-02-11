<?php

namespace App\Entity;

use App\Repository\MissionLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MissionLikeRepository::class)]
class MissionLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'missionLikes')]
    private ?MissionVolunteer $mission = null;

    #[ORM\ManyToOne(inversedBy: 'missionLikes')]
    private ?User $user = null;

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
}

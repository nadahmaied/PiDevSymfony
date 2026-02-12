<?php

namespace App\Entity;

use App\Repository\VolunteerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// IMPORTANT : On importe le validateur pour sécuriser les données
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VolunteerRepository::class)]
class Volunteer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Veuillez expliquer votre motivation.")]
    #[Assert\Length(
        min: 10, 
        minMessage: "Votre motivation est un peu courte (min {{ limit }} caractères)."
    )]
    private ?string $motivation = null;

    #[ORM\Column]
    #[Assert\Count(min: 1, minMessage: "Veuillez sélectionner au moins une disponibilité.")]
    private array $disponibilites = [];

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire.")]
    #[Assert\Regex(
        pattern: "/^[0-9\+\-\s]+$/",
        message: "Le format du numéro de téléphone n'est pas valide."
    )]
    private ?string $telephone = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'En attente';

    #[ORM\ManyToOne(inversedBy: 'volunteers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'volunteers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MissionVolunteer $mission = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(string $motivation): static
    {
        $this->motivation = $motivation;

        return $this;
    }

    public function getDisponibilites(): array
    {
        return $this->disponibilites;
    }

    public function setDisponibilites(array $disponibilites): static
    {
        $this->disponibilites = $disponibilites;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

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

    public function getMission(): ?MissionVolunteer
    {
        return $this->mission;
    }

    public function setMission(?MissionVolunteer $mission): static
    {
        $this->mission = $mission;

        return $this;
    }
}
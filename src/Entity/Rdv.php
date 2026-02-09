<?php

namespace App\Entity;

use App\Repository\RdvRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RdvRepository::class)]
class Rdv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $hdebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $hfin = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(length: 255)]
    private ?string $motif = null;

    #[ORM\Column(length: 255)]
    private ?string $medecin = null;

    #[ORM\Column(length: 255)]
    private ?string $message = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getHdebut(): ?\DateTime
    {
        return $this->hdebut;
    }

    public function setHdebut(\DateTime $hdebut): static
    {
        $this->hdebut = $hdebut;

        return $this;
    }

    public function getHfin(): ?\DateTime
    {
        return $this->hfin;
    }

    public function setHfin(\DateTime $hfin): static
    {
        $this->hfin = $hfin;

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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getMedecin(): ?string
    {
        return $this->medecin;
    }

    public function setMedecin(string $medecin): static
    {
        $this->medecin = $medecin;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }
}

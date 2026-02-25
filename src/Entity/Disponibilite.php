<?php

namespace App\Entity;

use App\Repository\DisponibiliteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DisponibiliteRepository::class)]
class Disponibilite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateDispo = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $hdebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $hFin = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?int $nbrH = null;

    #[ORM\Column]
    private ?int $MedId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDispo(): ?\DateTime
    {
        return $this->dateDispo;
    }

    public function setDateDispo(\DateTime $dateDispo): static
    {
        $this->dateDispo = $dateDispo;

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

    public function getHFin(): ?\DateTime
    {
        return $this->hFin;
    }

    public function setHFin(\DateTime $hFin): static
    {
        $this->hFin = $hFin;

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

    public function getNbrH(): ?int
    {
        return $this->nbrH;
    }

    public function setNbrH(int $nbrH): static
    {
        $this->nbrH = $nbrH;

        return $this;
    }

    public function getMedId(): ?int
    {
        return $this->MedId;
    }

    public function setMedId(int $MedId): static
    {
        $this->MedId = $MedId;

        return $this;
    }
}

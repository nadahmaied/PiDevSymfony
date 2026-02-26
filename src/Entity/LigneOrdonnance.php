<?php

namespace App\Entity;

use App\Repository\LigneOrdonnanceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneOrdonnanceRepository::class)]
class LigneOrdonnance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lignesOrdonnance')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ordonnance $ordonnance = null;

    #[ORM\ManyToOne(inversedBy: 'lignesOrdonnance')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Medicament $medicament = null;

    #[ORM\Column]
    private ?int $nbJours = null;

    #[ORM\Column]
    private ?int $frequenceParJour = null;

    #[ORM\Column(length: 255)]
    private ?string $momentPrise = null;

    #[ORM\Column]
    private ?bool $avantRepas = null;

    #[ORM\Column(length: 255)]
    private ?string $periode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdonnance(): ?Ordonnance
    {
        return $this->ordonnance;
    }

    public function setOrdonnance(?Ordonnance $ordonnance): static
    {
        $this->ordonnance = $ordonnance;

        return $this;
    }

    public function getMedicament(): ?Medicament
    {
        return $this->medicament;
    }

    public function setMedicament(?Medicament $medicament): static
    {
        $this->medicament = $medicament;

        return $this;
    }

    public function getNbJours(): ?int
    {
        return $this->nbJours;
    }

    public function setNbJours(int $nbJours): static
    {
        $this->nbJours = $nbJours;

        return $this;
    }

    public function getFrequenceParJour(): ?int
    {
        return $this->frequenceParJour;
    }

    public function setFrequenceParJour(int $frequenceParJour): static
    {
        $this->frequenceParJour = $frequenceParJour;

        return $this;
    }

    public function getMomentPrise(): ?string
    {
        return $this->momentPrise;
    }

    public function setMomentPrise(string $momentPrise): static
    {
        $this->momentPrise = $momentPrise;

        return $this;
    }

    public function isAvantRepas(): ?bool
    {
        return $this->avantRepas;
    }

    public function setAvantRepas(bool $avantRepas): static
    {
        $this->avantRepas = $avantRepas;

        return $this;
    }

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(string $periode): static
    {
        $this->periode = $periode;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\RdvRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: RdvRepository::class)]
class Rdv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: "La date est obligatoire")]
    #[Assert\GreaterThanOrEqual(
        value: "today",
        message: "La date ne peut pas être dans le passé"
    )]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: "L'heure de début est obligatoire")]
    private ?\DateTime $hdebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $hfin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le motif est obligatoire")]
    private ?string $motif = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le médecin est obligatoire")]
    private ?string $medecin = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(
        pattern: "/^[A-Z]/",
        message: "Le message doit commencer par une lettre majuscule"
    )]
    private ?string $message = null;

    #[ORM\ManyToOne(inversedBy: 'rdvs')]
    private ?User $patient = null;

    #[ORM\ManyToOne]
    private ?User $medecinUser = null;

    #[ORM\OneToMany(targetEntity: Ordonnance::class, mappedBy: 'idRdv')]
    private Collection $ordonnances;

    public function __construct()
    {
        $this->ordonnances = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateHdebut(ExecutionContextInterface $context): void
    {
        if ($this->hdebut === null) return;

        $totalMinutes = ((int)$this->hdebut->format('H') * 60) + (int)$this->hdebut->format('i');

        if ($totalMinutes < 540 || $totalMinutes > 1020) {
            $context->buildViolation("L'heure doit être entre 09:00 et 17:00")
                ->atPath('hdebut')
                ->addViolation();
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getDate(): ?\DateTime { return $this->date; }
    public function setDate(?\DateTime $date): static { $this->date = $date; return $this; }

    public function getHdebut(): ?\DateTime { return $this->hdebut; }
    public function setHdebut(?\DateTime $hdebut): static { $this->hdebut = $hdebut; return $this; }

    public function getHfin(): ?\DateTime { return $this->hfin; }
    public function setHfin(?\DateTime $hfin): static { $this->hfin = $hfin; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getMotif(): ?string { return $this->motif; }
    public function setMotif(?string $motif): static { $this->motif = $motif; return $this; }

    public function getMedecin(): ?string { return $this->medecin; }
    public function setMedecin(?string $medecin): static { $this->medecin = $medecin; return $this; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $message): static { $this->message = $message; return $this; }

    public function getPatient(): ?User { return $this->patient; }
    public function setPatient(?User $patient): static { $this->patient = $patient; return $this; }

    public function getMedecinUser(): ?User { return $this->medecinUser; }
    public function setMedecinUser(?User $medecinUser): static { $this->medecinUser = $medecinUser; return $this; }

    /** @return Collection<int, Ordonnance> */
    public function getOrdonnances(): Collection { return $this->ordonnances; }

    public function addOrdonnance(Ordonnance $ordonnance): static
    {
        if (!$this->ordonnances->contains($ordonnance)) {
            $this->ordonnances->add($ordonnance);
            $ordonnance->setIdRdv($this);
        }
        return $this;
    }

    public function removeOrdonnance(Ordonnance $ordonnance): static
    {
        if ($this->ordonnances->removeElement($ordonnance)) {
            if ($ordonnance->getIdRdv() === $this) {
                $ordonnance->setIdRdv(null);
            }
        }
        return $this;
    }
}

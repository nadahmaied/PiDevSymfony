<?php

namespace App\Entity;

use App\Entity\Medicament;
use App\Entity\User;
use App\Repository\OrdonnanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrdonnanceRepository::class)]
class Ordonnance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La posologie est obligatoire.')]
    private ?string $posologie = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La fréquence est obligatoire.')]
    private ?string $frequence = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La durée du traitement est obligatoire.')]
    #[Assert\Positive(message: 'La durée doit être un nombre positif.')]
    private ?int $dureeTraitement = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateOrdonnance = null;

    #[ORM\ManyToOne(inversedBy: 'ordonnances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $idU = null;

    #[ORM\ManyToOne(inversedBy: 'ordonnances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Rdv $idRdv = null;

    /** @var Collection<int, LigneOrdonnance> */
    #[ORM\OneToMany(targetEntity: LigneOrdonnance::class, mappedBy: 'ordonnance', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignesOrdonnance;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $scanToken = null;

    public function __construct()
    {
        $this->lignesOrdonnance = new ArrayCollection();
        $this->dateOrdonnance = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPosologie(): ?string
    {
        return $this->posologie;
    }

    public function setPosologie(?string $posologie): static
    {
        $this->posologie = $posologie;

        return $this;
    }

    public function getFrequence(): ?string
    {
        return $this->frequence;
    }

    public function setFrequence(?string $frequence): static
    {
        $this->frequence = $frequence;

        return $this;
    }

    public function getDureeTraitement(): ?int
    {
        return $this->dureeTraitement;
    }

    public function setDureeTraitement(?int $dureeTraitement): static
    {
        $this->dureeTraitement = $dureeTraitement;

        return $this;
    }

    public function getDateOrdonnance(): ?\DateTimeInterface
    {
        return $this->dateOrdonnance;
    }

    public function setDateOrdonnance(?\DateTimeInterface $dateOrdonnance): static
    {
        $this->dateOrdonnance = $dateOrdonnance;

        return $this;
    }

    public function getIdU(): ?User
    {
        return $this->idU;
    }

    public function setIdU(?User $idU): static
    {
        $this->idU = $idU;

        return $this;
    }

    public function getIdRdv(): ?Rdv
    {
        return $this->idRdv;
    }

    public function setIdRdv(?Rdv $idRdv): static
    {
        $this->idRdv = $idRdv;

        return $this;
    }

    /**
     * @return Collection<int, LigneOrdonnance>
     */
    public function getLignesOrdonnance(): Collection
    {
        return $this->lignesOrdonnance;
    }

    public function addLignesOrdonnance(LigneOrdonnance $ligneOrdonnance): static
    {
        if (!$this->lignesOrdonnance->contains($ligneOrdonnance)) {
            $this->lignesOrdonnance->add($ligneOrdonnance);
            $ligneOrdonnance->setOrdonnance($this);
        }

        return $this;
    }

    public function removeLignesOrdonnance(LigneOrdonnance $ligneOrdonnance): static
    {
        if ($this->lignesOrdonnance->removeElement($ligneOrdonnance)) {
            if ($ligneOrdonnance->getOrdonnance() === $this) {
                $ligneOrdonnance->setOrdonnance(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Medicament>
     */
    public function getMedicaments(): Collection
    {
        $medicaments = new ArrayCollection();
        $seen = [];

        foreach ($this->lignesOrdonnance as $ligneOrdonnance) {
            $medicament = $ligneOrdonnance->getMedicament();
            if ($medicament === null) {
                continue;
            }
            $objectId = spl_object_id($medicament);
            if (isset($seen[$objectId])) {
                continue;
            }
            $seen[$objectId] = true;
            $medicaments->add($medicament);
        }

        return $medicaments;
    }

    public function addMedicament(Medicament $medicament): static
    {
        foreach ($this->lignesOrdonnance as $ligneOrdonnance) {
            if ($ligneOrdonnance->getMedicament() === $medicament) {
                return $this;
            }
        }

        $ligneOrdonnance = new LigneOrdonnance();
        $ligneOrdonnance->setMedicament($medicament);
        $ligneOrdonnance->setNbJours($this->dureeTraitement ?? 1);
        $ligneOrdonnance->setFrequenceParJour(1);
        $ligneOrdonnance->setMomentPrise('Matin');
        $ligneOrdonnance->setAvantRepas(false);
        $ligneOrdonnance->setPeriode($this->frequence ?? 'Quotidien');
        $this->addLignesOrdonnance($ligneOrdonnance);

        return $this;
    }

    public function removeMedicament(Medicament $medicament): static
    {
        foreach ($this->lignesOrdonnance as $ligneOrdonnance) {
            if ($ligneOrdonnance->getMedicament() === $medicament) {
                $this->removeLignesOrdonnance($ligneOrdonnance);
            }
        }

        return $this;
    }

    public function getScanToken(): ?string
    {
        return $this->scanToken;
    }

    public function setScanToken(?string $scanToken): static
    {
        $this->scanToken = $scanToken;

        return $this;
    }

}






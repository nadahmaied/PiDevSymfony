<?php

namespace App\Entity;

use App\Repository\MedicamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MedicamentRepository::class)]
class Medicament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du médicament est obligatoire.')]
    #[Assert\Length(min: 3, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.')]
    private ?string $nomMedicament = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
    private ?string $categorie = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le dosage est obligatoire.')]
    private ?string $dosage = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'La forme est obligatoire.')]
    private ?string $forme = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date d\'expiration est obligatoire.')]
    private ?\DateTimeInterface $dateExpiration = null;

    /** @var Collection<int, LigneOrdonnance> */
    #[ORM\OneToMany(targetEntity: LigneOrdonnance::class, mappedBy: 'medicament')]
    private Collection $lignesOrdonnance;

    public function __construct()
    {
        $this->lignesOrdonnance = new ArrayCollection();
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

    public function getNomMedicament(): ?string
    {
        return $this->nomMedicament;
    }

    public function setNomMedicament(?string $nomMedicament): static
    {
        $this->nomMedicament = $nomMedicament;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getDosage(): ?string
    {
        return $this->dosage;
    }

    public function setDosage(?string $dosage): static
    {
        $this->dosage = $dosage;

        return $this;
    }

    public function getForme(): ?string
    {
        return $this->forme;
    }

    public function setForme(?string $forme): static
    {
        $this->forme = $forme;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?\DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;

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
            $ligneOrdonnance->setMedicament($this);
        }

        return $this;
    }

    public function removeLignesOrdonnance(LigneOrdonnance $ligneOrdonnance): static
    {
        if ($this->lignesOrdonnance->removeElement($ligneOrdonnance)) {
            if ($ligneOrdonnance->getMedicament() === $this) {
                $ligneOrdonnance->setMedicament(null);
            }
        }

        return $this;
    }

}






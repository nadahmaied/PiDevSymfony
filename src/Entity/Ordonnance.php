<?php

namespace App\Entity;

use App\Entity\Fiche;
use App\Entity\Medicament;
use App\Entity\User;
use App\Repository\OrdonnanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\ManyToOne(inversedBy: 'ordonnances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $idU = null;

    #[ORM\ManyToOne(inversedBy: 'ordonnances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Fiche $id_fiche = null;

    #[ORM\ManyToMany(targetEntity: Medicament::class, inversedBy: 'ordonnances')]
    #[ORM\JoinTable(name: 'ord_med')]
    #[ORM\JoinColumn(name: "id_ord", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "id_medicament", referencedColumnName: "id")]
    private Collection $medicaments;

    public function __construct()
    {
        $this->medicaments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIdU(): ?User
    {
        return $this->idU;
    }

    public function setIdU(?User $idU): static
    {
        $this->idU = $idU;

        return $this;
    }

    public function getIdFiche(): ?Fiche
    {
        return $this->id_fiche;
    }

    public function setIdFiche(?Fiche $id_fiche): static
    {
        $this->id_fiche = $id_fiche;

        return $this;
    }

    /**
     * @return Collection<int, Medicament>
     */
    public function getMedicaments(): Collection
    {
        return $this->medicaments;
    }

    public function addMedicament(Medicament $medicament): static
    {
        if (!$this->medicaments->contains($medicament)) {
            $this->medicaments->add($medicament);
        }

        return $this;
    }

    public function removeMedicament(Medicament $medicament): static
    {
        $this->medicaments->removeElement($medicament);

        return $this;
    }
}

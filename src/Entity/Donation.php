<?php

namespace App\Entity;

use App\Repository\DonationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DonationRepository::class)]
class Donation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de don est obligatoire.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le type de don ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $typeDon = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être un nombre positif.')]
    private ?int $quantite = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date du don est obligatoire.')]
    private ?\DateTimeInterface $dateDonation = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(
        choices: ['en attente', 'accepté', 'refusé'],
        message: 'Le statut doit être en attente, accepté ou refusé.'
    )]
    private ?string $statut = null;

    #[ORM\OneToOne(mappedBy: 'donation', cascade: ['persist', 'remove'])]
    private ?Annonce $annonce = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeDon(): ?string
    {
        return $this->typeDon;
    }

    public function setTypeDon(string $typeDon): static
    {
        $this->typeDon = $typeDon;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getDateDonation(): ?\DateTimeInterface
    {
        return $this->dateDonation;
    }

    public function setDateDonation(?\DateTimeInterface $dateDonation): static
    {
        $this->dateDonation = $dateDonation;

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

    public function getAnnonce(): ?Annonce
    {
        return $this->annonce;
    }

    public function setAnnonce(Annonce $annonce): static
    {
        // set the owning side of the relation if necessary
        if ($annonce->getDonation() !== $this) {
            $annonce->setDonation($this);
        }

        $this->annonce = $annonce;

        return $this;
    }
}

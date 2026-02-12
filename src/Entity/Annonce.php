<?php

namespace App\Entity;

use App\Repository\AnnonceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnnonceRepository::class)]
class Annonce
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 150,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $titreAnnonce = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de publication est obligatoire.')]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le niveau d\'urgence est obligatoire.')]
    #[Assert\Choice(
        choices: ['faible', 'moyenne', 'élevée'],
        message: 'Le niveau d\'urgence doit être faible, moyenne ou élevée.'
    )]
    private ?string $urgence = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'L\'état de l\'annonce est obligatoire.')]
    #[Assert\Choice(
        choices: ['active', 'clôturée'],
        message: 'L\'état de l\'annonce doit être active ou clôturée.'
    )]
    private ?string $etatAnnonce = null;

    #[ORM\OneToOne(inversedBy: 'annonce', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: True)]
    private ?Donation $donation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitreAnnonce(): ?string
    {
        return $this->titreAnnonce;
    }

    public function setTitreAnnonce(string $titreAnnonce): static
    {
        $this->titreAnnonce = $titreAnnonce;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;

        return $this;
    }

    public function getUrgence(): ?string
    {
        return $this->urgence;
    }

    public function setUrgence(string $urgence): static
    {
        $this->urgence = $urgence;

        return $this;
    }

    public function getEtatAnnonce(): ?string
    {
        return $this->etatAnnonce;
    }

    public function setEtatAnnonce(string $etatAnnonce): static
    {
        $this->etatAnnonce = $etatAnnonce;

        return $this;
    }

    public function getDonation(): ?Donation
    {
        return $this->donation;
    }

    public function setDonation(Donation $donation): static
    {
        $this->donation = $donation;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\FicheRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FicheRepository::class)]
class Fiche
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le poids est obligatoire.')]
    #[Assert\Positive(message: 'Le poids doit être un nombre positif.')]
    private ?float $poids = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La taille est obligatoire.')]
    #[Assert\Positive(message: 'La taille doit être un nombre positif.')]
    private ?float $taille = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Le groupe sanguin est obligatoire.')]
    #[Assert\Choice(choices: ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], message: 'Choisissez un groupe sanguin valide.')]
    private ?string $grpSanguin = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $allergie = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $maladieChronique = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'La tension est obligatoire.')]
    private ?string $tension = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La glycémie est obligatoire.')]
    #[Assert\Positive(message: 'La glycémie doit être positive.')]
    private ?float $glycemie = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le libellé de la maladie est obligatoire.')]
    private ?string $libelleMaladie = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $gravite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $recommandation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $symptomes = null;

    #[ORM\OneToOne(inversedBy: 'fiche')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $idU = null;

    public function __construct()
    {
        $this->date = new \DateTime();
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

    public function getPoids(): ?float
    {
        return $this->poids;
    }

    public function setPoids(?float $poids): static
    {
        $this->poids = $poids;

        return $this;
    }

    public function getTaille(): ?float
    {
        return $this->taille;
    }

    public function setTaille(?float $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    public function getGrpSanguin(): ?string
    {
        return $this->grpSanguin;
    }

    public function setGrpSanguin(?string $grpSanguin): static
    {
        $this->grpSanguin = $grpSanguin;

        return $this;
    }

    public function getAllergie(): ?string
    {
        return $this->allergie;
    }

    public function setAllergie(?string $allergie): static
    {
        $this->allergie = $allergie;

        return $this;
    }

    public function getMaladieChronique(): ?string
    {
        return $this->maladieChronique;
    }

    public function setMaladieChronique(?string $maladieChronique): static
    {
        $this->maladieChronique = $maladieChronique;

        return $this;
    }

    public function getTension(): ?string
    {
        return $this->tension;
    }

    public function setTension(?string $tension): static
    {
        $this->tension = $tension;

        return $this;
    }

    public function getGlycemie(): ?float
    {
        return $this->glycemie;
    }

    public function setGlycemie(?float $glycemie): static
    {
        $this->glycemie = $glycemie;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getLibelleMaladie(): ?string
    {
        return $this->libelleMaladie;
    }

    public function setLibelleMaladie(?string $libelleMaladie): static
    {
        $this->libelleMaladie = $libelleMaladie;

        return $this;
    }

    public function getGravite(): ?string
    {
        return $this->gravite;
    }

    public function setGravite(?string $gravite): static
    {
        $this->gravite = $gravite;

        return $this;
    }

    public function getRecommandation(): ?string
    {
        return $this->recommandation;
    }

    public function setRecommandation(?string $recommandation): static
    {
        $this->recommandation = $recommandation;

        return $this;
    }

    public function getSymptomes(): ?string
    {
        return $this->symptomes;
    }

    public function setSymptomes(?string $symptomes): static
    {
        $this->symptomes = $symptomes;

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

}






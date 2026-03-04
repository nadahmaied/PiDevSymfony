<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        min: 5, 
        max: 150, 
        minMessage: "Le titre doit faire au moins {{ limit }} caractères.",
        maxMessage: "Le titre ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Veuillez décrire votre question.")]
    #[Assert\Length(
        min: 20, 
        minMessage: "Votre description est trop courte, donnez plus de détails (min {{ limit }} caractères)."
    )]
    private ?string $contenu = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $auteur = null;

    #[ORM\Column(length: 20)]
    private string $moderationStatus = 'safe';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moderationReason = null;

    #[ORM\Column(nullable: true)]
    private ?float $toxicityScore = null;

    #[ORM\Column(nullable: true)]
    private ?float $sensitiveScore = null;

    #[ORM\Column(nullable: true)]
    private ?float $medicalRiskScore = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $flaggedAt = null;

    #[ORM\ManyToOne]
    private ?User $reviewedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    /**
     * @var Collection<int, Reponse>
     */
    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: 'question', orphanRemoval: true)]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setQuestion($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            // set the owning side to null (unless already changed)
            if ($reponse->getQuestion() === $this) {
                $reponse->setQuestion(null);
            }
        }

        return $this;
    }

    public function getModerationStatus(): string
    {
        return $this->moderationStatus;
    }

    public function setModerationStatus(string $moderationStatus): static
    {
        $this->moderationStatus = $moderationStatus;

        return $this;
    }

    public function getModerationReason(): ?string
    {
        return $this->moderationReason;
    }

    public function setModerationReason(?string $moderationReason): static
    {
        $this->moderationReason = $moderationReason;

        return $this;
    }

    public function getToxicityScore(): ?float
    {
        return $this->toxicityScore;
    }

    public function setToxicityScore(?float $toxicityScore): static
    {
        $this->toxicityScore = $toxicityScore;

        return $this;
    }

    public function getSensitiveScore(): ?float
    {
        return $this->sensitiveScore;
    }

    public function setSensitiveScore(?float $sensitiveScore): static
    {
        $this->sensitiveScore = $sensitiveScore;

        return $this;
    }

    public function getMedicalRiskScore(): ?float
    {
        return $this->medicalRiskScore;
    }

    public function setMedicalRiskScore(?float $medicalRiskScore): static
    {
        $this->medicalRiskScore = $medicalRiskScore;

        return $this;
    }

    public function getFlaggedAt(): ?\DateTimeImmutable
    {
        return $this->flaggedAt;
    }

    public function setFlaggedAt(?\DateTimeImmutable $flaggedAt): static
    {
        $this->flaggedAt = $flaggedAt;

        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;

        return $this;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;

        return $this;
    }

}






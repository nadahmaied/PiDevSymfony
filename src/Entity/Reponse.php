<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Une réponse vide n'est pas autorisée.")]
    #[Assert\Length(
        min: 5, 
        minMessage: "Votre réponse est trop courte (min {{ limit }} caractères)."
    )]
    private ?string $contenu = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $auteur = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Question $question = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

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






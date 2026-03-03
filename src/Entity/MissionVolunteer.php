<?php

namespace App\Entity;

use App\Repository\MissionVolunteerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MissionVolunteerRepository::class)]
class MissionVolunteer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        min: 5, 
        max: 50, 
        minMessage: "Le titre doit faire au moins {{ limit }} caractères.",
        maxMessage: "Le titre est trop long (max {{ limit }} caractères)."
    )]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(
        min: 20, 
        minMessage: "Veuillez écrire une description plus détaillée (min {{ limit }} caractères)."
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    #[Assert\Length(
        min: 4, 
        minMessage: "Le lieu doit contenir au moins {{ limit }} caractères (ex: Tunis)."
    )]
    private ?string $lieu = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: "La date de début est requise.")]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: "La date de fin est requise.")]
    #[Assert\GreaterThan(propertyPath: "dateDebut", message: "La date de fin doit être après la date de début.")]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(length: 50)]
    private string $statut = 'Ouverte'; // Valeur par défaut recommandée

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $requiredSkills = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $thematicTags = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $criticalPeriods = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $targetAudience = null;

    #[ORM\Column(nullable: true)]
    private ?int $difficultyLevel = null;

    #[ORM\Column(nullable: true)]
    private ?int $urgencyLevel = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    // --- RELATION 1 : Liste des candidatures (Bénévoles) ---
    // Corrigé en OneToMany pour correspondre à votre entité Volunteer
    /** @var Collection<int, Volunteer> */
    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: Volunteer::class, orphanRemoval: true)]
    private Collection $volunteers;

    // --- RELATION 2 : L'Admin créateur de la mission ---
    #[ORM\ManyToOne(inversedBy: 'missions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    /**
     * @var Collection<int, Sponsor>
     */
    #[ORM\ManyToMany(targetEntity: Sponsor::class, mappedBy: 'missions')]
    private Collection $sponsors;

    /**
     * @var Collection<int, MissionLike>
     */
    #[ORM\OneToMany(targetEntity: MissionLike::class, mappedBy: 'mission')]
    private Collection $missionLikes;

    /**
     * @var Collection<int, MissionRating>
     */
    #[ORM\OneToMany(targetEntity: MissionRating::class, mappedBy: 'mission')]
    private Collection $missionRatings;

    public function __construct()
    {
        $this->volunteers = new ArrayCollection();
        $this->sponsors = new ArrayCollection();
        $this->missionLikes = new ArrayCollection();
        $this->missionRatings = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getRequiredSkills(): ?string
    {
        return $this->requiredSkills;
    }

    public function setRequiredSkills(?string $requiredSkills): static
    {
        $this->requiredSkills = $requiredSkills;

        return $this;
    }

    public function getThematicTags(): ?string
    {
        return $this->thematicTags;
    }

    public function setThematicTags(?string $thematicTags): static
    {
        $this->thematicTags = $thematicTags;

        return $this;
    }

    public function getCriticalPeriods(): ?string
    {
        return $this->criticalPeriods;
    }

    public function setCriticalPeriods(?string $criticalPeriods): static
    {
        $this->criticalPeriods = $criticalPeriods;

        return $this;
    }

    public function getTargetAudience(): ?string
    {
        return $this->targetAudience;
    }

    public function setTargetAudience(?string $targetAudience): static
    {
        $this->targetAudience = $targetAudience;

        return $this;
    }

    public function getDifficultyLevel(): ?int
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(?int $difficultyLevel): static
    {
        $this->difficultyLevel = $difficultyLevel;

        return $this;
    }

    public function getUrgencyLevel(): ?int
    {
        return $this->urgencyLevel;
    }

    public function setUrgencyLevel(?int $urgencyLevel): static
    {
        $this->urgencyLevel = $urgencyLevel;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    /** @return list<string> */
    public function requiredSkillsAsArray(): array
    {
        return self::csvToArray($this->requiredSkills);
    }

    /** @return list<string> */
    public function thematicTagsAsArray(): array
    {
        return self::csvToArray($this->thematicTags);
    }

    /** @return list<string> */
    public function criticalPeriodsAsArray(): array
    {
        return self::csvToArray($this->criticalPeriods);
    }

    // --- GESTION DES BÉNÉVOLES (Candidatures) ---

    /**
     * @return Collection<int, Volunteer>
     */
    public function getVolunteers(): Collection
    {
        return $this->volunteers;
    }

    public function addVolunteer(Volunteer $volunteer): static
    {
        if (!$this->volunteers->contains($volunteer)) {
            $this->volunteers->add($volunteer);
            $volunteer->setMission($this);
        }
        return $this;
    }

    public function removeVolunteer(Volunteer $volunteer): static
    {
        if ($this->volunteers->removeElement($volunteer)) {
            // set the owning side to null (unless already changed)
            if ($volunteer->getMission() === $this) {
                $volunteer->setMission(null);
            }
        }
        return $this;
    }

    // --- GESTION DE L'ADMIN (User) ---

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, Sponsor>
     */
    public function getSponsors(): Collection
    {
        return $this->sponsors;
    }

    public function addSponsor(Sponsor $sponsor): static
    {
        if (!$this->sponsors->contains($sponsor)) {
            $this->sponsors->add($sponsor);
            $sponsor->addMission($this);
        }

        return $this;
    }

    public function removeSponsor(Sponsor $sponsor): static
    {
        if ($this->sponsors->removeElement($sponsor)) {
            $sponsor->removeMission($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, MissionLike>
     */
    public function getMissionLikes(): Collection
    {
        return $this->missionLikes;
    }

    public function addMissionLike(MissionLike $missionLike): static
    {
        if (!$this->missionLikes->contains($missionLike)) {
            $this->missionLikes->add($missionLike);
            $missionLike->setMission($this);
        }

        return $this;
    }

    public function removeMissionLike(MissionLike $missionLike): static
    {
        if ($this->missionLikes->removeElement($missionLike)) {
            // set the owning side to null (unless already changed)
            if ($missionLike->getMission() === $this) {
                $missionLike->setMission(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MissionRating>
     */
    public function getMissionRatings(): Collection
    {
        return $this->missionRatings;
    }

    public function addMissionRating(MissionRating $missionRating): static
    {
        if (!$this->missionRatings->contains($missionRating)) {
            $this->missionRatings->add($missionRating);
            $missionRating->setMission($this);
        }

        return $this;
    }

    public function removeMissionRating(MissionRating $missionRating): static
    {
        if ($this->missionRatings->removeElement($missionRating)) {
            // set the owning side to null (unless already changed)
            if ($missionRating->getMission() === $this) {
                $missionRating->setMission(null);
            }
        }

        return $this;
    }

    /** @return list<string> */
    private static function csvToArray(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $parts = array_map('trim', explode(',', mb_strtolower($value)));
        $parts = array_filter($parts, static fn (string $part): bool => $part !== '');

        return array_values(array_unique($parts));
    }


}






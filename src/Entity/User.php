<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'Veuillez saisir une adresse email valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\Length(min: 6, max: 4096, groups: ['registration'])]
    private ?string $plainPassword = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^\D+$/u',
        message: 'Le nom ne doit pas contenir de chiffres.'
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^\D+$/u',
        message: 'Le prénom ne doit pas contenir de chiffres.'
    )]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\d+$/',
        message: 'Le numéro de téléphone doit contenir uniquement des chiffres.',
        groups: ['registration']
    )]
    private ?string $telephone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $skillsProfile = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $interestsProfile = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $availabilityProfile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $preferredCity = null;

    #[ORM\Column(nullable: true)]
    private ?int $actionRadiusKm = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'json')]
    private array $recommendationWeights = [];

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    /**
     * @var Collection<int, Question>
     */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'auteur')]
    private Collection $questions;

    /**
     * @var Collection<int, Reponse>
     */
    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: 'auteur')]
    private Collection $reponses;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->recommendationWeights = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getSkillsProfile(): ?string
    {
        return $this->skillsProfile;
    }

    public function setSkillsProfile(?string $skillsProfile): static
    {
        $this->skillsProfile = $skillsProfile;

        return $this;
    }

    public function getInterestsProfile(): ?string
    {
        return $this->interestsProfile;
    }

    public function setInterestsProfile(?string $interestsProfile): static
    {
        $this->interestsProfile = $interestsProfile;

        return $this;
    }

    public function getAvailabilityProfile(): ?string
    {
        return $this->availabilityProfile;
    }

    public function setAvailabilityProfile(?string $availabilityProfile): static
    {
        $this->availabilityProfile = $availabilityProfile;

        return $this;
    }

    public function getPreferredCity(): ?string
    {
        return $this->preferredCity;
    }

    public function setPreferredCity(?string $preferredCity): static
    {
        $this->preferredCity = $preferredCity;

        return $this;
    }

    public function getActionRadiusKm(): ?int
    {
        return $this->actionRadiusKm;
    }

    public function setActionRadiusKm(?int $actionRadiusKm): static
    {
        $this->actionRadiusKm = $actionRadiusKm;

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

    public function getRecommendationWeights(): array
    {
        if (!isset($this->recommendationWeights) || !is_array($this->recommendationWeights)) {
            return [];
        }

        return $this->recommendationWeights;
    }

    public function setRecommendationWeights(array $recommendationWeights): static
    {
        $this->recommendationWeights = $recommendationWeights;

        return $this;
    }

    public function skillsProfileAsArray(): array
    {
        return self::csvToArray($this->skillsProfile);
    }

    public function interestsProfileAsArray(): array
    {
        return self::csvToArray($this->interestsProfile);
    }

    public function availabilityProfileAsArray(): array
    {
        return self::csvToArray($this->availabilityProfile);
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setAuteur($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getAuteur() === $this) {
                $question->setAuteur(null);
            }
        }

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
            $reponse->setAuteur($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            // set the owning side to null (unless already changed)
            if ($reponse->getAuteur() === $this) {
                $reponse->setAuteur(null);
            }
        }

        return $this;
    }
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = [];

        if ($this->role) {
            $roles[] = $this->role;
        }

        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return $roles;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

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

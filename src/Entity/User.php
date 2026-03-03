<?php

namespace App\Entity;

use App\Entity\Fiche;
use App\Entity\Ordonnance;
use App\Entity\Rdv;
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

    /** @var array<string, float> */
    #[ORM\Column(type: 'json')]
    private array $recommendationWeights = [];

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diplomaDocument = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idCardDocument = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verificationStatus = null;

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

    #[ORM\OneToOne(mappedBy: 'idU', cascade: ['persist', 'remove'])]
    private ?Fiche $fiche = null;

    /**
     * @var Collection<int, Ordonnance>
     */
    #[ORM\OneToMany(targetEntity: Ordonnance::class, mappedBy: 'idU')]
    private Collection $ordonnances;

    /**
     * @var Collection<int, Rdv>
     */
    #[ORM\OneToMany(targetEntity: Rdv::class, mappedBy: 'patient')]
    private Collection $rdvs;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->ordonnances = new ArrayCollection();
        $this->rdvs = new ArrayCollection();
        $this->recommendationWeights = [];
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

    /** @return array<string, float> */
    public function getRecommendationWeights(): array
    {
        return $this->recommendationWeights;
    }

    /** @param array<string, float|int> $recommendationWeights */
    public function setRecommendationWeights(array $recommendationWeights): static
    {
        $normalized = [];
        foreach ($recommendationWeights as $key => $weight) {
            $normalized[(string) $key] = (float) $weight;
        }
        $this->recommendationWeights = $normalized;

        return $this;
    }

    /** @return list<string> */
    public function skillsProfileAsArray(): array
    {
        return self::csvToArray($this->skillsProfile);
    }

    /** @return list<string> */
    public function interestsProfileAsArray(): array
    {
        return self::csvToArray($this->interestsProfile);
    }

    /** @return list<string> */
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

        // ROLE_SUPER_ADMIN inherits ROLE_ADMIN permissions.
        if (in_array('ROLE_SUPER_ADMIN', $roles, true) && !in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
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

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    public function getDiplomaDocument(): ?string
    {
        return $this->diplomaDocument;
    }

    public function setDiplomaDocument(?string $diplomaDocument): static
    {
        $this->diplomaDocument = $diplomaDocument;

        return $this;
    }

    public function getIdCardDocument(): ?string
    {
        return $this->idCardDocument;
    }

    public function setIdCardDocument(?string $idCardDocument): static
    {
        $this->idCardDocument = $idCardDocument;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getVerificationStatus(): ?string
    {
        return $this->verificationStatus;
    }

    public function setVerificationStatus(?string $verificationStatus): static
    {
        $this->verificationStatus = $verificationStatus;

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

    public function getFiche(): ?Fiche
    {
        return $this->fiche;
    }

    public function setFiche(?Fiche $fiche): static
    {
        if ($fiche === null && $this->fiche !== null) {
            $this->fiche->setIdU(null);
        }

        if ($fiche !== null && $fiche->getIdU() !== $this) {
            $fiche->setIdU($this);
        }

        $this->fiche = $fiche;

        return $this;
    }

    /**
     * @return Collection<int, Ordonnance>
     */
    public function getOrdonnances(): Collection
    {
        return $this->ordonnances;
    }

    public function addOrdonnance(Ordonnance $ordonnance): static
    {
        if (!$this->ordonnances->contains($ordonnance)) {
            $this->ordonnances->add($ordonnance);
            $ordonnance->setIdU($this);
        }

        return $this;
    }

    public function removeOrdonnance(Ordonnance $ordonnance): static
    {
        if ($this->ordonnances->removeElement($ordonnance)) {
            if ($ordonnance->getIdU() === $this) {
                $ordonnance->setIdU(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rdv>
     */
    public function getRdvs(): Collection
    {
        return $this->rdvs;
    }

    public function addRdv(Rdv $rdv): static
    {
        if (!$this->rdvs->contains($rdv)) {
            $this->rdvs->add($rdv);
            $rdv->setPatient($this);
        }

        return $this;
    }

    public function removeRdv(Rdv $rdv): static
    {
        if ($this->rdvs->removeElement($rdv)) {
            if ($rdv->getPatient() === $this) {
                $rdv->setPatient(null);
            }
        }

        return $this;
    }

}






<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    // --- RELATION 1: Liste des candidatures bénévoles de cet utilisateur ---
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Volunteer::class)]
    private Collection $volunteers;

    // --- RELATION 2: Liste des missions créées par cet admin ---
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: MissionVolunteer::class)]
    private Collection $missions;

    /**
     * @var Collection<int, MissionLike>
     */
    #[ORM\OneToMany(targetEntity: MissionLike::class, mappedBy: 'user')]
    private Collection $missionLikes;

    /**
     * @var Collection<int, MissionRating>
     */
    #[ORM\OneToMany(targetEntity: MissionRating::class, mappedBy: 'user')]
    private Collection $missionRatings;

    public function __construct()
    {
        $this->volunteers = new ArrayCollection();
        $this->missions = new ArrayCollection();
        $this->missionLikes = new ArrayCollection();
        $this->missionRatings = new ArrayCollection();
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
            $volunteer->setUser($this);
        }
        return $this;
    }

    public function removeVolunteer(Volunteer $volunteer): static
    {
        if ($this->volunteers->removeElement($volunteer)) {
            // Si l'utilisateur était lié, on le détache
            if ($volunteer->getUser() === $this) {
                $volunteer->setUser(null);
            }
        }
        return $this;
    }

    // --- GESTION DES MISSIONS (Créées par l'Admin) ---

    /**
     * @return Collection<int, MissionVolunteer>
     */
    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(MissionVolunteer $mission): static
    {
        if (!$this->missions->contains($mission)) {
            $this->missions->add($mission);
            $mission->setUser($this);
        }
        return $this;
    }

    public function removeMission(MissionVolunteer $mission): static
    {
        if ($this->missions->removeElement($mission)) {
            // Si la mission était liée à cet admin, on la détache
            if ($mission->getUser() === $this) {
                $mission->setUser(null);
            }
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
            $missionLike->setUser($this);
        }

        return $this;
    }

    public function removeMissionLike(MissionLike $missionLike): static
    {
        if ($this->missionLikes->removeElement($missionLike)) {
            // set the owning side to null (unless already changed)
            if ($missionLike->getUser() === $this) {
                $missionLike->setUser(null);
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
            $missionRating->setUser($this);
        }

        return $this;
    }

    public function removeMissionRating(MissionRating $missionRating): static
    {
        if ($this->missionRatings->removeElement($missionRating)) {
            // set the owning side to null (unless already changed)
            if ($missionRating->getUser() === $this) {
                $missionRating->setUser(null);
            }
        }

        return $this;
    }
}
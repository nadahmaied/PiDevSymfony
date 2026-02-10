<?php

namespace App\Entity;

use App\Repository\SponsorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
class Sponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de la société est obligatoire.")]
    #[Assert\Length(
        min: 3, 
        minMessage: "Le nom de la société doit faire au moins {{ limit }} caractères."
    )]
    private ?string $nomSociete = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide.")]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    /**
     * @var Collection<int, MissionVolunteer>
     */
    #[ORM\ManyToMany(targetEntity: MissionVolunteer::class, inversedBy: 'sponsors')]
    #[Assert\Count(
        min: 1,
        minMessage: "Veuillez sélectionner au moins une mission à sponsoriser."
    )]
    private Collection $missions;

    public function __construct()
    {
        $this->missions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomSociete(): ?string
    {
        return $this->nomSociete;
    }

    public function setNomSociete(string $nomSociete): static
    {
        $this->nomSociete = $nomSociete;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

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
        }

        return $this;
    }

    public function removeMission(MissionVolunteer $mission): static
    {
        $this->missions->removeElement($mission);

        return $this;
    }
}

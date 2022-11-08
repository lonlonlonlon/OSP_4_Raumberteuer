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

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    private ?string $passwordHash = null;

    #[ORM\OneToMany(mappedBy: 'Supervisor', targetEntity: Room::class)]
    private Collection $rooms;

    #[ORM\OneToMany(mappedBy: 'reportedBy', targetEntity: ErrorReport::class)]
    private Collection $errorReports;

    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->errorReports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    /**
     * @return Collection<int, Room>
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Room $room): self
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms->add($room);
            $room->setSupervisor($this);
        }

        return $this;
    }

    public function removeRoom(Room $room): self
    {
        if ($this->rooms->removeElement($room)) {
            // set the owning side to null (unless already changed)
            if ($room->getSupervisor() === $this) {
                $room->setSupervisor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ErrorReport>
     */
    public function getErrorReports(): Collection
    {
        return $this->errorReports;
    }

    public function addErrorReport(ErrorReport $errorReport): self
    {
        if (!$this->errorReports->contains($errorReport)) {
            $this->errorReports->add($errorReport);
            $errorReport->setReportedBy($this);
        }

        return $this;
    }

    public function removeErrorReport(ErrorReport $errorReport): self
    {
        if ($this->errorReports->removeElement($errorReport)) {
            // set the owning side to null (unless already changed)
            if ($errorReport->getReportedBy() === $this) {
                $errorReport->setReportedBy(null);
            }
        }

        return $this;
    }
}

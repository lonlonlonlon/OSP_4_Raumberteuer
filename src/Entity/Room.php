<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'rooms')]
    private ?User $Supervisor = null;

    #[ORM\Column(length: 2)]
    private ?string $tract = null;

    #[ORM\Column]
    private ?int $roomNumber = null;

    #[ORM\OneToMany(mappedBy: 'reportedRoom', targetEntity: ErrorReport::class)]
    private Collection $errorReports;

    #[ORM\Column]
    private ?int $roomType = null;

    public function __construct()
    {
        $this->errorReports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSupervisor(): ?User
    {
        return $this->Supervisor;
    }

    public function setSupervisor(?User $Supervisor): self
    {
        $this->Supervisor = $Supervisor;

        return $this;
    }

    public function getTract(): ?string
    {
        return $this->tract;
    }

    public function setTract(string $tract): self
    {
        $this->tract = $tract;

        return $this;
    }

    public function getRoomNumber(): ?int
    {
        return $this->roomNumber;
    }

    public function setRoomNumber(int $roomNumber): self
    {
        $this->roomNumber = $roomNumber;

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
            $errorReport->setReportedRoom($this);
        }

        return $this;
    }

    public function removeErrorReport(ErrorReport $errorReport): self
    {
        if ($this->errorReports->removeElement($errorReport)) {
            // set the owning side to null (unless already changed)
            if ($errorReport->getReportedRoom() === $this) {
                $errorReport->setReportedRoom(null);
            }
        }

        return $this;
    }

    public function getRoomType(): ?int
    {
        return $this->roomType;
    }

    public function setRoomType(int $roomType): self
    {
        $this->roomType = $roomType;

        return $this;
    }
}

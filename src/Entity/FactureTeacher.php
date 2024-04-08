<?php

namespace App\Entity;

use App\Repository\FactureTeacherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FactureTeacherRepository::class)]
class FactureTeacher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $Amount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $DatePay = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAt = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'factureTeachers')]
    private ?Teacher $teacher = null;

    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'factureTeacher')]
    private Collection $session;


    public function __construct()
    {
        $this->session = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?int
    {
        return $this->Amount;
    }

    public function setAmount(int $Amount): static
    {
        $this->Amount = $Amount;

        return $this;
    }

    public function getDatePay(): ?\DateTimeInterface
    {
        return $this->DatePay;
    }

    public function setDatePay(\DateTimeInterface $DatePay): static
    {
        $this->DatePay = $DatePay;

        return $this;
    }

    public function getDateAt(): ?\DateTimeInterface
    {
        return $this->dateAt;
    }

    public function setDateAt(\DateTimeInterface $dateAt): static
    {
        $this->dateAt = $dateAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(?Teacher $teacher): static
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * @return Collection<int, Session>
     */
    public function getSession(): Collection
    {
        return $this->session;
    }

    public function addSession(Session $session): static
    {
        if (!$this->session->contains($session)) {
            $this->session->add($session);
            $session->setFactureTeacher($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->session->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getFactureTeacher() === $this) {
                $session->setFactureTeacher(null);
            }
        }

        return $this;
    }
}

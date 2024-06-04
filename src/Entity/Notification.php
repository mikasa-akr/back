<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\NotificationRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notification_details'])] 
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['notification_details'])] 
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['notification_details'])] 
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Teacher $teacher = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Student $student = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Group $rgroupe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;

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

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getRgroupe(): ?Group
    {
        return $this->rgroupe;
    }

    public function setRgroupe(?Group $rgroupe): static
    {
        $this->rgroupe = $rgroupe;

        return $this;
    }
}

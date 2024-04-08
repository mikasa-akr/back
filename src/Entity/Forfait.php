<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ForfaitRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ForfaitRepository::class)]
#[UniqueEntity(fields: ['title'], message: 'There is already an account with this title')]
class Forfait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $price = null;

    #[ORM\Column]
    private ?int $nbr_hour_session = null;

    #[ORM\Column]
    private ?int $nbr_hour_seance = null;
    #[ORM\Column(length: 255)]
    private ?string $Subscription = null;

    #[ORM\OneToMany(mappedBy: 'forfait', targetEntity: Student::class)]
    private Collection $students;

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getNbrHourSession(): ?int
    {
        return $this->nbr_hour_session;
    }

    public function setNbrHourSession(int $nbr_hour_session): static
    {
        $this->nbr_hour_session = $nbr_hour_session;

        return $this;
    }

    public function getNbrHourSeance(): ?int
    {
        return $this->nbr_hour_seance;
    }

    public function setNbrHourSeance(int $nbr_hour_seance): static
    {
        $this->nbr_hour_seance = $nbr_hour_seance;

        return $this;
    }

         /**
     * Convert entity properties to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'price' => $this->getPrice(),
            'NbrHourSeance' => $this->getNbrHourSeance(),
            'NbrHourSession' => $this->getNbrHourSession(),
            'NbrHourSession' => $this->getNbrHourSession(),
            'subscription' => $this->getSubscription(),
        ];
    }

    public function getSubscription(): ?string
    {
        return $this->Subscription;
    }

    public function setSubscription(string $Subscription): static
    {
        $this->Subscription = $Subscription;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setForfait($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getForfait() === $this) {
                $student->setForfait(null);
            }
        }

        return $this;
    }
}

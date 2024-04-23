<?php

namespace App\Entity;

use App\Repository\GenderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GenderRepository::class)]
class Gender
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(targetEntity: Student::class, mappedBy: 'genders')]
    private Collection $student;

    #[ORM\OneToMany(targetEntity: Teacher::class, mappedBy: 'genders')]
    private Collection $teacher;

    #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'gender')]
    private Collection $groupes;

    public function __construct()
    {
        $this->student = new ArrayCollection();
        $this->teacher = new ArrayCollection();
        $this->groupes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudent(): Collection
    {
        return $this->student;
    }

    public function addStudent(Student $student): static
    {
        if (!$this->student->contains($student)) {
            $this->student->add($student);
            $student->setGender($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        if ($this->student->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getGender() === $this) {
                $student->setGender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Teacher>
     */
    public function getTeacher(): Collection
    {
        return $this->teacher;
    }

    public function addTeacher(Teacher $teacher): static
    {
        if (!$this->teacher->contains($teacher)) {
            $this->teacher->add($teacher);
            $teacher->setGender($this);
        }

        return $this;
    }

    public function removeTeacher(Teacher $teacher): static
    {
        if ($this->teacher->removeElement($teacher)) {
            // set the owning side to null (unless already changed)
            if ($teacher->getGender() === $this) {
                $teacher->setGender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroupes(): Collection
    {
        return $this->groupes;
    }

    public function addGroupe(Group $groupe): static
    {
        if (!$this->groupes->contains($groupe)) {
            $this->groupes->add($groupe);
            $groupe->setGender($this);
        }

        return $this;
    }

    public function removeGroupe(Group $groupe): static
    {
        if ($this->groupes->removeElement($groupe)) {
            // set the owning side to null (unless already changed)
            if ($groupe->getGender() === $this) {
                $groupe->setGender(null);
            }
        }

        return $this;
    }
}

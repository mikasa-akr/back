<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group_list'])]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'groupe_seance', targetEntity: Session::class)]
    private Collection $sessions;

    #[ORM\ManyToMany(targetEntity: Teacher::class, inversedBy: 'groupeT')]
    private Collection $teachers;

    #[ORM\ManyToMany(targetEntity: Student::class, inversedBy: 'groupe', cascade: ['persist'])]
    private ?Collection $students = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group_list'])]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'groupes')]
    private ?Teacher $teach = null;



    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->teachers = new ArrayCollection();
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setGroupeSeanceId($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getGroupeSeanceId() === $this) {
                $session->setGroupeSeanceId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Teacher>
     */
    public function getTeachers(): Collection
    {
        return $this->teachers;
    }

    public function addTeacher(Teacher $teacher): static
    {
        if (!$this->teachers->contains($teacher)) {
            $this->teachers->add($teacher);
        }

        return $this;
    }

    public function removeTeacher(Teacher $teacher): static
    {
        $this->teachers->removeElement($teacher);

        return $this;
    }

    public function hasCourse(Course $course): bool
{
    foreach ($this->students as $student) {
        if ($student->getCourse()->contains($course)) {
            return true;
        }
    }
    return false;
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
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        $this->students->removeElement($student);

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
    public function toArray(): array
    {
        
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'student_id' => $this->getStudents(),
            'teacher_id' => $this->getTeachers()->toArray(),
        ];
    }

    public function getTeach(): ?Teacher
    {
        return $this->teach;
    }

    public function setTeach(?Teacher $teach): static
    {
        $this->teach = $teach;

        return $this;
    }
}

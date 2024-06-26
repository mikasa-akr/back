<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\GroupRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
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
    #[Groups(['group_sessions'])] 
    private Collection $sessions;

    #[ORM\ManyToMany(targetEntity: Student::class, inversedBy: 'groupe', cascade: ['persist'])]
    #[Groups(['group_list'])]
    private ?Collection $students = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group_list'])]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'groupes')]
    #[Groups(['group_list'])]
    private ?Teacher $teach = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group_list'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group_list'])]
    private ?string $avatar = null;

    #[ORM\ManyToOne(inversedBy: 'groupes')]
    private ?Gender $gender = null;


    #[ORM\ManyToMany(targetEntity: Teacher::class, mappedBy: 'groupM')]
    private Collection $teachers;

    #[ORM\OneToOne(cascade: ['remove'])]
    private ?Chat $chat = null;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'rgroupe')]
    private Collection $notifications;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->students = new ArrayCollection();
        $this->teachers = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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
        if ($this->students->removeElement($student)) {
            $student->removeGroupe($this);
        }
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
            'teacher_id' => $this->getTeach()->toArray(),
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): static
    {
        $this->gender = $gender;

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
            $teacher->addGroupM($this);
        }

        return $this;
    }

    public function removeTeacher(Teacher $teacher): static
    {
        if ($this->teachers->removeElement($teacher)) {
            $teacher->removeGroupM($this);
        }

        return $this;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): static
    {
        $this->chat = $chat;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setRgroupe($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getRgroupe() === $this) {
                $notification->setRgroupe(null);
            }
        }

        return $this;
    }
    
}

<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\TeacherRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: TeacherRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class Teacher implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Course $course = null;

    public function getCourse(): ?Course
    {
        return $this->course;
    }
    public function getPaymentStatus(): ?string
{
    return null;
}

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password;

    #[ORM\Column(length: 255)]
    private ?string $first_name;

    #[ORM\Column(length: 255)]
    private ?string $last_name;

    #[ORM\Column(length: 255)]
    private ?string $number;

    #[ORM\Column(length: 255)]
    private ?string $avatar;

    #[ORM\OneToMany(mappedBy: 'teacher_id', targetEntity: Session::class)]
    private Collection $sessions;


    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?string $hourly_rate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $registeredAt = null;

    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: Reclamation::class)]
    private Collection $reclamations;

    #[ORM\OneToMany(targetEntity: FactureTeacher::class, mappedBy: 'teacher')]
    private Collection $factureTeachers;

    #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'teach')]
    private Collection $groupes;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'teacher')]
    private Collection $notifications;

    #[ORM\ManyToOne(inversedBy: 'teacher')]
    private ?Gender $genders = null;

    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'teachers')]
    private Collection $groupM;

    #[ORM\OneToMany(targetEntity: Messagerie::class, mappedBy: 'teacher')]
    private Collection $messageries;

    #[ORM\OneToMany(targetEntity: Chat::class, mappedBy: 'teacher')]
    private Collection $chats;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
        $this->factureTeachers = new ArrayCollection();
        $this->groupes = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->groupM = new ArrayCollection();
        $this->messageries = new ArrayCollection();
        $this->chats = new ArrayCollection();
        
    }

     /**
     * @see UserInterface
     */
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

     /**
     * @see UserInterface
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }
    
     /**
     * @see UserInterface
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }
    
    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): static
    {
        $this->avatar = $avatar;

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
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'email' => $this->getEmail(),
            'avatar' => $this->getAvatar(),
            'number' => $this->getNumber(),
            'gender' => $this->getGender(),
            'course' => $this->getCourse()
        ];
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
            $session->setTeacher($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            if ($session->getTeacher() === $this) {
                $session->setTeacher(null);
            }
        }

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

    public function getHourlyRate(): ?string
    {
        return $this->hourly_rate;
    }

    public function setHourlyRate(string $hourly_rate): static
    {
        $this->hourly_rate = $hourly_rate;

        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeInterface $registeredAt): static
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): static
    {
        if (!$this->reclamations->contains($reclamation)) {
            $this->reclamations->add($reclamation);
            $reclamation->setTeacher($this);
        }

        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): static
    {
        if ($this->reclamations->removeElement($reclamation)) {
            // set the owning side to null (unless already changed)
            if ($reclamation->getTeacher() === $this) {
                $reclamation->setTeacher(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FactureTeacher>
     */
    public function getFactureTeachers(): Collection
    {
        return $this->factureTeachers;
    }

    public function addFactureTeacher(FactureTeacher $factureTeacher): static
    {
        if (!$this->factureTeachers->contains($factureTeacher)) {
            $this->factureTeachers->add($factureTeacher);
            $factureTeacher->setTeacher($this);
        }

        return $this;
    }

    public function removeFactureTeacher(FactureTeacher $factureTeacher): static
    {
        if ($this->factureTeachers->removeElement($factureTeacher)) {
            // set the owning side to null (unless already changed)
            if ($factureTeacher->getTeacher() === $this) {
                $factureTeacher->setTeacher(null);
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
            $groupe->setTeach($this);
        }

        return $this;
    }

    public function removeGroupe(Group $groupe): static
    {
        if ($this->groupes->removeElement($groupe)) {
            // set the owning side to null (unless already changed)
            if ($groupe->getTeach() === $this) {
                $groupe->setTeach(null);
            }
        }

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
            $notification->setTeacher($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getTeacher() === $this) {
                $notification->setTeacher(null);
            }
        }

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->genders;
    }

    public function setGender(?Gender $genders): static
    {
        $this->genders = $genders;

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroupM(): Collection
    {
        return $this->groupM;
    }

    public function addGroupM(Group $groupM): static
    {
        if (!$this->groupM->contains($groupM)) {
            $this->groupM->add($groupM);
        }

        return $this;
    }

    public function removeGroupM(Group $groupM): static
    {
        $this->groupM->removeElement($groupM);

        return $this;
    }

    /**
     * @return Collection<int, Messagerie>
     */
    public function getMessageries(): Collection
    {
        return $this->messageries;
    }

    /**
     * @return Collection<int, Chat>
     */
    public function getChats(): Collection
    {
        return $this->chats;
    }

    public function addChat(Chat $chat): static
    {
        if (!$this->chats->contains($chat)) {
            $this->chats->add($chat);
            $chat->setTeacher($this);
        }

        return $this;
    }

    public function removeChat(Chat $chat): static
    {
        if ($this->chats->removeElement($chat)) {
            // set the owning side to null (unless already changed)
            if ($chat->getTeacher() === $this) {
                $chat->setTeacher(null);
            }
        }

        return $this;
    }

}

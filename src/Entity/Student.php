<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\StudentRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class Student implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(length: 255)]
    private ?string $age;

    #[ORM\Column(length: 255)]
    private ?string $parent_email;

    public function __construct()
    {
        $this->course = new ArrayCollection();
        $this->groupe = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }
    public function getAge(): ?string
    {
        return $this->age;
    }

    public function setAge(string $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getParentEmail(): ?string
    {
        return $this->parent_email;
    }

    public function setParentEmail(string $parent_email): static
    {
        $this->parent_email = $parent_email;

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

    #[ORM\ManyToMany(targetEntity: Course::class, inversedBy: 'students')]
    #[Groups(["exclude_course"])]
    private Collection $course;

    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'students')]
    private Collection $groupe;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Forfait $forfait = null;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Reclamation::class)]
    private Collection $reclamations;

    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'student', cascade: ['remove'])]
    private Collection $payments;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAt = null;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'student')]
    private Collection $notifications;

    #[ORM\ManyToOne(inversedBy: 'student')]
    private ?Gender $genders = null;

    
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
            'password' => $this->getPassword(),
            'avatar' => $this->getAvatar(),
            'number' => $this->getNumber(),
            'gender' => $this->getGender(),
            'age' => $this->getAge(),
            'course_id' => $this->getCourse()

        ];
    } 

    /**
     * @return Collection<int, Course>
     */
    public function getCourse(): Collection
    {
        return $this->course;
    }

    public function addCourse(Course $course): static
    {
        if (!$this->course->contains($course)) {
            $this->course->add($course);
        }

        return $this;
    }

    public function removeCourse(Course $course): static
    {
        $this->course->removeElement($course);

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroupe(): Collection
    {
        return $this->groupe;
    }

    public function addGroupe(Group $groupe): static
    {
        if (!$this->groupe->contains($groupe)) {
            $this->groupe->add($groupe);
            $groupe->addStudent($this);
        }

        return $this;
    }

    public function removeGroupe(Group $groupe): static
    {
        if ($this->groupe->removeElement($groupe)) {
            $groupe->removeStudent($this);
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

    public function getForfait(): ?Forfait
    {
        return $this->forfait;
    }

    public function setForfait(?Forfait $forfait): static
    {
        $this->forfait = $forfait;

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
            $reclamation->setStudent($this);
        }

        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): static
    {
        if ($this->reclamations->removeElement($reclamation)) {
            // set the owning side to null (unless already changed)
            if ($reclamation->getStudent() === $this) {
                $reclamation->setStudent(null);
            }
        }

        return $this;
    }

     /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setStudent($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getStudent() === $this) {
                $payment->setStudent(null);
            }
        }

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPaymentStatus(): ?string
    {
        foreach ($this->payments as $payment) {
            // Assuming Payment entity has a getStatus method that returns the status
            return $payment->getStatus();
        }
        // If no payments are found, return null or another appropriate value
        return null;
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
            $notification->setStudent($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getStudent() === $this) {
                $notification->setStudent(null);
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
}

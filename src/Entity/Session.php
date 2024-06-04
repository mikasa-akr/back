<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\SessionRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?bool $visibility = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[Groups(["session_list"])]
    private ?Group $groupe_seance = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    private ?Teacher $teacher = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    private ?Course $seance_course = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_seance = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $time_start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $time_end = null;

    #[ORM\OneToMany(mappedBy: 'session', targetEntity: Rattrapage::class,cascade:['remove'])]
    private Collection $rattrapages;

    #[ORM\OneToMany(mappedBy: 'session', targetEntity: Vote::class,cascade:['remove'])]
    private Collection $votes;

    #[ORM\ManyToOne(inversedBy: 'session',cascade:['remove'])]
    private ?FactureTeacher $factureTeacher = null;

    public function __construct()
    {
        $this->rattrapages = new ArrayCollection();
        $this->votes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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


    public function isVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getGroupeSeanceId(): ?Group
    {
        return $this->groupe_seance;
    }

    public function setGroupeSeanceId(?Group $groupe_seance_id): static
    {
        $this->groupe_seance = $groupe_seance_id;

        return $this;
    }

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(?Teacher $teacher_id): static
    {
        $this->teacher = $teacher_id;

        return $this;
    }

    public function getSeanceCourse(): ?Course
    {
        return $this->seance_course;
    }

    public function setSeanceCourse(?Course $seance_course): static
    {
        $this->seance_course = $seance_course;

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
           'status'=> $this->getStatus(),
           'date_seance'=> $this->getDateSeance(),
           'time_start'=> $this->getTimeStart(),
           'time_end'=>$this->getTimeEnd(),
           'visibility'=>$this->isVisibility(),
           'teacher_id'=> $this->getTeacher(),
           'groupe_seance_id'=> $this->getGroupeSeanceId(),
           'seance_course_id'=> $this->getSeanceCourse()
       ];
   }

   public function getDateSeance(): ?\DateTimeInterface
   {
       return $this->date_seance;
   }

   public function setDateSeance(\DateTimeInterface $date_seance): static
   {
       $this->date_seance = $date_seance;

       return $this;
   }

   public function getTimeStart(): ?\DateTimeInterface
   {
       return $this->time_start;
   }

   public function setTimeStart(\DateTimeInterface $time_start): static
   {
       $this->time_start = $time_start;

       return $this;
   }

   public function getTimeEnd(): ?\DateTimeInterface
   {
       return $this->time_end;
   }

   public function setTimeEnd(\DateTimeInterface $time_end): static
   {
       $this->time_end = $time_end;

       return $this;
   }

   /**
    * @return Collection<int, Rattrapage>
    */
   public function getRattrapages(): Collection
   {
       return $this->rattrapages;
   }

   public function addRattrapage(Rattrapage $rattrapage): static
   {
       if (!$this->rattrapages->contains($rattrapage)) {
           $this->rattrapages->add($rattrapage);
           $rattrapage->setSession($this);
       }

       return $this;
   }

   public function removeRattrapage(Rattrapage $rattrapage): static
   {
       if ($this->rattrapages->removeElement($rattrapage)) {
           // set the owning side to null (unless already changed)
           if ($rattrapage->getSession() === $this) {
               $rattrapage->setSession(null);
           }
       }

       return $this;
   }

   /**
    * @return Collection<int, Vote>
    */
   public function getVotes(): Collection
   {
       return $this->votes;
   }

   public function addVote(Vote $vote): static
   {
       if (!$this->votes->contains($vote)) {
           $this->votes->add($vote);
           $vote->setSession($this);
       }

       return $this;
   }

   public function removeVote(Vote $vote): static
   {
       if ($this->votes->removeElement($vote)) {
           // set the owning side to null (unless already changed)
           if ($vote->getSession() === $this) {
               $vote->setSession(null);
           }
       }

       return $this;
   }

   public function getFactureTeacher(): ?FactureTeacher
   {
       return $this->factureTeacher;
   }

   public function setFactureTeacher(?FactureTeacher $factureTeacher): static
   {
       $this->factureTeacher = $factureTeacher;

       return $this;
   }

}

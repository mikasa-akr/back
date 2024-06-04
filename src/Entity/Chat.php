<?php

namespace App\Entity;

use App\Repository\ChatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatRepository::class)]
class Chat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'chats')]
    private ?Teacher $teacher = null;

    #[ORM\OneToOne(cascade: ['remove'])]
    private ?Group $groupe = null;

    #[ORM\OneToMany(targetEntity: Messagerie::class, mappedBy: 'chat',cascade:['remove'])]
    private Collection $message;

    public function __construct()
    {
        $this->message = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getGroupe(): ?Group
    {
        return $this->groupe;
    }

    public function setGroupe(?Group $groupe): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    /**
     * @return Collection<int, Messagerie>
     */
    public function getMessage(): Collection
    {
        return $this->message;
    }

    public function addMessage(Messagerie $message): static
    {
        if (!$this->message->contains($message)) {
            $this->message->add($message);
            $message->setChat($this);
        }

        return $this;
    }

    public function removeMessage(Messagerie $message): static
    {
        if ($this->message->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getChat() === $this) {
                $message->setChat(null);
            }
        }

        return $this;
    }
}

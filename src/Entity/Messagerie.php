<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MessagerieRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MessagerieRepository::class)]
class Messagerie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message_details'])] 
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['message_details'])] 
    private ?\DateTimeInterface $timeSend = null;

    #[ORM\Column(length: 255)]
    #[Groups(['message_details'])] 
    private ?string $context = null;

    #[ORM\Column]
    private ?int $sender = null;

    #[ORM\ManyToOne(inversedBy: 'message')]
    private ?Chat $chat = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeSend(): ?\DateTimeInterface
    {
        return $this->timeSend;
    }

    public function setTimeSend(\DateTimeInterface $timeSend): static
    {
        $this->timeSend = $timeSend;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(string $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getSender(): ?int
    {
        return $this->sender;
    }

    public function setSender(int $sender): static
    {
        $this->sender = $sender;

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

}

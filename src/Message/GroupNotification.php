<?php
namespace App\Message;

class GroupNotification
{
    private int $chatId;
    private string $message;

    public function __construct(int $chatId, string $message)
    {
        $this->chatId = $chatId;
        $this->message = $message;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}

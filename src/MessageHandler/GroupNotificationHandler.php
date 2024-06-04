<?php
namespace App\MessageHandler;

use App\Entity\Chat;
use Psr\Log\LoggerInterface;
use App\Service\MailerService;
use App\Message\GroupNotification;
use App\Repository\ChatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GroupNotificationHandler
{
    private $entityManager;
    private $chatRepository;
    private $mailer;
    private $logger;

    public function __construct(ChatRepository $chatRepository, EntityManagerInterface $entityManager, MailerService $mailer, LoggerInterface $logger)
    {
        $this->chatRepository = $chatRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function __invoke(GroupNotification $notification)
    {
        try {
            $chatId = $notification->getChatId();
            $message = $notification->getMessage();

            $chat = $this->entityManager->getRepository(Chat::class)->find($chatId);

            if (!$chat) {
                throw new \Exception('Chat not found');
            }

            $groups = $chat->getGroupe();
            $students = $groups->getStudents();

            foreach ($students as $student) {
                $email = $student->getEmail();
                $this->mailer->sendNotificationEmail($email, $message);
            }

            $teacher = $chat->getTeacher();
            if ($teacher) {
                $email = $teacher->getEmail();
                $this->mailer->sendNotificationEmail($email, $message);
            }

            $this->logger->info("Notification processed for chat ID: $chatId");
        } catch (\Exception $e) {
            $this->logger->error('Failed to process group notification: ' . $e->getMessage());
        }
    }
}

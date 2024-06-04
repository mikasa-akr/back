<?php

namespace App\Controller;

use DateTime;
use App\Entity\Chat;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Messagerie;
use Psr\Log\LoggerInterface;
use App\Service\MailerService;
use Symfony\Component\Mime\Email;
use App\Message\GroupNotification;
use App\Repository\ChatRepository;
use Symfony\Component\Mime\Address;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\MessagerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/Messagerie',name:'api_crud_Messagerie')]
class MessagerieCRUDController extends AbstractController
{

    private $mailerService;
    private $logger;

    public function __construct(private ValidatorInterface $validator,private MessagerieRepository $MessagerieRepository,MailerService $mailerService,LoggerInterface $logger)
    {
        $this->mailerService = $mailerService;
        $this->logger = $logger;
    }
    
    #[Route('/', name: 'api_crud_Messagerie_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $Messageries = $this->MessagerieRepository->findAll();
    $data = [];
    foreach ($Messageries as $Messagerie) {
        $data[] = [
            'id' => $Messagerie->getId(),
            'timeSend' => $Messagerie->getTimeSend(),
            'context' => $Messagerie->getContext(),

        ];
    }
    return new JsonResponse($data, Response::HTTP_OK);
    }
    #[Route('/{id}/edit', name: 'api_crud_Messagerie_edit', methods: ['PUT'])]
    public function update($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $Messagerie = $entityManager->getRepository(Messagerie::class)->findOneBy(['id' => $id]);
    if (!$Messagerie) {
        return new JsonResponse(['error' => 'Messagerie not found'], Response::HTTP_NOT_FOUND);
    }
    $data = json_decode($request->getContent(), true);
    empty($data['context']) ? true : $Messagerie->setContext($data['context']);
    empty($data['timemSend']) ? true : $Messagerie->setTimeSend($data['timemSend']);
    empty($data['group']) ? true : $Messagerie->setGroupe($data['group']);

    if (!empty($data['teacher_id'])) {
        $teacherId = $data['teacher_id'];
        $teacher = $entityManager->getRepository(Teacher::class)->find($teacherId);
        if (!$teacher) {
            return new JsonResponse(['error' => 'teacher not found'], Response::HTTP_NOT_FOUND);
        }
        $Messagerie->setTeacher($teacher);;
    }

    $entityManager->persist($Messagerie);
    $entityManager->flush();

    return new JsonResponse($Messagerie->toArray(), Response::HTTP_OK);
}
    #[Route('/{id}', name: 'api_crud_Messagerie_delete', methods: ['DELETE'])]
    public function delete(Messagerie $Messagerie, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($Messagerie);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/create/teacher/{id}/{chatid}', name: 'api_teacher_Messagerie', methods: ['POST'])]
    public function TeacherMessagerie($id,$chatid,TeacherRepository $teacherRepository,Request $request,EntityManagerInterface $entityManager,MailerService $mailerService,LoggerInterface $logger): JsonResponse {
        $data = json_decode($request->getContent(), true);
    
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
    
        $context = $data['context'] ?? null;
        if (empty($context)) {
            return $this->json(['error' => 'Message context is required'], Response::HTTP_BAD_REQUEST);
        }
    
        $teacher = $teacherRepository->find($id);
        if (!$teacher) {
            return $this->json(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
        }
    
        $chat = $entityManager->getRepository(Chat::class)->find($chatid);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found'], Response::HTTP_NOT_FOUND);
        }
    
        $date = new \DateTime('now');
        $messagerie = new Messagerie();
        $messagerie->setContext($context);
        $messagerie->setTimeSend($date);
        $messagerie->setSender($teacher->getId());
        $messagerie->setChat($chat);
    
        $group = $chat->getGroupe();
        if (!$group) {
            return $this->json(['error' => 'Group not found for this chat'], Response::HTTP_NOT_FOUND);
        }
    
        $emailSendSuccess = true;
        $text = $teacher->getLastName().' '. $teacher->getFirstName() . ' sent a new message. Go check it out.';
        $subject = $teacher->getLastName() . ' sent a new message ';
        $nom = $teacher->getFirstName() . ' via Edu School';
    
        $url = 'http://localhost:3000/chat/student';
        $htmlContent = '
        <div style="font-size: 18px; line-height: 1.5;">
            <p style="text-align: center; color: #000000; ">' . htmlspecialchars($text) . '</p>
            <p style="text-align: center;">
                <a href="' . htmlspecialchars($url) . '" style="display: inline-block; padding: 10px 20px; font-size: 16px; color: #ffffff; background-color: #007bff; text-align: center; text-decoration: none; border-radius: 5px;">View Message</a>
            </p>
        </div>
    ';
    
        foreach ($group->getStudents() as $member) {
            $email = $member->getEmail();
            try {
                $mailerService->sendNotificationEmail($email,$subject,$htmlContent,$nom);
                $logger->info("Email sent to {$email}");
            } catch (\Exception $e) {
                $emailSendSuccess = false;
                $logger->error("Failed to send email to {$email}: " . $e->getMessage());
                break;
            }
        }
    
        if ($emailSendSuccess) {
            try {
                $entityManager->persist($messagerie);
                $entityManager->flush();
                return $this->json(['message' => 'Messagerie registered successfully'], Response::HTTP_CREATED);
            } catch (\Exception $e) {
                $logger->error("Failed to save message: " . $e->getMessage());
                return $this->json(['error' => 'Failed to save message'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            $logger->error("Message not saved due to email sending failure");
            return $this->json(['error' => 'Failed to send emails'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    

    #[Route('/create/group/{id}/{chatid}', name: 'api_group_messagerie', methods: ['POST'])]
    public function GroupMessagerie($id, $chatid, StudentRepository $studentRepository, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $student = $studentRepository->findOneBy(['id' => $id]);
        if (!$student) {
            return $this->json(['error' => 'Student not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $groups = $student->getGroupe();
        $context = $data['context'] ?? null;
        $date = new \DateTime('now');

        foreach ($groups as $group) {
            $chat = $entityManager->getRepository(Chat::class)->find($chatid);
            if (!$chat) {
                continue;
            }

            $messagerie = new Messagerie();
            $messagerie->setContext($context);
            $messagerie->setTimeSend($date);
            $messagerie->setSender($student->getId());
            $messagerie->setChat($chat);
            
            $emailSendSuccess = true;
            $text= $student->getLastName().' send new message go check it ';
            $subject = $student->getLastName() . ' sent a new message ';
            $nom = $student->getFirstName() . ' via Edu School';
            $url = 'http://localhost:3000/chat/teacher';
            $htmlContent = '
            <div style="font-size: 18px; line-height: 1.5;">
                <p style="text-align: center; color: #000000;">' . htmlspecialchars($text) . '</p>
                <p style="text-align: center;">
                    <a href="' . htmlspecialchars($url) . '" style="display: inline-block; padding: 10px 20px; font-size: 16px; color: #ffffff; background-color: #007bff; text-align: center; text-decoration: none; border-radius: 5px;">View Message</a>
                </p>
            </div>
        ';

        $url1 = 'http://localhost:3000/chat/student';
        $htmlContent1 = '
        <div style="font-size: 18px; line-height: 1.5;">
            <p style="text-align: center;color: #000000;">' . htmlspecialchars($text) . '</p>
            <p style="text-align: center;">
                <a href="' . htmlspecialchars($url1) . '" style="display: inline-block; padding: 10px 20px; font-size: 16px; color: #ffffff; background-color: #007bff; text-align: center; text-decoration: none; border-radius: 5px;">View Message</a>
            </p>
        </div>
    ';

            // Send email to all students in the group
            $groupMembers = $group->getStudents();
            foreach ($groupMembers as $member) {
                $email = $member->getEmail();
                try {
                    $this->mailerService->sendNotificationEmail($email,$subject,$htmlContent,$nom);
                    $this->logger->info("Email sent to {$email}");
                } catch (\Exception $e) {
                    $emailSendSuccess = false;
                    $this->logger->error("Failed to send email to {$email}: " . $e->getMessage());
                    break;
                }
            }

            // Send email to teacher
            $teacher = $group->getTeach();
            if ($teacher) {
                $teacherEmail = $teacher->getEmail();
                try {
                    $this->mailerService->sendNotificationEmail($teacherEmail,$subject,$htmlContent1,$nom);
                    $this->logger->info("Email sent to teacher {$teacherEmail}");
                } catch (\Exception $e) {
                    $emailSendSuccess = false;
                    $this->logger->error("Failed to send email to teacher {$teacherEmail}: " . $e->getMessage());
                }
            }

            if ($emailSendSuccess) {
                $entityManager->persist($messagerie);
            } else {
                $this->logger->error("Message not saved due to email sending failure");
                return $this->json(['error' => 'Failed to send emails'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $entityManager->flush();
        return $this->json(['message' => 'Messagerie registered successfully and emails sent'], JsonResponse::HTTP_CREATED);
    }

        #[Route('/messages/{id}', name: 'api_teacher_messages', methods: ['GET'])]
        public function getMessages(int $id, ChatRepository $chatRepository, EntityManagerInterface $entityManager): JsonResponse
        {
            $chat = $chatRepository->findOneBy(['id' => $id]);
            
            if (!$chat) {
                return $this->json(['error' => 'Chat not found'], Response::HTTP_NOT_FOUND);
            }
            
            $messages = $chat->getMessage();
            
            $formattedMessages = [];
            foreach ($messages as $message) {
                $senderId = $message->getSender();
                $sender = null;
                if ($senderId) {
                    $sender = $entityManager->getRepository(Student::class)->find($senderId);
                    if (!$sender) {
                        $sender = $entityManager->getRepository(Teacher::class)->find($senderId);
                    }
                }                    
                $formattedMessages[] = [
                    'id' => $message->getId(),
                    'timeSend' => $message->getTimeSend(),
                    'context' => $message->getContext(),
                    'senderId' =>$sender->getId(),
                    'sender' => $sender ? $sender->getFirstName() : null,
                    'senderA' => $sender ? $sender->getAvatar() : null,
                ];
            }
            
            // Sort messages by timeSend
            usort($formattedMessages, function($a, $b) {
                return $a['timeSend'] <=> $b['timeSend'];
            });
            
            return $this->json($formattedMessages, Response::HTTP_OK, [], ['groups' => ['message_details']]);
        }
        
        
        #[Route('/teacher/chat/{id}', name: 'api_teacher_chat', methods: ['GET'])]
        public function getTeacherChat(int $id, TeacherRepository $teacherRepository, EntityManagerInterface $entityManager): JsonResponse
        {
            $teacher = $teacherRepository->findOneBy(['id' => $id]);
            
            if (!$teacher) {
                return $this->json(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
            }
        
            // Get the chats associated with the teacher
            $chats = $entityManager->getRepository(Chat::class)->findBy(['teacher' => $teacher]);
        
            $formattedChats = [];
            foreach ($chats as $chat) {
                $group = $chat->getGroupe();
                $formattedChats[] = [
                    'id' => $chat->getId(),
                    'groupid' => $group->getId(),
                    'name' => $group->getName(),
                    'avatar' => $group->getAvatar(),
                ];
            }
            
            return $this->json($formattedChats, Response::HTTP_OK, [], ['groups' => ['message_details']]);
        }

        #[Route('/student/chat/{id}', name: 'api_student_chat', methods: ['GET'])]
        public function getstudentChat(int $id, StudentRepository $studentRepository, EntityManagerInterface $entityManager): JsonResponse
        {
            $student = $studentRepository->findOneBy(['id' => $id]);
            
            if (!$student) {
                return $this->json(['error' => 'student not found'], Response::HTTP_NOT_FOUND);
            }
            $groups = $student->getGroupe();
            $formattedChats = [];
            foreach($groups as $group){
                $chats = $entityManager->getRepository(Chat::class)->findBy(['groupe' => $group]);
                foreach ($chats as $chat) {
                    $formattedChats[] = [
                        'id' => $chat->getId(),
                        'groupid' => $group->getId(),
                        'name' => $group->getName(),
                        'avatar' => $group->getAvatar(),
                    ];
                }
            }    
            return $this->json($formattedChats, Response::HTTP_OK, [], ['groups' => ['message_details']]);
        }

        #[Route('/all/chat', name: 'api_all_chat', methods: ['GET'])]
        public function getChat(EntityManagerInterface $entityManager): JsonResponse
        {

            $formattedChats = [];
                $chats = $entityManager->getRepository(Chat::class)->findAll();
                foreach ($chats as $chat) {
                    $group = $chat->getGroupe();
                    $formattedChats[] = [
                        'id' => $chat->getId(),
                        'groupid' => $group->getId(),
                        'name' => $group->getName(),
                        'avatar' => $group->getAvatar(),
                    ];
                }   
            return $this->json($formattedChats, Response::HTTP_OK, [], ['groups' => ['message_details']]);
        }
        
        
        
}

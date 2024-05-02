<?php

namespace App\Controller;

use DateTime;
use App\Entity\Chat;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Messagerie;
use App\Repository\ChatRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\MessagerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/Messagerie',name:'api_crud_Messagerie')]
class MessagerieCRUDController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private MessagerieRepository $MessagerieRepository)
    {

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
    public function TeacherMessagerie($id, $chatid, TeacherRepository $teacherRepository, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
    
        // Validate context
        $context = $data['context'] ?? null;
        if (empty($context)) {
            return $this->json(['error' => 'Message context is required'], Response::HTTP_BAD_REQUEST);
        }
    
        // Find teacher
        $teacher = $teacherRepository->findOneBy(['id' => $id]);
        $teacherId = $teacher->getId();
        if (!$teacher) {
            return $this->json(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
        }
    
        // Find chat
        $chat = $entityManager->getRepository(Chat::class)->find($chatid);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found'], Response::HTTP_NOT_FOUND);
        }
    
        // Create message
        $date = new DateTime('now');
        $messagerie = new Messagerie();
        $messagerie->setContext($context);
        $messagerie->setTimeSend($date);
        $messagerie->setSender($teacherId);
        $messagerie->setChat($chat);
    
        // Persist message
        $entityManager->persist($messagerie);
        $entityManager->flush();
    
        return $this->json(['message' => 'Messagerie registered successfully'], Response::HTTP_CREATED);
    }
    

        #[Route('/create/group/{id}/{chatid}', name: 'api_group_Messagerie', methods: ['POST'])]
        public function GroupMessagerie($id,$chatid,StudentRepository $studentRepository,Request $request, EntityManagerInterface $entityManager): JsonResponse
        {
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
            }
            $student = $studentRepository->findOneBy(['id' => $id]);
            $studentId = $student->getId();
            $groups = $student->getGroupe();
            foreach($groups as $group){
                $chat = $entityManager->getRepository(Chat::class)->find($chatid);
                $context = $data['context'] ?? null;
                $date = new DateTime('now');

                $messagerie = new Messagerie();
                $messagerie->setContext($context);
                $messagerie->setTimeSend($date);
                $messagerie->setSender($studentId);
                $messagerie->setChat($chat);
                $entityManager->persist($messagerie);
            }
            $entityManager->flush();
        
            return $this->json(['message' => 'Messagerie registered successfully'], Response::HTTP_CREATED);
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

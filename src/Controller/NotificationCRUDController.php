<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Forfait;
use App\Entity\Subscription;
use App\Repository\ForfaitRepository;
use App\Repository\NotificationRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/notification',name:'api_notification')]
class NotificationCRUDController extends AbstractController
{

    #[Route('/teacher/{id}', name: 'api_notification_teacher', methods: ['GET'])]
    public function getNotificationTeacher(int $id, TeacherRepository $teacherRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $teacher = $teacherRepository->findOneBy(['id' => $id]);
        
        if (!$teacher) {
            return $this->json(['error' => 'teacher not found'], Response::HTTP_NOT_FOUND);
        }
        
        $notification = $teacher->getNotifications();
        
        $formattednotification = [];
        foreach ($notification as $notification) {
            $formattednotification[] = [
                'id' => $notification->getId(),
                'content' => $notification->getContent(),
                'time' => $notification->getSentAt()
            ];
        }
        
        return $this->json($formattednotification, Response::HTTP_OK, [], ['groups' => ['notification_details']]);
    }

    #[Route('/student/{id}', name: 'api_notification_student', methods: ['GET'])]
    public function getNotificationStudent(int $id, StudentRepository $studentRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $student = $studentRepository->findOneBy(['id' => $id]);
        
        if (!$student) {
            return $this->json(['error' => 'student not found'], Response::HTTP_NOT_FOUND);
        }
        
        $notification = $student->getNotifications();
        
        $formattednotification = [];
        foreach ($notification as $notification) {
            $formattednotification[] = [
                'id' => $notification->getId(),
                'content' => $notification->getContent(),
                'time' => $notification->getSentAt()
            ];
        }
        
        return $this->json($formattednotification, Response::HTTP_OK, [], ['groups' => ['notification_details']]);
    }
    
}

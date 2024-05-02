<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Reclamation;
use App\Repository\SessionRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReclamationRepository;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/reclamation',name:'api_crud_reclamation')]
class ReclamationCRUDController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private ReclamationRepository $reclamationRepository)
    {

    }
    #[Route('/', name: 'api_crud_reclamation_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $reclamations = $this->reclamationRepository->findAll();
        $data = [];
    
        foreach ($reclamations as $reclamation) {
            $data[] = [
                'id' => $reclamation->getId(),
                'type' => $reclamation->getType(),
                'status' => $reclamation->getStatus(),
                'teacher_id' => $reclamation->getTeacher() ? $reclamation->getTeacher()->getId() : null,
                'student_id' => $reclamation->getStudent() ? $reclamation->getStudent()->getId() : null,
                'reason' => $reclamation->getReason(),
                'time' => $reclamation->getTime() ? $reclamation->getTime()->format('Y-m-d H:i:s') : null,
            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'api_crud_reclamation_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $reclamation = $this->reclamationRepository->findOneBy(['id' => $id]);
    
        $data = [
            'id' => $reclamation->getId(),
            'type' => $reclamation->getType(),
            'status' => $reclamation->getStatus(),
            'teacher_id' => $reclamation->getTeacher()->getId(),
            'student_id' => $reclamation->getStudent()->getId(),
            'reason' => $reclamation->getReason(),
            'time' => $reclamation->getTime()
        ];
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'api_crud_reclamation_delete', methods: ['DELETE'])]
    public function delete(Reclamation $reclamation, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($reclamation);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/annulation/teacher/{id}', name: 'api_crud_annulation_teacher', methods: ['POST'])]
    public function AnnulationTeacher($id, Request $request, EntityManagerInterface $entityManager, SessionRepository $sessionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
        
        $reason = $data['reason'];
        $sessions = $sessionRepository->findBy(['id' => $id]);
    
        foreach ($sessions as $session) {
            $date = $session->getDateSeance();
            $timeS = $session->getTimeStart();
    
            // Get the current time in the timezone of the session
            $currentTime = new \DateTime('now', $date->getTimezone());
            $sessionDateTime = new \DateTime($date->format('Y-m-d') . ' ' . $timeS->format('H:i:s'));
    
            $this->handleSessionCancellation($session, $currentTime, $reason, $entityManager);
    
            return $this->json(['message' => 'Reclamation registered successfully'], Response::HTTP_CREATED);
        }
    
        return $this->json(['error' => 'No sessions found for the teacher'], Response::HTTP_NOT_FOUND);
    }
    
    private function handleSessionCancellation($session, $currentTime, $reason, $entityManager)
    {
        $date = $session->getDateSeance();
        $timeS = $session->getTimeStart();
    
        $sessionDateTime = new \DateTime($date->format('Y-m-d') . ' ' . $timeS->format('H:i:s'));
    
        $interval = $sessionDateTime->diff($currentTime);
        $totalHours = $interval->days * 24 + $interval->h;
    
        $teacher = $session->getTeacher();
        $reclamation = new Reclamation();
        $reclamation->setTime($currentTime);
        $reclamation->setType("session cancellation");
        $reclamation->setTeacher($teacher);
        $reclamation->setStatus("annulated");
        $reclamation->setReason($reason);
        $session->setStatus("canceled session");
        $entityManager->persist($reclamation);
        $entityManager->flush();
    }

    #[Route('/annulation/session/{studentId}/{sessionId}', name: 'api_annulation_session', methods: ['POST'])]
    public function AnnulationSession($studentId, $sessionId, Request $request, EntityManagerInterface $entityManager, SessionRepository $sessionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
        
        $reason = $data['reason'];
        
        // Retrieve the session from the database
        $session = $sessionRepository->find($sessionId);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Get the student by ID
        $student = $entityManager->getRepository(Student::class)->find($studentId);
        if (!$student) {
            return $this->json(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Get the current time
        $currentTime = new \DateTime();
        
        // Calculate the time difference between the session time and the current time
        $sessionTime = $session->getDateSeance()->format('Y-m-d') . ' ' . $session->getTimeStart()->format('H:i:s');
        $sessionDateTime = new \DateTime($sessionTime, $session->getDateSeance()->getTimezone());
        $diff = $sessionDateTime->diff($currentTime);
        $totalHours = $diff->days * 24 + $diff->h;
        
        // Create and persist the reclamation for the specified student
        // Create a reclamation for the student
        $reclamation = new Reclamation();
        $reclamation->setTime($currentTime);
        $reclamation->setType("session cancellation");
        $reclamation->setStudent($student);
        $reclamation->setReason($reason);
        $reclamation->setStatus("annulated");

        // Persist the reclamation and flush changes to the database
        $entityManager->persist($reclamation);
        
        // Update the session status to "canceled session"
        $session->setStatus("canceled session");
        
        $entityManager->flush();
        
        return $this->json(['message' => 'Reclamation registered successfully'], Response::HTTP_CREATED);
    }
    
    #[Route('/student/reclame/{studentId}/{sessionId}', name: 'api_seance_reclame', methods: ['POST'])]
    public function Reclamation($studentId, $sessionId, Request $request, EntityManagerInterface $entityManager, SessionRepository $sessionRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
        $reason = $data['reason'];
    
        $session = $sessionRepository->find($sessionId);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
    
        $student = $entityManager->getRepository(Student::class)->findOneBy(['id' => $studentId]);
        if (!$student) {
            return $this->json(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
    
        $group = $session->getGroupeSeanceId();
        if (!$group) {
            return $this->json(['error' => 'Group not found'], Response::HTTP_NOT_FOUND);
        }
    
        // Check if the student is a member of the group
        if (!$group->getStudents()->contains($student)) {
            return $this->json(['error' => 'Student is not a member of the group'], Response::HTTP_BAD_REQUEST);
        }
    
        $currentTime = new \DateTime('now');
    
        if ($reason === 'absence_prof') {
            $session->setStatus('perdu');
            // Flush changes to the session entity before creating the reclamation
            $entityManager->flush();
        }
    
        $reclamation = new Reclamation();
        $reclamation->setTime($currentTime);
        $reclamation->setType("reclamation");
        $reclamation->setStudent($student);
        $reclamation->setReason($reason);
        $reclamation->setStatus("reclame");
        $entityManager->persist($reclamation);
    
        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    
        return $this->json(['message' => 'Reclamation registered successfully'], Response::HTTP_CREATED);
    }
    

    #[Route('/student/annulation/{id}', name: 'api_crud_annulation_student', methods: ['GET'])]
    public function StudentAnnulation($id): JsonResponse
    {   
        $reclamations = $this->reclamationRepository->findBy(['student' => $id , 'status' => 'annulated']);
        $data = [];
    
        foreach ($reclamations as $reclamation) {
            $data[] = [
                'id' => $reclamation->getId(),
                'type' => $reclamation->getType(),
                'status' => $reclamation->getStatus(),
                'teacher_id' => $reclamation->getTeacher() ? $reclamation->getTeacher()->getId() : null,
                'student_id' => $reclamation->getStudent() ? $reclamation->getStudent()->getId() : null,
                'reason' => $reclamation->getReason(),
                'time' => $reclamation->getTime() ? $reclamation->getTime()->format('Y-m-d H:i:s') : null,
            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/student/reclamation/{id}', name: 'api_crud_reclamation_student', methods: ['GET'])]
    public function StudentReclamation($id): JsonResponse
    {
        $reclamations = $this->reclamationRepository->findBy(['student' => $id , 'status' => 'reclame']);
        $data = [];
    
        foreach ($reclamations as $reclamation) {
            $data[] = [
                'id' => $reclamation->getId(),
                'type' => $reclamation->getType(),
                'status' => $reclamation->getStatus(),
                'teacher_id' => $reclamation->getTeacher() ? $reclamation->getTeacher()->getId() : null,
                'student_id' => $reclamation->getStudent() ? $reclamation->getStudent()->getId() : null,
                'reason' => $reclamation->getReason(),
                'time' => $reclamation->getTime() ? $reclamation->getTime()->format('Y-m-d H:i:s') : null,
            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }


    #[Route('/teacher/annulation/{id}', name: 'api_crud_annulation_teacher_show', methods: ['GET'])]
    public function TeacherAnnulation($id): JsonResponse
    {   
        $reclamations = $this->reclamationRepository->findBy(['teacher' => $id , 'status' => 'annulated']);
        $data = [];
    
        foreach ($reclamations as $reclamation) {
            $data[] = [
                'id' => $reclamation->getId(),
                'type' => $reclamation->getType(),
                'status' => $reclamation->getStatus(),
                'teacher_id' => $reclamation->getTeacher() ? $reclamation->getTeacher()->getId() : null,
                'student_id' => $reclamation->getStudent() ? $reclamation->getStudent()->getId() : null,
                'reason' => $reclamation->getReason(),
                'time' => $reclamation->getTime() ? $reclamation->getTime()->format('Y-m-d H:i:s') : null,
            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    
}
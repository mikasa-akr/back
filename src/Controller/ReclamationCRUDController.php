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
        
        if ($totalHours >= 24) {
            $reclamation->setStatus("annulated before 24h");
            $reclamation->setReason(" ");
        } elseif ($totalHours >= 2 && $totalHours < 24) {
            $reclamation->setReason($reason);
            $reclamation->setStatus("annulated");
        } else {
            throw new \Exception('Invalid time difference');
        }
        $session->setStatus("canceled session");
        $entityManager->persist($reclamation);
        $entityManager->flush();
    }

    #[Route('/annulation/student/{id}', name: 'api_crud_annulation_student', methods: ['POST'])]
    public function AnnulationStudent($id, Request $request, EntityManagerInterface $entityManager, SessionRepository $sessionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
        
        $reason = $data['reason'];
        
        // Retrieve the student from the database
        $student = $entityManager->find(Student::class, $id);
        if (!$student) {
            return $this->json(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Retrieve the groups associated with the student
        $groups = $student->getGroupe();
        
        foreach ($groups as $group) {
            // Retrieve the sessions associated with the group
            $sessions = $group->getSessions();
            
            foreach ($sessions as $session) {
                $date = $session->getDateSeance();
                $timeS = $session->getTimeStart();
        
                // Get the current time in the timezone of the session
                $currentTime = new \DateTime('now', $date->getTimezone());
                $sessionDateTime = new \DateTime($date->format('Y-m-d') . ' ' . $timeS->format('H:i:s'));
        
                $interval = $sessionDateTime->diff($currentTime);
                $totalHours = $interval->days * 24 + $interval->h;
                $reclamation = new Reclamation();
                $reclamation->setTime($currentTime);
                $reclamation->setType("session cancellation");
                $reclamation->setStudent($student);
                if ($totalHours >= 24) {
                    $reclamation->setReason(" ");
                    $reclamation->setStatus("annulated before 24h");
                }
                elseif ($totalHours >= 2 && $totalHours < 24) {
                    $reclamation->setReason($reason);
                    $reclamation->setStatus("annulated");
                } else {
                    throw new \Exception('Invalid time difference');
                }
                $session->setStatus("canceled session");
                $entityManager->persist($reclamation);
                $entityManager->flush();
            }
        }        
        return $this->json(['message' => 'Reclamations registered successfully'], Response::HTTP_CREATED);
    }
  
    #[Route('/student/retard/{id}', name: 'api_rattrapage_retarS', methods: ['POST'])]
    public function retardStudent($id, Request $request, EntityManagerInterface $entityManager, SessionRepository $sessionRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
    
        $session = $sessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
    
        // Assuming getGroupeSeanceId() returns a single Group entity
        $group = $session->getGroupeSeanceId();
        if (!$group) {
            return $this->json(['error' => 'Group not found'], Response::HTTP_NOT_FOUND);
        }
    
        $students = $group->getStudents();
        $currentTime = new \DateTime('now');
    
        foreach ($students as $student) {
            $reclamation = new Reclamation();
            $reclamation->setTime($currentTime);
            $reclamation->setType("retard 5min");
            $reclamation->setStudent($student);
            $reclamation->setReason("retard");
            $reclamation->setStatus("retard");
            $entityManager->persist($reclamation);
        }
    
        $entityManager->flush();
    
        return $this->json(['message' => 'Reclamations registered successfully'], Response::HTTP_CREATED);
    }
    

    #[Route('/teacher/retard/{id}', name: 'api_rattrapage_retarT', methods: ['POST'])]
    public function retardTeacher($id, Request $request, EntityManagerInterface $entityManager,SessionRepository $sessionRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }        
        $session = $sessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
            $teacher = $session->getTeacher();
                $currentTime = new \DateTime('now');
                $reclamation = new Reclamation();
                $reclamation->setTime($currentTime);
                $reclamation->setType("retard 5min");
                $reclamation->setTeacher($teacher);
                $reclamation->setReason("retard");
                $reclamation->setStatus("retard");
                $entityManager->persist($reclamation);
                $entityManager->flush();
                 
        return $this->json(['message' => 'Reclamations registered successfully'], Response::HTTP_CREATED);
    }
}
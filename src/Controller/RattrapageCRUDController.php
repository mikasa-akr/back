<?php

namespace App\Controller;

use DateTime;
use App\Entity\Vote;
use App\Entity\Session;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Rattrapage;
use App\Repository\SessionRepository;
use App\Repository\RattrapageRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReclamationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/rattrapage',name:'api_crud_rattrapage')]
class RattrapageCRUDController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private RattrapageRepository $rattrapageRepository)
    {

    }
    #[Route('/', name: 'api_crud_reclamation_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $rattrapages = $this->rattrapageRepository->findAll();
        $data = [];
    
        foreach ($rattrapages as $rattrapage) {
            $data[] = [
                'id' => $rattrapage->getId(),
                'status' => $rattrapage->getStatus(),
                'session' => $rattrapage->getSession()->getId(),
                'time' => $rattrapage->getTime() ? $rattrapage->getTime()->format('H:i:s') : null,
                'date' => $rattrapage->getDate() ? $rattrapage->getDate()->format('Y-m-d') : null,
            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    
    #[Route('/{id}', name: 'api_crud_rattrapage_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $rattrapage = $this->rattrapageRepository->findOneBy(['id' => $id]);
    
        $data = [
            'id' => $rattrapage->getId(),
            'status' => $rattrapage->getStatus(),
            'session' => $rattrapage->getSession(),
            'time' => $rattrapage->getTime() ? $rattrapage->getTime()->format('H:i:s') : null,
            'date' => $rattrapage->getDate() ? $rattrapage->getDate()->format('Y-m-d') : null,
        ];
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'api_crud_rattrapage_delete', methods: ['DELETE'])]
    public function delete(Rattrapage $rattrapage, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($rattrapage);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    #[Route('/create/{id}', name: 'api_rattrapage_create', methods: ['POST'])]
    public function createRattrapage($id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        // Retrieve session by ID
        $session = $entityManager->getRepository(Session::class)->find($id);
        // Check if session exists
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
        // Extract data from the request
        $dateString = $data['date'];
        $timeString = $data['time'];
        $date = new DateTime($dateString);
        $time = new DateTime($timeString);
        $currentTime = new \DateTime('now');

        // Create a new rattrapage instance
        $rattrapage = new Rattrapage();
        $rattrapage->setDate($date);
        $rattrapage->setTime($time);
        $rattrapage->setDateAt($currentTime);
        $rattrapage->setStatus("scheduling");
        $rattrapage->setSession($session);
        $session->setStatus("rattrrapage scheduling");
        // Persist the rattrapage entity
        $entityManager->persist($rattrapage);
        $entityManager->flush();    
        // Return a success response
        return $this->json(['message' => 'Rattrapage created successfully']);
    }

    #[Route('/vote/{id}', name: 'api_rattrapage', methods: ['POST'])]
    public function saveVote($id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $session = $entityManager->getRepository(Session::class)->findOneBy(['id' => $id]);
        
        // Check if session exists
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
        // Save vote for the session
        $vote = new Vote();
        $vote->setDate(new \DateTime('now'));
        $vote->setAgree($data['agree']);
        $vote->setSession($session);
        
        $entityManager->persist($vote);
        
        // Flush all changes to the database
        $entityManager->flush();
        
        // Return a success response
        return $this->json([
            'message' => 'Vote saved successfully'
        ]);
    }
    
    
    #[Route('/info/{studentId}', name: 'api_rattrapage_info', methods: ['GET'])]
    public function getInfo($studentId, EntityManagerInterface $entityManager): JsonResponse
    {
        // Retrieve student by ID
        $student = $entityManager->getRepository(Student::class)->find($studentId);
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        
        $sessions = [];
        $groups = $student->getGroupe();
        
        foreach ($groups as $group) {
            $sessions[] = $group->getSessions();
        }
        
        // Extract necessary information from sessions
        $info = [];
        $currentTime = new \DateTime('now');
        
        foreach ($sessions as $session) {
            foreach ($session as $s) {
                $teacher = $s->getTeacher();
                $nameF = $teacher->getFirstName();
                $nameL = $teacher->getLastName();
                $rattrapages = $s->getRattrapages(); // Retrieve all rattrapages related to this session
                
                foreach ($rattrapages as $rattrapage) {
                    $dateR = $rattrapage->getDate();
                    $dateAtR = $rattrapage->getDateAt();
                    $timeR = $rattrapage->getTime();
                    $dateS = $s->getDateSeance();
                    $timeS = $s->getTimeStart();
                    
                    $sessionDateTime = new \DateTime($dateS->format('Y-m-d') . ' ' . $timeS->format('H:i:s'));
                    $rattrapageDateTime = new \DateTime($dateR->format('Y-m-d') . ' ' . $timeR->format('H:i:s'));
                    
                    // Calculate time difference in hours
                    $timeDiff = $currentTime->diff($dateAtR);
                    $diffHours = $timeDiff->h + ($timeDiff->days * 24);
                    
                    // Check if the time difference is less than or equal to 3 hours
                    if ($diffHours <= 3) {
                        $sessionId = $s->getId();
                        $info[] = [
                            'nameF' => $nameF,
                            'nameL' => $nameL,
                            'sessionDate' => $sessionDateTime->format('Y-m-d H:i:s'),
                            'rattrapageDate' => $rattrapageDateTime->format('Y-m-d H:i:s'),
                            'sessionId' => $sessionId,
                            'dateAtR' => $dateAtR->format('Y-m-d H:i:s'),
                        ];
                    }
                }
            }
        }
        
        return new JsonResponse($info);
    }
    
    
}
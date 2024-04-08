<?php

namespace App\Controller;

use App\Entity\FactureTeacher;
use App\Repository\FactureTeacherRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/facture/teacher',name:'api_crud_facture_teacher')]
class FactureTeacherCRUDController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private FactureTeacherRepository $factureTeacherRepository)
    {

    }
    #[Route('/', name: 'api_crud_facture_teacher_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $factures = $this->factureTeacherRepository->findAll();
        $data = [];
    
        foreach ($factures as $facture) {
            $data[] = [
                'id' => $facture->getId(),
                'status' => $facture->getStatus(),
                'teacher_id' => $facture->getTeacher() ? $facture->getTeacher()->getId() : null,
                'amount' => $facture->getAmount(),
                'datePay' => $facture->getDatePay() ? $facture->getDatePay()->format('Y-m-d H:i:s') : null,
                'dateAt' => $facture->getDateAt() ? $facture->getDateAt()->format('Y-m-d H:i:s') : null,

            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'api_crud_facture_teacher_delete', methods: ['DELETE'])]
    public function delete(FactureTeacher $factureTeacher, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($factureTeacher);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/amount/{id}', name: 'api_crud_facture_teacher', methods: ['GET'])]
    public function calculAmmount($id, SessionRepository $sessionRepository, EntityManagerInterface $entityManager): Response
    {
        // Find session by ID
        $session = $sessionRepository->find($id);
    
        // Check if session is found
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
    
        // Check if session status is 'done'
        if ($session->getStatus() !== 'done') {
            return $this->json(['message' => 'Session is not done'], Response::HTTP_OK);
        }
    
        // Get teacher, date, and calculate next month's start date
        $teacher = $session->getTeacher();
        $currentDate = new DateTime('now');
        $dateAt = new DateTime('now');
        $datePay = (clone $currentDate)->modify('first day of next month');
    
        $hourRate = $teacher->getHourlyRate();
        $timeStart = $session->getTimeStart()->format('H:i:s');
        $timeEnd = $session->getTimeEnd()->format('H:i:s');
    
        // Calculate the difference in hours
        $start = strtotime($timeStart);
        $end = strtotime($timeEnd);
        $difference = abs($end - $start) / 3600;
    
        // Calculate the amount
        $amount = $hourRate * $difference;
    
        // Create a new FactureTeacher
        $facture = new FactureTeacher();
        $facture->setDateAt($dateAt);
        $facture->setDatePay($datePay);
        $facture->setTeacher($teacher);
        $facture->setAmount($amount);
        $facture->setStatus("not payed");
        $session->setFactureTeacher($facture); // Set the session to the facture
    
        // Persist and flush changes to the database
        $entityManager->persist($facture);
        $entityManager->flush();
    
        // Return success response
        return $this->json(['message' => 'Registered Successfully'], Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'api_crud_facture_teacher_show', methods: ['GET'])]
    public function facture($id): JsonResponse
    {
        $factures = $this->factureTeacherRepository->findBy(['teacher' => $id]);
        $total = 0;
        $data = [];
        foreach ($factures as $facture) {
            $factureAmount = $facture->getAmount();
            $total += $factureAmount;
            $data[] = [
                'id' => $facture->getId(),
                'status' => $facture->getStatus(),
                'amount' => $factureAmount,
                'datePay' => $facture->getDatePay() ? $facture->getDatePay()->format('Y-m-d') : null,
                'dateAt' => $facture->getDateAt() ? $facture->getDateAt()->format('Y-m-d H:i:s') : null,
            ];
        }
        
        $responseData = [
            'factures' => $data,
            'total' => $total,
        ];
    
        return new JsonResponse($responseData, Response::HTTP_OK);
    }
    
}
<?php

namespace App\Controller;

use App\Entity\Notification;
use DateTime;
use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\Date;

#[Route('api/facture/student',name:'api_crud_facture_student')]
class FactureStudentCRUDController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private PaymentRepository $paymentRepository)
    {

    }
    #[Route('/', name: 'api_crud_facture_student_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $factures = $this->paymentRepository->findAll();
        $data = [];

        foreach ($factures as $facture) {
            $data[] = [
                'id' => $facture->getId(),
                'status' => $facture->getStatus(),
                'student_id' => $facture->getStudent() ? $facture->getStudent()->getEmail(): null,
                'amount' => $facture->getAmount(),
                'datePay' => $facture->getDatePay() ? $facture->getDatePay()->format('Y-m-d H:i:s') : null,
                'file' => $facture->getTransactionFile(),
                'method' => $facture->getMethode(),
                'stripeCard' => $facture->getCardNumber()
            ];
        }
        usort($data, function($a, $b) {
            return $b['datePay'] <=> $a['datePay'];
        });
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    #[Route('/{id}', name: 'api_crud_facture_student_delete', methods: ['DELETE'])]
    public function delete(Payment $payment, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($payment);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    #[Route('/{id}', name: 'api_crud_facture_student_show', methods: ['GET'])]
    public function facture($id): JsonResponse
    {
        $factures = $this->paymentRepository->findBy(['student' => $id]);
        $data = [];
        foreach ($factures as $facture) {
            $data[] = [
                'id' => $facture->getId(),
                'status' => $facture->getStatus(),
                'amount' => $facture->getAmount(),
                'datePay' => $facture->getDatePay() ? $facture->getDatePay()->format('Y-m-d') : null,
                'file' => $facture->getTransactionFile(),
                'method' => $facture->getMethode(),
                'stripeCard' => $facture->getCardNumber()
            ];
        }
        $responseData = [
            'factures' => $data,
        ];
        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/update_status/{id}', name: 'update_status', methods: ['PUT'])]
    public function updateStatus(EntityManagerInterface $entityManager, int $id,Request $request): JsonResponse
    {
        $payment = $entityManager->getRepository(Payment::class)->find($id);
        $student = $payment->getStudent();
        if (!$payment) {
            return new JsonResponse(['error' => 'payment not found'], 404);
        }

        $pay = $payment ;
        $date = new DateTime('now');

        $notification = new Notification();
        $notification->setContent('your payment work successfully');
        $notification->setStudent($student);
        $notification->setSentAt($date);

        $pay->setStatus("payed");
        $entityManager->flush();

        return new JsonResponse(['message' => 'Status updated successfully']);
    }
    
}
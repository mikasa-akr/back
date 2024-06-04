<?php

namespace App\Controller;

use DateTime;
use Psr\Log\LoggerInterface;
use App\Entity\FactureTeacher;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FactureTeacherRepository;
use App\Repository\TeacherRepository;
use Symfony\Component\HttpFoundation\Request;
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
            usort($data, function($a, $b) {
                return $b['datePay'] <=> $a['datePay'];
            });
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

    #[Route('/complete/{id}', name: 'api_crud_facture_teacher', methods: ['POST'])]
    public function calculAmmount($id, SessionRepository $sessionRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $sessionRepository->find($id);
    
        // Get teacher, date, and calculate next month's start date
        $teacher = $session->getTeacher();
        $dateAt = new DateTime('now');
    
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
        $facture->setTeacher($teacher);
        $datePay = new DateTime();
        $datePay->setTimestamp(0);
        $facture->setDatePay($datePay);      
        $facture->setAmount($amount);
        $facture->setCardNumber("");
        $facture->setStatus("not payed");
        $session->setFactureTeacher($facture);
        $session->setStatus("done");
    
        // Persist and flush changes to the database
        $entityManager->persist($facture);
        $entityManager->persist($session);
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
                'dateAt' => $facture->getDateAt() ? $facture->getDateAt()->format('Y-m-d') : null,
            ];
        }
        
        $responseData = [
            'factures' => $data,
            'total' => $total,
        ];
    
        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/payment/teacher/{teacherId}', name: 'api_payment_teacher', methods: ['POST'])]
    public function processPayment($teacherId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $requestData = json_decode($request->getContent(), true); // Decode JSON request data
    
        // Extract factureIds from the request data
        $factureIds = $requestData['factureIds'] ?? [];
    
        if (empty($factureIds)) {
            return $this->json(['error' => 'No factures selected for payment'], Response::HTTP_BAD_REQUEST);
        }
    
        // Fetch selected factures based on IDs
        $teacherFactures = $this->factureTeacherRepository->findBy(['teacher' => $teacherId, 'id' => $factureIds]);
    
        if (empty($teacherFactures)) {
            return $this->json(['error' => 'No factures found for the selected IDs'], Response::HTTP_NOT_FOUND);
        }
    
        try {
            // Calculate total amount of selected factures
            $total = 0;
            foreach ($teacherFactures as $facture) {
                $total += $facture->getAmount();
            }
    
            // Stripe payment processing
            \Stripe\Stripe::setApiKey($_ENV["STRIPE_SECRET"]);
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $total * 100, // Convert to cents
                'currency' => 'usd',
                'description' => 'Course Payment',
                'confirm' => true, // Confirm the PaymentIntent immediately
                'payment_method' => 'pm_card_visa', // Example payment method ID (replace with actual payment method ID)
                'return_url' => 'https://dashboard.stripe.com/test/dashboard', // Specify the return URL for the payment
            ]);
    
            // Update facture entities with payment details
            foreach ($teacherFactures as $facture) {
                $paymentMeth = \Stripe\PaymentMethod::retrieve($paymentIntent->payment_method);
                $cardNumber = $paymentMeth->card->last4;
                $maskedCardNumber = "**** **** **** " . $cardNumber;
                $facture->setCardNumber($maskedCardNumber);
                $facture->setStatus("payed");
                $datePay= new DateTime('now');
                $facture->setDatePay($datePay);
    
                // Persist facture entity
                $entityManager->persist($facture);
            }
    
            // Flush changes to database
            $entityManager->flush();
    
            // Return success response
            return $this->json([
                'message' => 'Factures paid successfully',
                'clientSecret' => $paymentIntent->client_secret,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            // Handle payment failure
            return $this->json(['error' => 'Payment failed: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
         
}
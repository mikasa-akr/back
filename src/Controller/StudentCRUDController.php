<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Forfait;
use App\Entity\Payment;
use App\Entity\Student;
use App\Repository\PaymentRepository;
use App\Service\StripeService;
use App\Repository\StudentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
#[Route('api/crud/student',name: 'api_crud_student')]
class StudentCRUDController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private StudentRepository $studentRepository)
    {

    }
    
    #[Route('/', name: 'api_crud_student_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $students = $this->studentRepository->findAll();
        $data = [];
    
        foreach ($students as $student) {
            if (in_array('ROLE_ADMIN', $student->getRoles(), true)) {
                continue; // Skip this admin
            }
    
            // Retrieve courses for the student
            $courses = $student->getCourse(); 
            $courseTypes = []; // Initialize array to store course types
    
            foreach ($courses as $course) {
                $courseTypes[] = $course->getType(); // Add course type to the array
            }    
            $data[] = [
                'id'=> $student->getId(),
                'firstName'=> $student->getFirstName(),
                'lastName'=> $student->getLastName(),
                'email'=> $student->getEmail(),
                'avatar'=> $student->getAvatar(),
                'gender'=> $student->getGender(),
                'number'=> $student->getNumber(),
                'age'=> $student->getAge(),
                'status'=> $student->getStatus(),
                'forfait_id' => $student->getForfait()->getTitle(),
                'subscription' => $student->getForfait()->getSubscription(),
                'course_types' => $courseTypes,
            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/{id}/edit', name: 'api_crud_student_edit', methods: ['PUT'])]
    public function update($id, Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        $student = $this->studentRepository->findOneBy(['id' => $id]);
        $data = json_decode($request->getContent(), true);
    
        empty($data['firstName']) ? true : $student->setFirstName($data['firstName']);
        empty($data['lastName']) ? true : $student->setLastName($data['lastName']);
        empty($data['email']) ? true : $student->setEmail($data['email']);
        empty($data['avatar']) ? true : $student->setAvatar($data['avatar']);
        empty($data['gender']) ? true : $student->setGender($data['gender']);
        empty($data['number']) ? true : $student->setNumber($data['number']);
        empty($data['password']) ? true : $student->setPassword($data['password']);
        empty($data['age']) ? true : $student->setAge($data['age']);

        if (!empty($data['forfait_id'])) {
            $forfaitId = $data['forfait_id'];
            $forfait = $entityManager->getRepository(Forfait::class)->find($forfaitId);
            if (!$forfait) {
                return new JsonResponse(['error' => 'forfait not found'], Response::HTTP_NOT_FOUND);
            }
            $student->setForfait($forfait);
        }

        if (!empty($data['course_id'])) {
            $courseId = $data['course_id'];
            $course = $entityManager->getRepository(Course::class)->find($courseId);
            if (!$course) {
                return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
            }
            $student->addCourse($course);
        }
        $updatedstudent = $this->studentRepository->updatestudent($student);
    
        return new JsonResponse($updatedstudent->toArray(), Response::HTTP_OK);
    }
    
    #[Route('/{id}', name: 'api_crud_student_delete', methods: ['DELETE'])]
    public function delete(Student $student, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($student);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/Groupes/{id}', name: 'teacher_groups', methods: ['GET'])]
    public function getGroups(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
{
    // Find the teacher by ID
    $student = $this->studentRepository->findOneBy(['id' => $id]);
    if (!$student) {
        return $this->json(['error' => 'student not found'], Response::HTTP_NOT_FOUND);
    }

    // Get all groups associated with the student
    $groups = $student->getGroupe();

    // Serialize the groups
    $data = $serializer->serialize($groups, 'json', [
        'groups' => ['group_list'],
    ]);

    return new JsonResponse($data, Response::HTTP_OK, [], true);
}

#[Route('/payment/student/{id}', name: 'api_payment_student', methods: ['POST'])]
public function processPayment($id, Request $request, EntityManagerInterface $entityManager,LoggerInterface $logger): Response
{
    $requestData = $request->getContent();

    // Log the content of the request
    $logger->debug('Request data: '.$requestData);

    // Attempt to decode the JSON payload
    $data = json_decode($requestData, true);

    $student = $this->studentRepository->findOneBy(['id' => $id]);
    if (!$student) {
        return $this->json(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
    }

    $paymentMethod = $request->request->get('method');
    $amount = $request->request->get('amount');
    $fileTransaction = $request->files->get('fileTransaction');

    $datePay = new \DateTime('now');

    $payment = new Payment();
    $payment->setDatePay($datePay);
    $payment->setMethode($paymentMethod);
    $payment->setStudent($student);
    $payment->setAmount($amount);

    try {
        // Use Stripe library directly
        \Stripe\Stripe::setApiKey($_ENV["STRIPE_SECRET"]);

        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount * 100, // Convert to cents
            'currency' => 'usd',
            'description' => 'Course Payment',
            'confirm' => true, // Confirm the PaymentIntent immediately
            'payment_method' => 'pm_card_visa', // Example payment method ID (replace with actual payment method ID)
            'return_url' => 'https://dashboard.stripe.com/test/dashboard', // Specify the return URL for the payment
        ]);

        // Process payment based on payment method
             if ($paymentMethod === 'stripeCard') {
                $paymentMeth = \Stripe\PaymentMethod::retrieve($paymentIntent->payment_method);
            $cardNumber = $paymentMeth->card->last4;
            $maskedCardNumber = "**** **** **** " . $cardNumber;
            $payment->setCardNumber($maskedCardNumber);
            $payment->setTransactionFile("");
            $payment->setStatus("payed");   
             } elseif ($paymentMethod === 'transaction') {
                if ($fileTransaction) {
                    // Move uploaded file to a directory
                    $fileName = md5(uniqid()) . '.' . $fileTransaction->guessExtension();
                    $fileTransaction->move(
                        $this->getParameter('PFE'),
                        $fileName
                    );
                    $payment->setTransactionFile($fileName);
                    $payment->setCardNumber(" ");
                    $payment->setStatus("not payed");
                } else {
                    return $this->json(['error' => 'File transaction is missing'], Response::HTTP_BAD_REQUEST);
                }
        } else {
            return $this->json(['error' => 'Invalid payment method'], Response::HTTP_BAD_REQUEST);
        }

        // Persist payment entity with client secret
        $entityManager->persist($payment);
        $entityManager->flush();

        // Return success message with client secret for frontend capture
        return $this->json([
            'message' => 'Payment initiated successfully',
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    } catch (\Exception $e) {
        return $this->json(['error' => 'Payment failed: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
    }
}

#[Route('/restHour/{id}', name: 'api_crud_hour_rest', methods: ['GET'])]
public function restHours($id, StudentRepository $studentRepository, EntityManagerInterface $entityManager): Response
{
    $student = $studentRepository->find($id);
    if (!$student) {
        return $this->json(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
    }
    $forfait = $student->getForfait()->getNbrHourSession();
    $groups = $student->getGroupe();
    $totalDifference = 0;
    foreach ($groups as $group) {
        $sessions = $group->getSessions();
        foreach ($sessions as $session) {
            if ($session->getStatus() === 'done'){
            $timeStart = $session->getTimeStart()->format('H:i:s');
            $timeEnd = $session->getTimeEnd()->format('H:i:s');
            $start = strtotime($timeStart);
            $end = strtotime($timeEnd);
            $difference = abs($end - $start) / 3600;
            $totalDifference += $difference;
        }
    }
    }
    $restHour = $forfait - $totalDifference;
    return $this->json($restHour, Response::HTTP_OK);
}

#[Route('/total', name: 'api_crud_hour_total', methods: ['GET'])]
public function TotalStudent(StudentRepository $studentRepository): Response
{
    $students = $studentRepository->findAll();
    $currentTime = new DateTime('now');
    $total = 0;
    
    foreach ($students as $student) {
        // Get the date at of the student and format it to include only the date component
        $dateAt = $student->getDateAt()->format("Y-m");
    
        // Format the current time to include only the date component
        $currentDate = $currentTime->format("Y-m");
    
        // Compare dates (date component only)
        if ($currentDate === $dateAt) {
            $total++;
        }
    }

    return $this->json($total, Response::HTTP_OK);
}

#[Route('/total/payment', name: 'api_crud_payment_total', methods: ['GET'])]
public function TotalPayment(PaymentRepository $paymentRepository): Response
{
    $payments = $paymentRepository->findAll();
    $totalPay = 0;
    $totalNotPay = 0;
    foreach ($payments as $payment) {
        $status = $payment->getStatus();
        if($status === 'payed'){
            $totalPay++;
        }elseif($status === 'not payed'){
            $totalNotPay++;
        }
    }

    return $this->json(["totalPay"=>$totalPay,"totalNotPay"=>$totalNotPay], Response::HTTP_OK);
}

}

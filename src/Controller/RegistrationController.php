<?php

namespace App\Controller;

use DateTime;
use Stripe\Stripe;
use App\Entity\Group;
use App\Entity\Course;
use App\Entity\Gender;
use App\Entity\Forfait;
use App\Entity\Payment;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Notification;
use Psr\Log\LoggerInterface;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Stripe\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[Route('/api', name: 'api_register')]
class RegistrationController extends AbstractController
{
    #[Route('/signUp/teacher', name: 'api_register_teacher', methods: ['POST'])]
    public function registerTeacher(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $em = $doctrine->getManager();
        $data = json_decode($request->getContent(), true);
        
        // Check if required fields are present
        $requiredFields = ['email', 'password', 'first_name', 'last_name', 'number', 'gender'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return $this->json(['error' => 'Missing required field: ' . $field], Response::HTTP_BAD_REQUEST);
            }
        }
        $email = $request->request->get('email');
        $plaintextPassword = $request->request->get('password');
        $firstName = $request->request->get('first_name');
        $lastName = $request->request->get('last_name');
        $number =$request->request->get('number');
        $registeredAt=new DateTime('now');
        $avatarFile = $request->files->get('avatar');

        $avatarFileName = null;
        if ($avatarFile) {
            // Move uploaded file to a directory
            $avatarFileName = md5(uniqid()) . '.' . $avatarFile->guessExtension();
            $avatarFile->move($this->getParameter('PFE'), $avatarFileName);
        }
        $courseId = $request->request->get('course_id');
        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course) {
            return $this->json(['error' => 'course not found'], Response::HTTP_NOT_FOUND);
        }

        $genderId = $request->request->get('gender_id');
        $gender = $entityManager->getRepository(Gender::class)->find($genderId);
        if (!$gender) {
            return $this->json(['error' => 'gender not found'], Response::HTTP_NOT_FOUND);
        }
        $price=$course->getPrice();
    
        $teacher = new Teacher();
        $hashedPassword = $passwordHasher->hashPassword($teacher, $plaintextPassword);
        $teacher->setPassword($hashedPassword);
        $teacher->setEmail($email);
        $teacher->setRoles(['ROLE_TEACHER']);
        $teacher->setFirstName($firstName);
        $teacher->setLastName($lastName);
        $teacher->setRegisteredAt($registeredAt);
        $teacher->setNumber($number);
        $teacher->setGender($gender);
        $teacher->setAvatar($avatarFileName);
        $teacher->setCourse($course);
        $teacher->setStatus("offline");
        $teacher->setHourlyRate($price);

        $studentId = 1; // Assuming the ID of the student you want to associate with the notification
        $student = $entityManager->getRepository(Student::class)->find($studentId);

        $date = new DateTime('now');
        $notification = new Notification();
        $notification->setContent('new teacher added');
        $notification->setStudent($student);
        $notification->setSentAt($date);
        
        $em->persist($teacher);
        $em->persist($notification);
        $em->flush();
    
        // Optionally, return the data of the newly registered teacher
        return $this->json(['message' => 'Registered Successfully', 'teacher' => $teacher->toArray()]);
    }

    #[Route('/signUp/student', name: 'api_register_student', methods: ['POST'])]
    public function registerStudent(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, LoggerInterface $logger): Response {
        // Extract form data from the request
        $plaintextPassword = $request->request->get('password');
        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $email = $request->request->get('email');
        $number = $request->request->get('number');
        $age = $request->request->get('age');
        $avatarFile = $request->files->get('avatar');
        $dateAt = new \DateTime('now');
        
        // Validate form data (optional)
    
        // Handle avatar upload
        $avatarFileName = null;
        if ($avatarFile) {
            // Move uploaded file to a directory
            $avatarFileName = md5(uniqid()) . '.' . $avatarFile->guessExtension();
            $avatarFile->move($this->getParameter('PFE'), $avatarFileName);
        }
    
        // Extract forfait data from the request
        $forfaitId = $request->request->get('forfait_id');
        $forfait = $entityManager->getRepository(Forfait::class)->find($forfaitId);
        if (!$forfait) {
            return $this->json(['error' => 'forfait not found'], Response::HTTP_NOT_FOUND);
        }
    
        $courseIds = $request->request->get('course_ids');
        $courseIds = json_decode($courseIds, true); // Decode JSON string to a PHP array
        if (!$courseIds) {
            return $this->json(['error' => 'course IDs are required'], Response::HTTP_BAD_REQUEST);
        }

        $genderId = $request->request->get('gender_id');
        $gender = $entityManager->getRepository(Gender::class)->find($genderId);
        if (!$gender) {
            return $this->json(['error' => 'gender not found'], Response::HTTP_NOT_FOUND);
        }
        // Retrieve course entities
        $courses = [];
        foreach ($courseIds as $courseId) { // Iterate through decoded array
            $course = $entityManager->getRepository(Course::class)->find($courseId);
            if (!$course) {
                return $this->json(['error' => 'course not found for ID: ' . $courseId], Response::HTTP_NOT_FOUND);
            }
            $courses[] = $course;
        }
        
    
        // Check if a student with the given email already exists
        $existingStudent = $entityManager->getRepository(Student::class)->findOneBy(['email' => $email]);
        
        if ($existingStudent) {
            // Update the existing student entity with the new course(s)
            foreach ($courses as $course) {
                $existingStudent->addCourse($course);
            }
            $entityManager->persist($existingStudent);
        } else {
            // Create a new student entity
            $student = new Student();
            $hashedPassword = $passwordHasher->hashPassword($student, $plaintextPassword);
            $student->setPassword($hashedPassword);
            $student->setEmail($email);
            $student->setRoles(['ROLE_STUDENT']);
            $student->setFirstName($firstName);
            $student->setLastName($lastName);
            $student->setDateAt($dateAt);
            $student->setNumber($number);
            $student->setGender($gender);
            $student->setAvatar($avatarFileName); // Set avatar file name
            $student->setAge($age);
            $student->setStatus("offline");
            $student->setForfait($forfait);
            foreach ($courses as $course) {
                $student->addCourse($course);
            }
            $entityManager->persist($student);
        }
    
        // Process Payment
        $paymentMethod = $request->request->get('method');
        $amount = $forfait->getPrice();
        $fileTransaction = $request->files->get('fileTransaction');
    
        $datePay = new \DateTime('now');
    
        $payment = new Payment();
        $payment->setDatePay($datePay);
        $payment->setMethode($paymentMethod); 
        $payment->setAmount($amount);  
        if (isset($student)) {
            $payment->setStudent($student);
        }
    
        try {
            // Use Stripe library directly
            \Stripe\Stripe::setApiKey($_ENV["STRIPE_SECRET"]);
    
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'description' => 'Course Payment',
                //'confirm' => true, // Confirm the PaymentIntent immediately
                'payment_method' => 'pm_card_visa', // Example payment method ID (replace with actual payment method ID)
                //'return_url' => 'https://dashboard.stripe.com/test/dashboard', // Specify the return URL for the payment
            ]);
    
            // Process payment based on payment method
            if ($paymentMethod === 'stripeCard') {
                $paymentMeth = \Stripe\PaymentMethod::retrieve($paymentIntent->payment_method);
                $cardNumber = $paymentMeth->card->last4;
                $maskedCardNumber = "**** **** **** " . $cardNumber;
                $payment->setCardNumber($maskedCardNumber);
                $payment->setTransactionFile("0.png");
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

            $studentIdA = 1; // Assuming the ID of the student you want to associate with the notification
            $studentA = $entityManager->getRepository(Student::class)->find($studentIdA);

            $date = new DateTime('now');
            $notification = new Notification();
            $notification->setContent('new student added');
            $notification->setStudent($studentA);
            $notification->setSentAt($date);

            $entityManager->persist($payment);
            $entityManager->persist($notification);
            $entityManager->flush();
    
            $studentId = isset($student) ? $student->getId() : $existingStudent->getId();

            return $this->json([
                'message' => 'Payment initiated successfully',
                'clientSecret' => $paymentIntent->client_secret,
                'studentId' => $studentId,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Payment failed: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    
         
    #[Route('/signUp/forfait', name: 'api_register_forfait', methods: ['POST'])]
    public function registerForfait(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {

        $data = json_decode($request->getContent(), true);
    

        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
    
        // Extract data from the JSON payload
        $title = $data['title'] ?? null;
        $price = $data['price'] ?? null;
        $nbHSeance = $data['NbrHourSeance'] ?? null;
        $nbHSession = $data['NbrHourSession'] ?? null;
        $subscription = $data['subscription'] ?? null; 

        // Create a new Forfait entity and set its properties
        $forfait = new Forfait();
        $forfait->setTitle($title);
        $forfait->setPrice($price);
        $forfait->setNbrHourSeance($nbHSeance);
        $forfait->setNbrHourSession($nbHSession);
        $forfait->setSubscription($subscription);
    
        // Persist the Forfait entity
        $entityManager->persist($forfait);
        $entityManager->flush();
    
        return $this->json(['message' => 'Forfait registered successfully'], Response::HTTP_CREATED);
    }

    #[Route('/course', name: 'api_cours', methods: ['GET'])]
    public function getCourses( EntityManagerInterface $entityManager): JsonResponse
    {
        $courses = $entityManager->getRepository(Course::class)->findAll();

        $formattedCourses = [];
        foreach ($courses as $course) {
            $formattedCourses[] = [
                'id' => $course->getId(),
                'type' => $course->getType(),
                'price' => $course->getPrice()
            ];
        }
        return new JsonResponse($formattedCourses, Response::HTTP_OK);
    }

    #[Route('/gender', name: 'api_genders', methods: ['GET'])]
    public function getGenders( EntityManagerInterface $entityManager): JsonResponse
    {
        $genders = $entityManager->getRepository(Gender::class)->findAll();

        $formattedgenders = [];
        foreach ($genders as $gender) {
            $formattedgenders[] = [
                'id' => $gender->getId(),
                'name' => $gender->getName(),
            ];
        }
        return new JsonResponse($formattedgenders, Response::HTTP_OK);
    }

    #[Route('/update_status/{id}', name: 'update_status', methods: ['PUT'])]
    public function updateStatus(EntityManagerInterface $entityManager, int $id,Request $request): JsonResponse
    {
        $student = $entityManager->getRepository(Student::class)->find($id);
        $teacher = $entityManager->getRepository(Teacher::class)->find($id);

        if (!$student && !$teacher) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $user = $student ?: $teacher;

        // Assuming the request body contains the new status
        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        $user->setStatus("offline");
        $entityManager->flush();

        return new JsonResponse(['message' => 'Status updated successfully']);
    }


    #[Route('/total', name: 'api_crud_hour_total', methods: ['GET'])]
public function TotalTeacher(TeacherRepository $teacherRepository): Response
{
    $teachers = $teacherRepository->findAll();
    $currentTime = new DateTime('now');
    $total = 0;
    if (empty($teachers)) {
        // Handle the case of no teachers found (e.g., return a specific response or log a message)
        return $this->json(['message' => 'No teachers found in the current month'], Response::HTTP_NOT_FOUND);
    }
    foreach ($teachers as $teacher) {
        // Ensure $teacher->getRegisteredAt() is not null before accessing its properties
        if ($teacher->getRegisteredAt() !== null) {
            // Get the date at of the teacher and format it to include only the date component
            $dateAt = $teacher->getRegisteredAt()->format("Y-m");
        
            // Format the current time to include only the date component
            $currentDate = $currentTime->format("Y-m");
        
            // Compare dates (date component only)
            if ($currentDate === $dateAt) {
                $total++;
            }
        }
    }

    return $this->json($total, Response::HTTP_OK);
}

}
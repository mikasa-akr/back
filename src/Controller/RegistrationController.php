<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Group;
use App\Entity\Course;
use App\Entity\Forfait;
use App\Entity\Student;
use App\Entity\Teacher;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Stripe\Exception\InvalidRequestException;
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
        Stripe::setApiKey('pk_test_51OyasWBNWgwGqFvzzcG0jn80B1lHgihqrkhbRdcCvexIeAZcLur7KbRpipxkKC9DTkO1xhsLehILhrBNm8Wi9ep400Td1NEJiI');
        $email = $data['email'];
        $plaintextPassword = $data['password'];
        $firstName = $data['first_name'];
        $lastName = $data['last_name'];
        $number = $data['number'];
        $gender = $data['gender'];
        $registeredAt=new DateTime('now');
        $avatar = $data['avatar'] ?? null;
        $cardNumber = $data['cardNumber'] ;
        try {
            $card = \Stripe\Token::create([
                'card' => [
                    'number' => $cardNumber,
                    'exp_month' => 12,  // Replace with actual month (1-12)
                    'exp_year' => 2024, // Replace with actual year
                ],
            ]);
        } catch (InvalidRequestException $e) {
            // Handle invalid card number error
            return $this->json(['error' => 'Invalid card number'], Response::HTTP_BAD_REQUEST);
        }
        if (!empty($data['course_id'])) {
            $courseId = $data['course_id'];
            $course = $entityManager->getRepository(Course::class)->find($courseId);
            if (!$course) {
                return $this->json(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
            }
        } else {
            return $this->json(['error' => 'Course ID is required'], Response::HTTP_BAD_REQUEST);
        }
    
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
        $teacher->setAvatar($avatar);
        $teacher->setCourse($course);
        $teacher->setStatus("offline");
        $teacher->setCardNumber($cardNumber);
        $teacher->setHourlyRate(30);
        $em->persist($teacher);
        $em->flush();
    
        // Optionally, return the data of the newly registered teacher
        return $this->json(['message' => 'Registered Successfully', 'teacher' => $teacher->toArray()]);
    }

    #[Route('/signUp/student', name: 'api_register_student', methods: ['POST'])]
    public function registerStudent(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['message' => 'Invalid JSON payload'], 400);
        }
        $email = $data['email'] ?? null;
        $plaintextPassword = $data['password'] ?? null;
        $firstName = $data['firstName'] ?? null;
        $lastName = $data['lastName'] ?? null;
        $number = $data['number'] ?? null;
        $gender = $data['gender'] ?? null;
        $avatar = $data['avatar'] ?? null;
        $age = $data['age'] ?? null;
    
        // Extract forfait data from the request
        if (empty($data['forfait_id'])) {
            return $this->json(['error' => 'forfait ID is required'], Response::HTTP_BAD_REQUEST);
        }
        $forfaitId = $data['forfait_id'];
        $forfait = $entityManager->getRepository(Forfait::class)->find($forfaitId);
        if (!$forfait) {
            return $this->json(['error' => 'forfait not found'], Response::HTTP_NOT_FOUND);
        }
    
        // Extract course data from the request
        if (empty($data['course_ids']) || !is_array($data['course_ids'])) {
            return $this->json(['error' => 'course IDs are required and must be an array'], Response::HTTP_BAD_REQUEST);
        }
        $courseIds = $data['course_ids'];
        $courses = [];
        foreach ($courseIds as $courseId) {
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
            $student = new Student();
            $hashedPassword = $passwordHasher->hashPassword($student, $plaintextPassword);
            $student->setPassword($hashedPassword);
            $student->setEmail($email);
            $student->setRoles(['ROLE_STUDENT']);
            $student->setFirstName($firstName);
            $student->setLastName($lastName);
            $student->setNumber($number);
            $student->setGender($gender);
            $student->setAvatar($avatar);
            $student->setAge($age);
            $student->setStatus("offline");
            $student->setForfait($forfait);
            foreach ($courses as $course) {
                $student->addCourse($course);
            }
            $entityManager->persist($student);
        }
    
        $entityManager->flush();
        
        return $this->json(['message' => 'Registered Successfully']);
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
            ];
        }
        return new JsonResponse($formattedCourses, Response::HTTP_OK);
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
}
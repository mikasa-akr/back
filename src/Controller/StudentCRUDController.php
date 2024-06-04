<?php

namespace App\Controller;

use DateTime;
use App\Entity\Course;
use App\Entity\Gender;
use App\Entity\Forfait;
use App\Entity\Payment;
use App\Entity\Student;
use Psr\Log\LoggerInterface;
use App\Service\StripeService;
use App\Repository\PaymentRepository;
use App\Repository\SessionRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
                $courseTypes[] = $course->getType().' '; // Add course type to the array
            }    
            $data[] = [
                'id'=> $student->getId(),
                'firstName'=> $student->getFirstName(),
                'lastName'=> $student->getLastName(),
                'email'=> $student->getEmail(),
                'avatar'=> $student->getAvatar(),
                'gender'=> $student->getGender()->getName(),
                'number'=> $student->getNumber(),
                'age'=> $student->getAge(),
                'status'=> $student->getStatus(),
                'forfait_id' => $student->getForfait()->getTitle(),
                'subscription' => $student->getForfait()->getSubscription(),
                'course_types' => $courseTypes,
                'gender_id' => $student->getGender()->getId()
            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_crud_student_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $student = $this->studentRepository->findOneBy(['id' => $id]);
        
        if (!$student) {
            return new JsonResponse(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
    
        $courses = $student->getCourse();
        $courseTypes = [];
    
        foreach ($courses as $course) {
            $courseTypes[] = $course->getType();
        }    
    
        $data = [
            'id' => $student->getId(),
            'firstName' => $student->getFirstName(),
            'lastName' => $student->getLastName(),
            'email' => $student->getEmail(),
            'avatar' => $student->getAvatar(),
            'gender' => $student->getGender()->getName(),
            'number' => $student->getNumber(),
            'age' => $student->getAge(),
            'status' => $student->getStatus(),
            'forfait_id' => $student->getForfait()->getTitle(),
            'subscription' => $student->getForfait()->getSubscription(),
            'course_types' => $courseTypes,
        ];
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    

    #[Route('/{id}/edit', name: 'api_crud_student_edit', methods: ['PUT'])]
    public function update($id, Request $request,EntityManagerInterface $entityManager,UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $student = $this->studentRepository->findOneBy(['id' => $id]);
        $data = json_decode($request->getContent(), true);
    
        empty($data['firstName']) ? true : $student->setFirstName($data['firstName']);
        empty($data['lastName']) ? true : $student->setLastName($data['lastName']);
        empty($data['email']) ? true : $student->setEmail($data['email']);
        empty($data['avatar']) ? true : $student->setAvatar($data['avatar']);
        empty($data['number']) ? true : $student->setNumber($data['number']);
        if (isset($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($student, $data['password']);
            $student->setPassword($hashedPassword);
        }   
            empty($data['age']) ? true : $student->setAge($data['age']);

        if (!empty($data['forfait_id'])) {
            $forfaitId = $data['forfait_id'];
            $forfait = $entityManager->getRepository(Forfait::class)->find($forfaitId);
            if (!$forfait) {
                return new JsonResponse(['error' => 'forfait not found'], Response::HTTP_NOT_FOUND);
            }
            $student->setForfait($forfait);
        }

        if (!empty($data['gender'])) {
            $genderId = $data['gender'];
            $gender = $entityManager->getRepository(Gender::class)->find($genderId);
            if (!$gender) {
                return new JsonResponse(['error' => 'gender not found'], Response::HTTP_NOT_FOUND);
            }
            $student->setGender($gender);
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
    
        // Serialize each group along with the required information
        $serializedGroups = [];
        foreach ($groups as $group) {
            $serializedGroups[] = [
                'id' => $group->getId(),
                'avatar' => $group->getAvatar(),
                'type' => $group->getType(),      
                'name' => $group->getName(),
                'teacher' =>$group->getTeach()->getEmail(),      
            ];
        }
    
        // Serialize the groups
        $data = $serializer->serialize($serializedGroups, 'json');
    
        return new JsonResponse($data, Response::HTTP_OK, [], true);
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

#[Route('/total/session/{id}', name: 'api_crud_session_total', methods: ['GET'])]
public function TotalSession($id): Response
{
    // Find sessions where the teacher ID matches the provided $id
    $students = $this->studentRepository->findBy(['id'=>$id]);

    $totalsessions = 0;
    foreach($students as $g){
        $groups=$g->getGroupe();
        foreach($groups as $group){
            $sessions=$group->getSessions();
            foreach($sessions as $session){
                    $totalsessions ++;
            }
        }
    }

    return $this->json(['totalsessions' => $totalsessions], Response::HTTP_OK);
}

#[Route('/total/session/count/{id}', name: 'api_crud_session_count', methods: ['GET'])]
public function SessionCount($id, SessionRepository $sessionRepository): Response
{
    $students = $this->studentRepository->findOneBy(['id' => $id]);
    $totalsessionsC = 0;
    $groups = $students->getGroupe();
foreach($groups as $group){
    $sessions = $group->getSessions();
    foreach ($sessions as $session) {
        if ($session->getStatus() === 'done') {
            $totalsessionsC++;
        }
    }
}

    return $this->json([
        'totalsessionsDone' => $totalsessionsC,
    ], Response::HTTP_OK);
}



#[Route('/total/session/next/{id}', name: 'api_crud_session_next', methods: ['GET'])]
public function NextSession($id): JsonResponse
{
    // Find a single student by ID
    $student = $this->studentRepository->find($id);

    if (!$student) {
        return $this->json(['message' => 'Student not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    $totalsessions = 0;
    $closestSession = null;
    $closestSessionDiff = null;
    $currentTimestamp = (new \DateTime())->getTimestamp();

    foreach ($student->getGroupe() as $group) {
        foreach ($group->getSessions() as $session) {
            $totalsessions++;
            $sessionDate = $session->getDateSeance();
            $sessionTimestamp = $sessionDate->getTimestamp();
            $diff = abs($sessionTimestamp - $currentTimestamp) / (60 * 60 * 24); // Convert seconds to days
            if ($closestSessionDiff === null || $diff < $closestSessionDiff) {
                $closestSession = $session->getDateSeance()->format("Y-m-d");
                $closestSessionDiff = $diff;
            }
        }
    }

    // Prepare response data
    $responseData = [
        'closestSession' => $closestSession
    ];
    return $this->json($responseData, JsonResponse::HTTP_OK, [], ['groups' => 'group_list']);
}



}

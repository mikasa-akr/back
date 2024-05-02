<?php

namespace App\Controller;

use DateTime;
use App\Entity\Group;
use App\Entity\Course;
use App\Entity\Gender;
use App\Entity\Teacher;
use App\Entity\FactureTeacher;
use App\Repository\GroupRepository;
use App\Repository\PaymentRepository;
use App\Repository\SessionRepository;
use App\Repository\TeacherRepository;
use App\Repository\ExpensesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReclamationRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\FactureTeacherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/crud/teacher', name:'api_crud_teacher')]
class TeacherCRUDController extends AbstractController
{

    public function __construct(private ValidatorInterface $validator,private TeacherRepository $teacherRepository)
    {

    }
    
    #[Route('/', name: 'api_crud_teacher_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $teachers = $this->teacherRepository->findAll();
        $data = [];
        foreach ($teachers as $teacher) {
        $data[] = [
            'id'=> $teacher->getId(),
            'firstName'=> $teacher->getFirstName(),
            'lastName'=> $teacher->getLastName(),
            'email'=> $teacher->getEmail(),
            'avatar'=> $teacher->getAvatar(),
            'gender'=> $teacher->getGender()->getName(),
            'number'=> $teacher->getNumber(),
            'status'=> $teacher->getStatus(),
            'registered_at'=> $teacher->getRegisteredAt(),
            'course_name'=> $teacher->getCourse()->getType(),
            'gender_id' => $teacher->getGender()->getId()

        ];
    }

    return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_crud_teacher_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $teacher = $this->teacherRepository->findOneBy(['id' => $id]);
    $data = [
        'id'=> $teacher->getId(),
        'firstName'=> $teacher->getFirstName(),
        'lastName'=> $teacher->getLastName(),
        'email'=> $teacher->getEmail(),
        'avatar'=> $teacher->getAvatar(),
        'gender'=> $teacher->getGender()->getName(),
        'number'=> $teacher->getNumber(),
        'status'=> $teacher->getStatus(),
        'registered_at'=> $teacher->getRegisteredAt(),
        'course_name'=> $teacher->getCourse()->getType()

    ];
    return new JsonResponse($data, Response::HTTP_OK);
    }
    
    #[Route('/{id}/edit', name: 'api_crud_teacher_edit', methods: ['PUT'])]
    public function update($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $teacher = $this->teacherRepository->findOneBy(['id' => $id]);
        if (!$teacher) {
            return new JsonResponse(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
        }
    
        $data = json_decode($request->getContent(), true);
    
        $teacher->setFirstName($data['firstName'] ?? $teacher->getFirstName());
        $teacher->setLastName($data['lastName'] ?? $teacher->getLastName());
        $teacher->setEmail($data['email'] ?? $teacher->getEmail());
        $teacher->setAvatar($data['avatar'] ?? $teacher->getAvatar());
        $teacher->setNumber($data['number'] ?? $teacher->getNumber());
        $teacher->setPassword($data['password'] ?? $teacher->getPassword());
    
        // Associate the selected course with the teacher
        if (!empty($data['course_id'])) {
            $courseId = $data['course_id'];
            $course = $entityManager->getRepository(Course::class)->find($courseId);
            if (!$course) {
                return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
            }
            $teacher->setCourse($course);
        }

        if (!empty($data['gender'])) {
            $genderId = $data['gender'];
            $gender = $entityManager->getRepository(Gender::class)->find($genderId);
            if (!$gender) {
                return new JsonResponse(['error' => 'gender not found'], Response::HTTP_NOT_FOUND);
            }
            $teacher->setGender($gender);
        }  
    
        // Persist changes to the teacher entity
        $entityManager->persist($teacher);
        $entityManager->flush();
    
        return new JsonResponse($teacher->toArray(), Response::HTTP_OK);
    }
    
    #[Route('/{id}', name: 'api_crud_teacher_delete', methods: ['DELETE'])]
    public function delete(Teacher $teacher, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($teacher);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/listeGroupe/{id}', name: 'teacher_groups', methods: ['GET'])]
    public function getGroupsForTeacher(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        // Find the teacher by ID
        $teacher = $this->teacherRepository->find($id);
        if (!$teacher) {
            return $this->json(['error' => 'Teacher not found'], 404);
        }
    
        // Get all groups
        $groups = $entityManager->getRepository(Group::class)->findAll();
    
        // Filter groups that do not have the specific teacher assigned
        $filteredGroups = [];
        foreach ($groups as $group) {
            $teachers = $group->getTeachers();
            $teacherAssigned = false;
            foreach ($teachers as $groupTeacher) {
                if ($groupTeacher->getId() === $id) {
                    $teacherAssigned = true;
                    break;
                }
            }
            if (!$teacherAssigned) {
                $filteredGroups[] = $group;
            }
        }
    
        // Serialize the filtered groups with serialization groups
        $data = $serializer->serialize($filteredGroups, 'json', [
            'groups' => ['group_list'],
        ]);
    
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
    

    #[Route('/selectGroup/{teacherId}/{groupId}', name: 'teacher_associate_group', methods: ['POST'])]
    public function selectGroup(int $teacherId, int $groupId, EntityManagerInterface $entityManager): JsonResponse
    {
        $teacher = $this->teacherRepository->find($teacherId);
        $group = $entityManager->getRepository(Group::class)->find($groupId);
    
        if (!$teacher || !$group) {
            return new JsonResponse(['error' => 'Teacher or group not found'], Response::HTTP_NOT_FOUND);
        }
    
        $teacher->addGroupe($group);
        $entityManager->flush();
    
        return new JsonResponse(['message' => 'Teacher associated with group successfully']);
    }

    
    #[Route('/Groupes/{id}', name: 'teacher_groups', methods: ['GET'])]
    public function getGroups(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
{
    // Find the teacher by ID
    $teacher = $this->teacherRepository->findOneBy(['id' => $id]);
    if (!$teacher) {
        return $this->json(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
    }
    $groups = $teacher->getGroupes();

    $data = $serializer->serialize($groups, 'json', [
        'groups' => ['group_list'],
    ]);

    return new JsonResponse($data, Response::HTTP_OK, [], true);
}
#[Route('/Courses/{id}', name: 'teacher_courses', methods: ['GET'])]
public function getCourses(int $id, SerializerInterface $serializer): Response
{
    $teacher = $this->teacherRepository->findOneBy(['id' => $id]);
    if (!$teacher) {
        return new JsonResponse(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
    }
    $courses = $teacher->getCourse(); // Corrected method call
    $data = $serializer->serialize($courses, 'json', [
        'groups' => ['course_details'],
    ]);
    return new JsonResponse($data, Response::HTTP_OK, [], true);
}
#[Route('/Students/{id}', name: 'teacher_students', methods: ['GET'])]
public function getStudents(int $id): Response
{
    $teacher = $this->teacherRepository->findOneBy(['id' => $id]);

    if (!$teacher) {
        return $this->json(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
    }

    $groups = $teacher->getGroupes();
    $students = [];

    foreach ($groups as $group) {
        $groupStudents = $group->getStudents();

        foreach ($groupStudents as $student) {
foreach( $student->getGroupe() as $g){
$name = $g->getName();
}
            $students[] = [
                'id' => $student->getId(),
                'firstName' => $student->getFirstName(),
                'lastName' => $student->getLastName(),
                'avatar' => $student->getAvatar(),
                'group' =>$name,
                'email' => $student->getEmail()
            ];
        }
    }

    return $this->json($students, Response::HTTP_OK);
}


#[Route('/total/payment', name: 'api_crud_payment_total', methods: ['GET'])]
public function TotalPayment(FactureTeacherRepository $factureRepository): Response
{
    $factures = $factureRepository->findAll();
    $totalPay = 0;
    $totalNotPay = 0;
    
    foreach ($factures as $facture) {
        $status = $facture->getStatus();
        if($status === 'payed'){
            $totalPay++;
        }elseif($status === 'not payed'){
            $totalNotPay++;
        }
    }

    return $this->json(["totalPay"=>$totalPay,"totalNotPay"=>$totalNotPay], Response::HTTP_OK);
}

#[Route('/total/revenue', name: 'api_crud_total', methods: ['GET'])]
public function totalRevenue(FactureTeacherRepository $factureTeacherRepository, PaymentRepository $paymentRepository, ExpensesRepository $expensesRepository): Response
{
    // Fetch all teacher factures
    $teacherFactures = $factureTeacherRepository->findAll();
    
    // Filter teacher factures by status 'payed' and calculate the total amount
    $factureTeacherAmount = 0;
    foreach ($teacherFactures as $facture) {
        if ($facture->getStatus() === 'payed') {
            $factureTeacherAmount += $facture->getAmount();
        }
    }

    // Fetch all student factures
    $studentFactures = $paymentRepository->findAll();

    // Filter student factures by status 'payed' and calculate the total amount
    $factureStudentAmount = 0;
    foreach ($studentFactures as $facture) {
        if ($facture->getStatus() === 'payed') {
            $factureStudentAmount += $facture->getAmount();
        }
    }

    // Fetch all expenses
    $expenses = $expensesRepository->findAll();

    // Calculate the total amount of expenses
    $totalExpenses = 0;
    foreach ($expenses as $expense) {
        $totalExpenses += $expense->getAmount();
    }

    // Calculate the total revenue
    $totalRevenue = $factureStudentAmount - ($factureTeacherAmount + $totalExpenses);

    return $this->json(['total_revenue' => $totalRevenue], Response::HTTP_OK);
}      


#[Route('/total/group/{id}', name: 'api_crud_group_total', methods: ['GET'])]
public function TotalGroup($id, GroupRepository $groupRepository): Response
{
    // Find groups where the teacher ID matches the provided $id
    $groups = $groupRepository->findBy(['teach' => $id]);

    // Initialize total to 0
    $totalGroups = 0;

    // Count the number of groups found
    if ($groups) {
        $totalGroups = count($groups);
    }

    return $this->json(['totalGroups' => $totalGroups], Response::HTTP_OK);
}

#[Route('/total/session/{id}', name: 'api_crud_session_total', methods: ['GET'])]
public function TotalSession($id, SessionRepository $sessionRepository): Response
{
    // Find sessions where the teacher ID matches the provided $id
    $sessions = $sessionRepository->findBy(['teacher' => $id]);

    // Initialize total to 0
    $totalsessions = 0;

    // Count the number of sessions found
    if ($sessions) {
        $totalsessions = count($sessions);
    }

    return $this->json(['totalsessions' => $totalsessions], Response::HTTP_OK);
}

#[Route('/total/annulation/{id}', name: 'api_crud_annulation_total', methods: ['GET'])]
public function TotalAnnulation($id, ReclamationRepository $reclamationRepository): Response
{
    // Find reclamations with the specified teacher ID
    $reclamations = $reclamationRepository->findBy(['teacher' => $id]);

    // Initialize total to 0
    $totalAnnulatedReclamations = 0;

    // Count annulated reclamations individually
    foreach ($reclamations as $reclamation) {
        if ($reclamation->getStatus() === 'annulated') {
            $totalAnnulatedReclamations++;
        }
    }

    return $this->json(['totalAnnulatedReclamations' => $totalAnnulatedReclamations], Response::HTTP_OK);
}

#[Route('/total/session/count/{id}', name: 'api_crud_session_count', methods: ['GET'])]
public function SessionCount($id, SessionRepository $sessionRepository): Response
{
    $sessions = $sessionRepository->findBy(['teacher' => $id]);

    $totalsessionsC = 0;
    $totalsessionsNotDone = 0;

    foreach ($sessions as $session) {
        if ($session->getStatus() === 'done') {
            $totalsessionsC++;
        } else {
            $totalsessionsNotDone++;
        }
    }

    return $this->json([
        'totalsessionsDone' => $totalsessionsC,
        'totalsessionsNotDone' => $totalsessionsNotDone,
    ], Response::HTTP_OK);
}

#[Route('/total/salary/{id}', name: 'api_crud_salary_total', methods: ['GET'])]
public function TotalSalary($id,FactureTeacherRepository $factureRepository): Response
{
    $factures = $factureRepository->findBy(['teacher'=>$id]);
    $currentTime = new DateTime('now');
    $total = 0;
    foreach($factures as $facture){
        $time = $facture->getDateAt()->format("Y-m");
        $currentDate = $currentTime->format("Y-m");

        if ($currentDate === $time && $facture->getStatus()==='payed') {
            $total += $facture->getAmount();
        }
    }

    return $this->json(['salary'=>$total], Response::HTTP_OK);
}

#[Route('/total/facture/count/{id}', name: 'api_crud_facture_count', methods: ['GET'])]
public function FactureCount($id, FactureTeacherRepository $factureRepository): Response
{
    $factures = $factureRepository->findBy(['teacher' => $id]);

    $totalfacturesP = 0;
    $totalfacturesNotPayed = 0;

    foreach ($factures as $facture) {
        if ($facture->getStatus() === 'payed') {
            $totalfacturesP++;
        } else {
            $totalfacturesNotPayed++;
        }
    }

    return $this->json([
        'totalfacturesDone' => $totalfacturesP,
        'totalfacturesNotDone' => $totalfacturesNotPayed,
    ], Response::HTTP_OK);
}
}

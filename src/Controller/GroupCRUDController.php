<?php

namespace App\Controller;

use DateTime;
use App\Entity\Chat;
use App\Entity\Group;
use App\Entity\Gender;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Messagerie;
use App\Entity\Notification;
use Psr\Log\LoggerInterface;
use App\Repository\GroupRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/group',name:'api_crud_group')]
class GroupCRUDController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private GroupRepository $groupRepository)
    {

    }

    #[Route('/', name: 'api_crud_group_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $groups = $this->groupRepository->findAll();
        $data = [];
    
        foreach ($groups as $group) {
    
            $students = [];
            foreach ($group->getStudents() as $student) {
                $students[] = [
                    'id' => $student->getId(),
                    'firstName' => $student->getFirstName(),
                    'lastName' => $student->getLastName(),
                ];
            }
    
            $data[] = [
                'id' => $group->getId(),
                'type' => $group->getType(),
                'teacher_first' => $group->getTeach()->getFirstName(),
                'teacher_last' => $group->getTeach()->getLastName(),
                'student_id' => $students,
                'name'=>$group->getName(),
                'avatar'=>$group->getAvatar(),
                'gender' => $group->getGender()->getName(),
                'gender_id' => $group->getGender()->getId()

            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/group/{id}', name: 'api_crud_group_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $group = $this->groupRepository->find($id);
    
        $students = [];
        foreach ($group->getStudents() ? $group->getStudents() : null as $student) {
            $students[] = [
                'id' => $student->getId(),
                'firstName' => $student->getFirstName(),
                'lastName' => $student->getLastName(),
                'student_course' => $student->getCourse(),

            ];
        }
    
        $data[] = [
            'id' => $group->getId(),
            'type' => $group->getType(),
            'teacherId' => $group->getTeach()->getId(),
            'teacher_first' => $group->getTeach()->getFirstName(),
            'teacher_last' => $group->getTeach()->getLastName(),
            'teacher_course' => $group->getTeach()->getCourse(),
            'student_id' => $students,
            'name'=>$group->getName(),
            'avatar'=>$group->getAvatar(),
            'gender' => $group->getGender()->getName(),
            'gender_id' => $group->getGender()->getId()

        ];
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    
    #[Route('/{id}/edit', name: 'api_crud_group_edit', methods: ['PUT'])]
    public function update($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $group = $this->groupRepository->findOneBy(['id' => $id]);
        $data = json_decode($request->getContent(), true);
    
        if (!empty($data['student_id'])) {
            $studentId = $data['student_id'];
            $student = $entityManager->getRepository(Student::class)->find($studentId);
            if (!$student) {
                return new JsonResponse(['error' => 'student not found'], Response::HTTP_NOT_FOUND);
            }
            $group->addStudent($student);
    
            // Create and persist a notification for the student
            $notification = new Notification();
            $notification->setContent('added to group successfully');
            $notification->setStudent($student);
            $notification->setSentAt(new \DateTime('now'));
            $entityManager->persist($notification);
        }
    
        $entityManager->persist($group);
        $entityManager->flush();
    
        return new JsonResponse(['message' => 'Group updated successfully'], Response::HTTP_OK);
    }
    

    #[Route('/delete/{id}', name: 'api_crud_group_delete', methods: ['DELETE'])]
    public function delete(Group $group, EntityManagerInterface $entityManager): JsonResponse
    {   
        // Remove the group
        $entityManager->remove($group);
        $entityManager->flush();
    
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    
    
    #[Route('/signUp/group', name: 'api_register_group', methods: ['POST'])]
    public function registerGroup(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
    
        $type = $data['type'];
        $group = new Group();
    
        $teacherId = $data['teacherId'];
        if (!empty($teacherId)) {
            $teacher = $entityManager->getRepository(Teacher::class)->find($teacherId);
            if (!$teacher) {
                return new JsonResponse(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
            }
            $group->setTeach($teacher);
        }
    
        $genderId = $data['gender_id'];
        $gender = $entityManager->getRepository(Gender::class)->find($genderId);
        if (!$gender) {
            return $this->json(['error' => 'Gender not found'], Response::HTTP_NOT_FOUND);
        }
    
        $studentIds = $data['studentId'];
        if (!is_array($studentIds)) {
            return new JsonResponse(['error' => 'Invalid student IDs'], Response::HTTP_BAD_REQUEST);
        }
        if (!empty($studentIds)) {
            foreach ($studentIds as $studentId) {
                $student = $entityManager->getRepository(Student::class)->find($studentId);
                if (!$student) {
                    return $this->json(['error' => 'Student not found for ID: ' . $studentId], Response::HTTP_NOT_FOUND);
                }
                $group->addStudent($student);
                $avatar = $student->getAvatar();
            }
        }
    
        $courseName = $teacher->getCourse()->getType();
        $name = $type . ' group ' . $courseName .' '. $group->getId();

        $date = new \DateTime('now');
        $notification = new Notification();
        $notification->setContent('added group successfully');
        $notification->setStudent($student);
        $notification->setSentAt($date);
        $notification->setTeacher($teacher);
    
        $chat = new Chat();
        $chat->setGroupe($group);
        $chat->setTeacher($teacher);
    
        $group->setType($type);
        $group->setName($name);
        $group->setGender($gender);
        $group->setChat($chat);
        $group->setAvatar($avatar);
    
        $entityManager->persist($group);
        $entityManager->persist($chat);
        $entityManager->flush();
    
        return $this->json(['message' => 'Group registered successfully'], Response::HTTP_CREATED);
    }

    #[Route('/session', name: 'api_group_all', methods: ['GET'])]
    public function index2(GroupRepository $groupRepository, LoggerInterface $logger): JsonResponse
    {
        $groups = $groupRepository->findAll();
        $data = [];
        foreach ($groups as $group) {
            $sessions = $group->getSessions();
            $total = 0;
            $student = $group->getStudents()->first();
            if ($student) {
                $nhrtotal = $student->getForfait()->getNbrHourSession();
                $nhrseance = $student->getForfait()->getNbrHourSeance();
                $total = $nhrtotal / $nhrseance;
            }
    
            $sessionCount = count($sessions);
            $logger->info("Group {$group->getId()} has {$sessionCount} sessions, total allowed: {$total}");
    
            if ($sessionCount < $total) {
                $data[] = [
                    'id' => $group->getId(),
                    'type' => $group->getType(),
                    'teacher_first' => $group->getTeach()->getFirstName(),
                    'teacher_last' => $group->getTeach()->getLastName(),
                    'name' => $group->getName(),
                    'avatar' => $group->getAvatar(),
                    'gender' => $group->getGender()->getName(),
                    'gender_id' => $group->getGender()->getId()
                ];
                $logger->info("Group {$group->getId()} added to response (sufficient remaining hours)"); // Success message
            } else {
                $logger->info("Group {$group->getId()} skipped (insufficient remaining hours)"); // Failure message
            }
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    
    
}
<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Student;
use App\Entity\Teacher;
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
            $teachers = [];
            foreach ($group->getTeachers() as $teacher) {
                $teachers[] = [
                    'id' => $teacher->getId(),
                    'firstName' => $teacher->getFirstName(),
                ];
            }
    
            $students = [];
            foreach ($group->getStudents() as $student) {
                $students[] = [
                    'id' => $student->getId(),
                    'firstName' => $student->getFirstName(),
                ];
            }
    
            $data[] = [
                'id' => $group->getId(),
                'type' => $group->getType(),
                'teacher_id' => $teachers,
                'student_id' => $students,
            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    

    #[Route('/{id}', name: 'api_crud_group_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $group = $this->groupRepository->findOneBy(['id' => $id]);
        $teachers = [];
        foreach ($group->getTeachers() as $teacher) {
            $teachers[] = [
                'id' => $teacher->getId(),
                'firstName' => $teacher->getFirstName(),
            ];
        }
    
        $students = [];
        foreach ($group->getStudents() as $student) {
            $students[] = [
                'id' => $student->getId(),
                'firstName' => $student->getFirstName(),
            ];
        }
    
        $data = [
            'id' => $group->getId(),
            'type' => $group->getType(),
            'teacher_id' => $teachers,
            'student_id' => $students,
        ];
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    

    #[Route('/{id}/edit', name: 'api_crud_group_edit', methods: ['PUT'])]
    public function update($id, Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        $group = $this->groupRepository->findOneBy(['id' => $id]);
        $data = json_decode($request->getContent(), true);
    
        empty($data['type']) ? true : $group->setType($data['type']);

        if (!empty($data['teacher_id'])) {
            $teacherId = $data['teacher_id'];
            $teacher = $entityManager->getRepository(Teacher::class)->find($teacherId);
            if (!$teacher) {
                return new JsonResponse(['error' => 'teacher not found'], Response::HTTP_NOT_FOUND);
            }
            $group->addTeacher($teacher);
        }

        if (!empty($data['student_id'])) {
            $studentId = $data['student_id'];
            $student = $entityManager->getRepository(Student::class)->find($studentId);
            if (!$student) {
                return new JsonResponse(['error' => 'student not found'], Response::HTTP_NOT_FOUND);
            }
            $group->addstudent($student);
        }
        $updatedgroup = $this->groupRepository->updateGroup($group);
    
        return new JsonResponse($updatedgroup->toArray(), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_crud_group_delete', methods: ['DELETE'])]
    public function delete(Group $group, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($group);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/Group/student/add/{id}', name: 'group_student_add', methods: ['POST'])]
    public function addStudent(int $id, EntityManagerInterface $entityManager, StudentRepository $studentRepository, GroupRepository $groupRepository): Response
    {
        // Find the student by ID
        $student = $studentRepository->find($id);
        if (!$student) {
            return $this->json(['error' => 'Student not found'], Response::HTTP_NOT_FOUND);
        }
        
        $forfaitType = $student->getForfait()->getSubscription();
        $courses = $student->getCourse();
        $payStatus = $student->getPaymentStatus();
        
        if ($payStatus === 'payed') {
            foreach ($courses as $course) {
                if ($forfaitType === 'private') {
                    $group = new Group();
                    $group->setType('private');
                    $group->addStudent($student);
                    $entityManager->persist($group);
                } else {
                    $existingGroups = $groupRepository->findBy(['type' => 'public']);
                    $isAdded = false;
                    
                    // Check if any existing public group matches forfait_id and course_id
                    foreach ($existingGroups as $existingGroup) {
                        $existingStudents = $existingGroup->getStudents();
                        foreach ($existingStudents as $existingStudent) {
                            $existingCourses = $existingStudent->getCourse();
                            foreach ($existingCourses as $existingCourse) {
                                if ($existingStudent->getForfait()->getId() === $student->getForfait()->getId() && $existingCourse->getId() === $course->getId()) {
                                    $existingGroup->addStudent($student);
                                    $isAdded = true;
                                    break 3;
                                }
                            }
                        }
                    }
                    
                    if (!$isAdded) {
                        $group = new Group();
                        $group->setType('public');
                        $group->addStudent($student);
                        $entityManager->persist($group);
                    }
                }
            }
        }
        
        $entityManager->flush();
        
        return new JsonResponse(['message' => 'Student added to groups successfully'], Response::HTTP_OK);
    }

    #[Route('/signUp/group', name: 'api_register_group', methods: ['POST'])]
    public function registerGroup(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {

        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
        $type = $data['type'] ?? null;
        $group = new Group();

        if (!empty($data['teacherId'])) {
            $teacherId = $data['teacherId'];
            $teacher = $entityManager->getRepository(Teacher::class)->find($teacherId);
            if (!$teacher) {
                return new JsonResponse(['error' => 'teacher not found'], Response::HTTP_NOT_FOUND);
            }
            $group->setTeach($teacher);
        }
        if (array_key_exists('studentId', $data) && is_string($data['studentId'])) {
            // Extract individual IDs from the string
            $studentIdArray = explode(' ', $data['studentId']);
            // Trim quotes and convert to integers if necessary
            $studentIdArray = array_map(function($id) {
                return trim($id, '"');
            }, $studentIdArray);
            // Loop through each student ID and add them to the group
            foreach ($studentIdArray as $studentId) {
                $student = $entityManager->getRepository(Student::class)->find($studentId);
                if (!$student) {
                    return $this->json(['error' => 'student not found for ID: ' . $studentId], Response::HTTP_NOT_FOUND);
                }
                $group->addStudent($student);
            }
        }
        $group->setType($type);
        $entityManager->persist($group);
        $entityManager->flush();
    
        return $this->json(['message' => 'Group registered successfully'], Response::HTTP_CREATED);
    }

}
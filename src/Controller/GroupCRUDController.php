<?php

namespace App\Controller;

use App\Entity\Gender;
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

    #[Route('/signUp/group', name: 'api_register_group', methods: ['POST'])]
    public function registerGroup(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
    
        $type = $request->request->get('type');
        $name = $request->request->get('name');
        $avatarFile = $request->files->get('avatar');
        $group = new Group();
    
        $avatarFileName = null;
        if ($avatarFile) {
            // Move uploaded file to a directory
            $avatarFileName = md5(uniqid()) . '.' . $avatarFile->guessExtension();
            $avatarFile->move($this->getParameter('PFE'), $avatarFileName);
        }
    
        // Fetch teacher by teacher_id if provided
        $teacherId = $request->request->get('teacher_id');
        if (!empty($teacherId)) {
            $teacher = $entityManager->getRepository(Teacher::class)->find($teacherId);
            if (!$teacher) {
                return new JsonResponse(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
            }
            $group->setTeach($teacher);
        }
    
        $genderId = $request->request->get('gender_id');
        $gender = $entityManager->getRepository(Gender::class)->find($genderId);
        if (!$gender) {
            return $this->json(['error' => 'Gender not found'], Response::HTTP_NOT_FOUND);
        }
    
        // Extract and add students to the group
        $studentIds = $request->request->get('studentId');
        $studentIds = $request->request->get('studentId');
        if (!is_iterable($studentIds)) {
            // Handle the case where $studentIds is not iterable
            return new JsonResponse(['error' => 'Invalid student IDs'], Response::HTTP_BAD_REQUEST);
        }
        if (!empty($studentIds)) {
            foreach ($studentIds as $studentId) {
                $student = $entityManager->getRepository(Student::class)->find($studentId);
                if (!$student) {
                    return $this->json(['error' => 'Student not found for ID: ' . $studentId], Response::HTTP_NOT_FOUND);
                }
                $group->addStudent($student);
            }
        }
    
        $group->setType($type);
        $group->setName($name);
        $group->setGender($gender);
        $group->setAvatar($avatarFileName);
    
        $entityManager->persist($group);
        $entityManager->flush();
    
        return $this->json(['message' => 'Group registered successfully'], Response::HTTP_CREATED);
    }
    

}
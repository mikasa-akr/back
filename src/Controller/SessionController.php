<?php

namespace App\Controller;

use DateTime;
use DateInterval;
use App\Entity\Group;
use App\Entity\Course;
use App\Entity\Session;
use App\Entity\Teacher;
use App\Repository\GroupRepository;
use App\Repository\SessionRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/session',name:'api_')]
class SessionController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private SessionRepository $sessionRepository)
    {

    }
    
    #[Route('/teacher/sessions/{id}', name: 'api_session', methods: ['GET'])]
    public function SessionTeacher($id): JsonResponse
    {
        $sessions = $this->sessionRepository->findBy(['teacher' => $id]);
    
        $data = [];
        foreach ($sessions as $session) {
            $data[] = [
                'id' => $session->getId(),
                'date_seance' => $session->getDateSeance(),
                'time_start' => $session->getTimeStart(),
                'status' => $session->getStatus(),
                'time_end' => $session->getTimeEnd(),
                'visibility' => $session->isVisibility(),
                'groupe_seance_id' => $session->getGroupeSeanceId()->getType(),
                'seance_course_id' => $session->getSeanceCourse()->getType(),
            ];
        }
        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/teacher/sessions/week/{id}', name: 'api_session_week_teacher', methods: ['GET'])]
    public function SessionTeacherForWeek($id): JsonResponse
    {
        // Get the current date
        $currentDate = new \DateTime();
        // Calculate the start and end date of the current week
        $startOfWeek = $currentDate->modify('this week')->format('Y-m-d');
        $endOfWeek = $currentDate->modify('this week +6 days')->format('Y-m-d');
    
        // Find sessions for the teacher within the current week
        $sessions = $this->sessionRepository->findSessionsForWeek($id, $startOfWeek, $endOfWeek);
        $data = [];
        foreach ($sessions as $session) {
            if ($session->getStatus() === 'canceled session') {
                continue; // Skip canceled sessions
            }
            $data[] = [
                'id' => $session->getId(),
                'date_seance' => $session->getDateSeance() ? $session->getDateSeance()->format('Y-m-d') : null,
                'time_start' => $session->getTimeStart() ? $session->getTimeStart()->format('H:i') : null,
                'status' => $session->getStatus(),
                'time_end' => $session->getTimeEnd() ? $session->getTimeEnd()->format('H:i') : null,
                'visibility' => $session->isVisibility(),
                'groupe_seance_id' => $session->getGroupeSeanceId()->getType(),
                'seance_course_id' => $session->getSeanceCourse()->getType(),
            ];
        }
    
        return new JsonResponse($data, Response::HTTP_OK);
    }
    
#[Route('/student/sessions/week/{id}', name: 'api_session_week_student', methods: ['GET'])]
public function SessionStudentForWeek($id): JsonResponse
{
    $currentDate = new \DateTime();
    $startOfWeek = $currentDate->modify('this week')->format('Y-m-d');
    $endOfWeek = $currentDate->modify('this week +6 days')->format('Y-m-d');
    $sessions = $this->sessionRepository->findStudentSessionsForWeek($id, $startOfWeek, $endOfWeek);
    $data = [];
    foreach ($sessions as $session) {
    if($session->getStatus() === 'canceled session'){
        continue;
        }
        $data[] = [
            'id' => $session->getId(),
            'date_seance' => $session->getDateSeance() ? $session->getDateSeance()->format('Y-m-d') : null,
            'time_start' => $session->getTimeStart() ? $session->getTimeStart()->format('H:i') : null,
            'status' => $session->getStatus(),
            'time_end' => $session->getTimeEnd() ? $session->getTimeEnd()->format('H:i') : null,
            'visibility' => $session->isVisibility(),
            'groupe_seance_id' => $session->getGroupeSeanceId()->getType(),
            'seance_course_id' => $session->getSeanceCourse()->getType(),
        ];
    }
    return new JsonResponse($data, Response::HTTP_OK);
}

#[Route('/student/{id}', name: 'api_crud_session_student', methods: ['GET'])]
public function sessionStudent($id, StudentRepository $studentRepository): JsonResponse
{
    $students = $studentRepository->findBy(['id'=>$id]);
    
    $sessions = [];
    foreach ($students as $student) {
        $groups = $student->getGroupe();
        foreach ($groups as $group) {
                $groupSessions = $group->getSessions();
                foreach ($groupSessions as $session) {
                    $sessions[] = [
                        'id' => $session->getId(),
                        'date_seance' => $session->getDateSeance(),
                        'time_start' => $session->getTimeStart(),
                        'status' => $session->getStatus(),
                        'time_end' => $session->getTimeEnd(),
                        'visibility' => $session->isVisibility(),
                        'teacher_id' => $session->getTeacher()->getId(),
                        'seance_course_id' => $session->getSeanceCourse()->getType(),
                    ];
            }
        }
    }
    
    return new JsonResponse($sessions, Response::HTTP_OK);
}

#[Route('/signUp/session', name: 'api_register_session', methods: ['POST'])]
public function registerSession(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (null === $data) {
        return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
    }
    $visibility = $data['visibility'] ?? null;
    $dataSeanceS = $data['date_seance'] ?? null;
    $timeStartS = $data['time_start'] ?? null;
    $dataSeance = new DateTime($dataSeanceS);
    $timeStart = new DateTime($timeStartS);

    if (!empty($data['groupe_id'])) {
        $groupId = $data['groupe_id'];
        $group = $entityManager->getRepository(Group::class)->find($groupId);
        if (!$group) {
            return $this->json(['error' => 'group not found'], Response::HTTP_NOT_FOUND);
        }
        $teacher=$group->getTeach();
        $firstStudent = $group->getStudents()->first();
        if (!$firstStudent) {
            return $this->json(['error' => 'No students found in the group'], Response::HTTP_NOT_FOUND);
        }
        $forfait = $firstStudent->getForfait();

        $course = $teacher->getCourse(); 

        if (!$forfait) {
            return $this->json(['error' => 'NbrHourSeance not found for the group'], Response::HTTP_NOT_FOUND);
        }
        $nb = $forfait->getNbrHourSeance();
      
        // Ensure nb is not null before using it
        if ($nb === null) {
            return $this->json(['error' => 'NbrHourSeance not found in the forfait'], Response::HTTP_NOT_FOUND);
        }
    } else {
        return $this->json(['error' => 'group ID is required'], Response::HTTP_BAD_REQUEST);
    }
    
    $interval = new DateInterval('PT' . $nb . 'H');
    
    $timeEnd = clone $timeStart;
    $timeEnd->add($interval);

    $session = new Session();
    $session->setStatus("active");
    $session->setVisibility($visibility);
    $session->setDateSeance($dataSeance);
    $session->setTimeStart($timeStart);
    $session->setTimeEnd($timeEnd);
    $session->setSeanceCourse($course);
    $session->setTeacher($teacher);
    $session->setGroupeSeanceId($group);

    $entityManager->persist($session);
    $entityManager->flush();

    return $this->json(['message' => 'session registered successfully'], Response::HTTP_CREATED);
}

#[Route('/', name: 'api_crud_session_index', methods: ['GET'])]
public function index(): JsonResponse
{
    $sessions = $this->sessionRepository->findAll();
    $data = [];

    foreach ($sessions as $session) {
        $teacher = $session->getTeacher()->getId();
        $groupeSeanceId = $session->getGroupeSeanceId()->getType();
        $seanceCourse = $session->getSeanceCourse()->getType();

        $data[] = [
            'id' => $session->getId(),
            'status' => $session->getStatus(),
            'date_seance' => $session->getDateSeance(),
            'time_start' => $session->getTimeStart(),
            'time_end' => $session->getTimeEnd(),
            'visibility' => $session->isVisibility(),
            'teacher_id' => $teacher,
            'groupe_seance_id' => $groupeSeanceId,
            'seance_course_id' => $seanceCourse,
        ];
    }

    return new JsonResponse($data, Response::HTTP_OK);
}

#[Route('/{id}', name: 'api_crud_session_show', methods: ['GET'])]
public function show($id): JsonResponse
{
        $session = $this->sessionRepository->findOneBy(['id' => $id]);
    $data = [
        'id'=> $session->getId(),
        'status'=> $session->getStatus(),
        'date_seance'=> $session->getDateSeance(),
        'time_start'=> $session->getTimeStart(),
        'time_end'=>$session->getTimeEnd(),
        'visibility'=>$session->isVisibility(),
        'teacher_id'=> $session->getTeacher()->toArray(),
        'groupe_seance_id'=> $session->getGroupeSeanceId()->toArray(),
        'seance_course_id'=> $session->getSeanceCourse()->toArray(),
    ];
    return new JsonResponse($data, Response::HTTP_OK);
}
#[Route('/{id}', name: 'api_crud_session_delete', methods: ['DELETE'])]
public function delete(Session $session, EntityManagerInterface $entityManager): JsonResponse
{
    $entityManager->remove($session);
    $entityManager->flush();
    return new JsonResponse(null, Response::HTTP_NO_CONTENT);
}
#[Route('/{id}/edit', name: 'api_crud_session_edit', methods: ['PUT'])]
public function update($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $session = $this->sessionRepository->findOneBy(['id' => $id]);
    $data = json_decode($request->getContent(), true);
    empty($data['status']) ? true : $session->setStatus($data['status']);
    empty($data['date_seance']) ? true : $session->setDateSeance($data['date_seance']);
    empty($data['time_start']) ? true : $session->setTimeStart($data['time_start']);
    empty($data['time_end']) ? true : $session->setTimeEnd($data['time_end']);
    empty($data['visibility']) ? true : $session->setVisibility($data['visibility']);

    if (!empty($data['groupe_id'])) {
        $groupId = $data['groupe_id'];
        $group = $entityManager->getRepository(Group::class)->find($groupId);
        if (!$group) {
            return new JsonResponse(['error' => 'group not found'], Response::HTTP_NOT_FOUND);
        }
        $session->setGroupeSeanceId($group);
    }

    if (!empty($data['course_id'])) {
        $courseId = $data['course_id'];
        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }
        $session->setSeanceCourse($course);
    }
    if (!empty($data['teacher_id'])) {
        $teacherId = $data['teacher_id'];
        $teacher = $entityManager->getRepository(Teacher::class)->find($teacherId);
        if (!$teacher) {
            return new JsonResponse(['error' => 'teacher not found'], Response::HTTP_NOT_FOUND);
        }
        $session->setTeacher($teacher);
    }
    $updatedsession = $this->sessionRepository->updateSession($session);
    return new JsonResponse($updatedsession->toArray(), Response::HTTP_OK);
}
}

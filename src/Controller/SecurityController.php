<?php

namespace App\Controller;

use DateTime;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]
class SecurityController extends AbstractController
{

    #[Route('/total/student', name: 'api_crud_hour_total', methods: ['GET'])]
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
#[Route('/total/all', name: 'api_total_all', methods: ['GET'])]
public function Total(StudentRepository $studentRepository, TeacherRepository $teacherRepository): Response
{
    $students = $studentRepository->findAll();
    $teachers = $teacherRepository->findAll();
    $totalS = count($students);
    $totalT = count($teachers); 

    return $this->json(['student' => $totalS, 'teacher' => $totalT], Response::HTTP_OK);
}
}

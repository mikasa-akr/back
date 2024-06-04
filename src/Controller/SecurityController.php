<?php

namespace App\Controller;

use DateTime;
use App\Entity\Group;
use App\Entity\Student;
use App\Entity\Teacher;
use Psr\Log\LoggerInterface;
use App\Service\MailerService;
use App\Repository\GroupRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

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


#[Route('/password', name: 'api_password', methods: ['POST'])]
public function forgotPassword(Request $request, StudentRepository $studentRepository, TeacherRepository $teacherRepository, MailerService $mailerService, LoggerInterface $logger, EntityManagerInterface $entityManager): JsonResponse 
{
    $data = json_decode($request->getContent(), true);

    if (null === $data) {
        return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
    }

    $email = $data['email'] ?? null;

    if (empty($email)) {
        return $this->json(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
    }

    // Check if the user is a student
    $student = $studentRepository->findOneBy(['email' => $email]);

    // Check if the user is a teacher
    $teacher = $teacherRepository->findOneBy(['email' => $email]);

    if (!$student && !$teacher) {
        return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
    }

    // Use the appropriate user entity
    $user = $student ?? $teacher;

    // Generate the reset URL (using email as a parameter)
    $resetUrl = 'http://localhost:3000/reset-password?email=' . urlencode($email);

    $htmlContent = '
    <div style="font-size: 18px; line-height: 1.5;">
        <p style="text-align: center;">
            <a href="' . htmlspecialchars($resetUrl) . '" style="display: inline-block; padding: 10px 20px; font-size: 16px; color: #ffffff; background-color: #007bff; text-align: center; text-decoration: none; border-radius: 5px;">Change Password</a>
        </p>
    </div>
    ';

    // Send the email with the password reset link
    try {
        $mailerService->sendPasswordEmail($user->getEmail(), $htmlContent);
        $logger->info("Password reset email sent to {$user->getEmail()}");

        return $this->json(['message' => 'Password reset instructions sent successfully'], Response::HTTP_OK);
    } catch (\Exception $e) {
        $logger->error("Failed to send password reset email to {$user->getEmail()}: " . $e->getMessage());

        return $this->json(['error' => 'Failed to send password reset instructions'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


#[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
public function resetPassword( Request $request, StudentRepository $studentRepository, TeacherRepository $teacherRepository, EntityManagerInterface $entityManager,UserPasswordHasherInterface $passwordHasher): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (null === $data) {
        return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
    }

    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (empty($password)) {
        return $this->json(['error' => 'Password is required'], Response::HTTP_BAD_REQUEST);
    }

    $student = $studentRepository->findOneBy(['email' => $email]);

    // Check if the user is a teacher
    $teacher = $teacherRepository->findOneBy(['email' => $email]);

    if (!$student && !$teacher) {
        return $this->json(['error' => 'User not found or phone number does not match'], Response::HTTP_NOT_FOUND);
    }

    // Use the appropriate user entity
    $user = $student ?? $teacher;
    if (!$user) {
        return $this->json(['error' => 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
    }

    $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
    $user->setPassword($hashedPassword);


    $entityManager->persist($user);
    $entityManager->flush();

    return $this->json(['message' => 'Password reset successfully'], Response::HTTP_OK);
}




}

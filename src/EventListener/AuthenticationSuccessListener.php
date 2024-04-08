<?php 
// src/EventListener/AuthenticationSuccessListener.php
namespace App\EventListener;

use App\Entity\Student;
use App\Entity\Teacher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class AuthenticationSuccessListener
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }
        // Check the user type and update status accordingly
        if ($user instanceof Student) {
            $user->setStatus('online');
        } elseif ($user instanceof Teacher) {
            $user->setStatus('online');
        } else {
        }
        $this->entityManager->flush();
        $data['data'] = array(
            'id' => $user->getId(),
            'first_name'=>$user->getFirstName(),
            'status'=>$user->getPaymentStatus(),
            'last_name' => $user->getLastName()
        );
        $event->setData($data);
    }
}

<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Forfait;
use App\Entity\Subscription;
use App\Repository\ForfaitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/notification',name:'api_notification')]
class NotificationCRUDController extends AbstractController
{

}

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

#[Route('api/crud/forfait',name:'api_crud_forfait')]
class ForfaitCRUDController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private ForfaitRepository $forfaitRepository)
    {

    }
    
    #[Route('/', name: 'api_crud_forfait_index', methods: ['GET'])]
    public function index(): JsonResponse
    {

        $forfaits = $this->forfaitRepository->findAll();
    $data = [];

    foreach ($forfaits as $forfait) {
        $data[] = [
            'id' => $forfait->getId(),
            'title' => $forfait->getTitle(),
            'price' => $forfait->getPrice(),
            'NbrHourSeance' => $forfait->getNbrHourSeance(),
            'NbrHourSession' => $forfait->getNbrHourSession(),
            'subscription' => $forfait->getSubscription(),


        ];
    }

    return new JsonResponse($data, Response::HTTP_OK);

    }

    #[Route('/{id}', name: 'api_crud_forfait_show', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $forfait = $this->forfaitRepository->findOneBy(['id' => $id]);
    $data = [
        'id' => $forfait->getId(),
        'title' => $forfait->getTitle(),
        'price' => $forfait->getPrice(),
        'NbrHourSeance' => $forfait->getNbrHourSeance(),
        'NbrHourSession' => $forfait->getNbrHourSession(),
        'subscription' => $forfait->getSubscription(),


    ];
    return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/{id}/edit', name: 'api_crud_forfait_edit', methods: ['PUT'])]
    public function update($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $forfait = $entityManager->getRepository(Forfait::class)->findOneBy(['id' => $id]);
    if (!$forfait) {
        return new JsonResponse(['error' => 'Forfait not found'], Response::HTTP_NOT_FOUND);
    }
    
    $data = json_decode($request->getContent(), true);

    empty($data['title']) ? true : $forfait->setTitle($data['title']);
    empty($data['price']) ? true : $forfait->setPrice($data['price']);
    empty($data['NbrHourSeance']) ? true : $forfait->setNbrHourSeance($data['NbrHourSeance']);
    empty($data['NbrHourSession']) ? true : $forfait->setNbrHourSession($data['NbrHourSession']);
    empty($data['subscription']) ? true : $forfait->setSubscription($data['subscription']);


    $entityManager->persist($forfait);
    $entityManager->flush();

    return new JsonResponse($forfait->toArray(), Response::HTTP_OK);
}
    #[Route('/{id}', name: 'api_crud_Forfait_delete', methods: ['DELETE'])]
    public function delete(Forfait $Forfait, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($Forfait);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

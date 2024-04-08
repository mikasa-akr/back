<?php

namespace App\Controller;

use App\Entity\Expenses;
use App\Repository\ExpensesRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('api/crud/expenses',name:'api_crud_expenses')]
class ExpensesCRUDController extends AbstractController
{
    public function __construct(private ValidatorInterface $validator,private ExpensesRepository $expensesRepository)
    {

    }
    
    #[Route('/', name: 'api_crud_expenses_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $expensess = $this->expensesRepository->findAll();
    $data = [];
    foreach ($expensess as $expenses) {
        $data[] = [
            'id' => $expenses->getId(),
            'amount' => $expenses->getAmount(),
            'category' => $expenses->getCategory(),
            'date' => $expenses->getDate()->format('Y-m-d H:i'),
        ];
    }
    return new JsonResponse($data, Response::HTTP_OK);
    }
    #[Route('/{id}/edit', name: 'api_crud_expenses_edit', methods: ['PUT'])]
    public function update($id, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $expenses = $entityManager->getRepository(Expenses::class)->findOneBy(['id' => $id]);
    if (!$expenses) {
        return new JsonResponse(['error' => 'expenses not found'], Response::HTTP_NOT_FOUND);
    }
    $data = json_decode($request->getContent(), true);
    empty($data['amount']) ? true : $expenses->setAmount($data['amount']);
    empty($data['category']) ? true : $expenses->setCategory($data['category']);
    empty($data['date']) ? true : $expenses->setDate($data['date']);
    $entityManager->persist($expenses);
    $entityManager->flush();

    return new JsonResponse($expenses->toArray(), Response::HTTP_OK);
}
    #[Route('/{id}', name: 'api_crud_expenses_delete', methods: ['DELETE'])]
    public function delete(Expenses $expenses, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($expenses);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/signUp/expenses', name: 'api_register_expenses', methods: ['POST'])]
    public function registerExpenses(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
        $amount = $data['amount'] ?? null;
        $category = $data['category'] ?? null;
        $date = new DateTime('now');
 
        $forfait = new Expenses();
        $forfait->setAmount($amount);
        $forfait->setCategory($category);
        $forfait->setDate($date);
    
        $entityManager->persist($forfait);
        $entityManager->flush();
    
        return $this->json(['message' => 'Expenses registered successfully'], Response::HTTP_CREATED);
    }
}

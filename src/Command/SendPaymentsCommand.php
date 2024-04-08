<?php 
// src/Command/SendPaymentsCommand.php

namespace App\Command;

use App\Entity\FactureTeacher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;

class SendPaymentsCommand extends Command
{
    protected static $defaultName = 'app:send-payments';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $factures = $this->entityManager->getRepository(FactureTeacher::class)->findAll();

        foreach ($factures as $facture) {
            $datePay = $facture->getDatePay();
            $currentDate = new \DateTime();

            if ($datePay->format('Y-m-d') === $currentDate->format('Y-m-d')) {
                $teacher = $facture->getTeacher();
                $amount = $facture->getAmount();
                $card = $teacher->getCardName();
                
                // Here you can implement the logic to send the payment to the teacher's card
                // Use the Stripe API or any other payment gateway
                
                // Example Stripe payment
                $httpClient = HttpClient::create();
                $response = $httpClient->request('POST', 'https://api.stripe.com/v1/payment_intents', [
                    'auth_basic' => ['pk_test_51OyasWBNWgwGqFvzzcG0jn80B1lHgihqrkhbRdcCvexIeAZcLur7KbRpipxkKC9DTkO1xhsLehILhrBNm8Wi9ep400Td1NEJiI', ''],
                    'json' => [
                        'amount' => $amount * 100, // Amount in cents
                        'currency' => 'usd',
                        'source' => $card, // Replace with actual card ID
                        'confirm' => true,
                    ],
                ]);

                if ($response->getStatusCode() === 200) {
                    $output->writeln("Payment sent successfully for teacher " . $teacher->getFirstName() . " " . $teacher->getLastName());
                } else {
                    $output->writeln("Failed to send payment for teacher " . $teacher->getFirstName() . " " . $teacher->getLastName());
                }
            }
        }

        return Command::SUCCESS;
    }
}

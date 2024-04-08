<?php
// src/Command/UpdateRattrapageStatusCommand.php

namespace App\Command;

use DateInterval;
use App\Entity\Session;
use App\Repository\RattrapageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRattrapageStatusCommand extends Command
{
    protected static $defaultName = 'app:update-rattrapage-status';

    private $entityManager;
    private $rattrapageRepository;

    public function __construct(EntityManagerInterface $entityManager, RattrapageRepository $rattrapageRepository)
    {
        $this->entityManager = $entityManager;
        $this->rattrapageRepository = $rattrapageRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Updates the status of rattrapage after 3 hours')
            ->setHelp('This command updates the status of rattrapage after 3 hours of its date');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Retrieve the current date and time
        $currentTime = new \DateTime();
    
        // Retrieve the sessions older than 3 hours
        $sessions = $this->entityManager->getRepository(Session::class)->findSessions($currentTime);
    
        foreach ($sessions as $session) {
            // Retrieve the associated Rattrapage entities
            $rattrapages = $session->getRattrapages();
    
            // Check if rattrapages exist
            if ($rattrapages->isEmpty()) {
                continue; // Skip to the next session if no rattrapages exist
            }
    
            // Retrieve the votes related to the session
            $votes = $session->getVotes();
    
            // Count the number of "yes" and "no" votes
            $yesCount = 0;
            $noCount = 0;
            foreach ($votes as $vote) {
                if ($vote->getAgree() === 'yes') {
                    $yesCount++;
                } elseif ($vote->getAgree() === 'no') {
                    $noCount++;
                }
            }
    
            // If "yes" votes are more than "no" votes, update session status to "confirm"
            if ($yesCount > $noCount) {
                foreach ($rattrapages as $rattrapage) {
                    $session->setDateSeance($rattrapage->getDate());
                    $session->setTimeStart($rattrapage->getTime());
                    $session->setStatus("confirm");
                    $group=$session->getGroupeSeanceId();
                    $firstStudent = $group->getStudents()->first(); // Use first() method
                    $forfait = $firstStudent->getForfait();
                    $nb = $forfait->getNbrHourSeance();
                    $interval = new DateInterval('PT' . $nb . 'H');
                    $timeEnd = clone $rattrapage->getTime();
                    $timeEnd->add($interval);
                    $session->setTimeEnd($timeEnd);

                }
            } elseif ($noCount > $yesCount) {
                // If "no" votes are more than "yes" votes, update session status to "canceled session"
                $session->setStatus("canceled session");
            }
        }
    
        // Flush all changes to the database
        $this->entityManager->flush();
        $output->writeln('Rattrapage status updated successfully.');
    
        return Command::SUCCESS;
    }
}

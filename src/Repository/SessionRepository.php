<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session>
 *
 * @method Session|null find($id, $lockMode = null, $lockVersion = null)
 * @method Session|null findOneBy(array $criteria, array $orderBy = null)
 * @method Session[]    findAll()
 * @method Session[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

//    /**
//     * @return Session[] Returns an array of Session objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Session
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
public function updateSession(Session $session): Session
{
    $this->getEntityManager()->persist($session);
    $this->getEntityManager()->flush();
    return $session;
}

public function findSessions(\DateTimeInterface $dateTime): array
{
    return $this->createQueryBuilder('s')
        ->leftJoin('s.rattrapages', 'r')
        ->andWhere('r.dateAt < :dateTime')
        ->setParameter('dateTime', $dateTime->format('Y-m-d H:i:s'))
        ->getQuery()
        ->getResult();
}

    public function findSessionsForWeek($teacherId, $startOfWeek, $endOfWeek)
    {
        // Create a query builder
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.teacher', 't')
            ->andWhere('t.id = :teacherId')
            ->andWhere('s.date_seance BETWEEN :startOfWeek AND :endOfWeek')
            ->setParameter('teacherId', $teacherId)
            ->setParameter('startOfWeek', $startOfWeek)
            ->setParameter('endOfWeek', $endOfWeek)
            ->getQuery();

        // Execute the query and return the result
        return $qb->getResult();
    }
    public function findStudentSessionsForWeek($studentId, $startOfWeek, $endOfWeek)
    {
        // Create a query builder
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.groupe_seance', 'g')
            ->leftJoin('g.students', 'e')
            ->andWhere('e.id = :studentId')
            ->andWhere('s.date_seance BETWEEN :startOfWeek AND :endOfWeek')
            ->setParameter('studentId', $studentId)
            ->setParameter('startOfWeek', $startOfWeek)
            ->setParameter('endOfWeek', $endOfWeek)
            ->getQuery();

        // Execute the query and return the result
        return $qb->getResult();
    }
}


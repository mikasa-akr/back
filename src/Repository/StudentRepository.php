<?php

namespace App\Repository;

use App\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Student>
 *
 * @method Student|null find($id, $lockMode = null, $lockVersion = null)
 * @method Student|null findOneBy(array $criteria, array $orderBy = null)
 * @method Student[]    findAll()
 * @method Student[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Student::class);
    }

//    /**
//     * @return Student[] Returns an array of Student objects
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

//    public function findOneBySomeField($value): ?Student
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

public function updateStudent(Student $student): Student
{
    $this->getEntityManager()->persist($student);
    $this->getEntityManager()->flush();

    return $student;
}

public function totalStudent(): int
{
    $currentMonth = date('m'); // Get the current month as a two-digit number (e.g., '04' for April)
    $currentYear = date('Y'); // Get the current year

    // Query to count the number of students registered for the current month
    $qb = $this->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->where('MONTH(s.date_at) = :currentMonth')
        ->andWhere('YEAR(s.date_at) = :currentYear')
        ->setParameter('currentMonth', $currentMonth)
        ->setParameter('currentYear', $currentYear)
        ->getQuery();

    return (int) $qb->getSingleScalarResult();
}


}

<?php

namespace App\Repository;

use App\Entity\FactureTeacher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FactureTeacher>
 *
 * @method FactureTeacher|null find($id, $lockMode = null, $lockVersion = null)
 * @method FactureTeacher|null findOneBy(array $criteria, array $orderBy = null)
 * @method FactureTeacher[]    findAll()
 * @method FactureTeacher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactureTeacherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactureTeacher::class);
    }

//    /**
//     * @return FactureTeacher[] Returns an array of FactureTeacher objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FactureTeacher
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

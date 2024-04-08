<?php

namespace App\Repository;

use App\Entity\Rattrapage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rattrapage>
 *
 * @method Rattrapage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rattrapage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rattrapage[]    findAll()
 * @method Rattrapage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RattrapageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rattrapage::class);
    }

//    /**
//     * @return Rattrapage[] Returns an array of Rattrapage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Rattrapage
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

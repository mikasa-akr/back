<?php

namespace App\Repository;

use App\Entity\Forfait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Forfait>
 *
 * @method Forfait|null find($id, $lockMode = null, $lockVersion = null)
 * @method Forfait|null findOneBy(array $criteria, array $orderBy = null)
 * @method Forfait[]    findAll()
 * @method Forfait[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForfaitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forfait::class);
    }

//    /**
//     * @return Forfait[] Returns an array of Forfait objects
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

//    public function findOneBySomeField($value): ?Forfait
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
public function updateForfait(Forfait $forfait): Forfait
{
    $this->getEntityManager()->persist($forfait);
    $this->getEntityManager()->flush();

    return $forfait;
}

public function enregisterForfait($title, $price, $stype, $nbse, $nbss, $ctype): array
{
    $conn = $this->getEntityManager()->getConnection();

    // Insert data into the forfait table
    $sql = '
    INSERT INTO forfait (title, price, subscription_id, nbr_hour_seance, nbr_hour_session, course_id) 
    SELECT :title, :price, s.id, :nbHSeance, :nbHSession, c.id 
    FROM subscription s, course c 
    WHERE s.type = :stype AND c.type = :ctype';

    $resultSet = $conn->executeQuery($sql, [
        'title' => $title, 
        'price' => $price,
        'stype' => $stype, 
        'nbHSeance' => $nbse,
        'nbHSession' => $nbss, 
        'ctype' => $ctype
    ]);

    // You can return any specific response if needed
    return ['message' => 'Forfait registered successfully'];
}





}

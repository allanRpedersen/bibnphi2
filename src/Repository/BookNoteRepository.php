<?php

namespace App\Repository;

use App\Entity\BookNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BookNote|null find($id, $lockMode = null, $lockVersion = null)
 * @method BookNote|null findOneBy(array $criteria, array $orderBy = null)
 * @method BookNote[]    findAll()
 * @method BookNote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookNote::class);
    }

    // /**
    //  * @return BookNote[] Returns an array of BookNote objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BookNote
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    //     public function deleteAll(){
    //         $query = $this->createQueryBuilder('e')
    //                  ->delete()
    //                  ->getQuery()
    //                  ->execute();
    //         return $query;
    // }

}

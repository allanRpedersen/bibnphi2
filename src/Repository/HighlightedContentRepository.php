<?php

namespace App\Repository;

use App\Entity\HighlightedContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HighlightedContent|null find($id, $lockMode = null, $lockVersion = null)
 * @method HighlightedContent|null findOneBy(array $criteria, array $orderBy = null)
 * @method HighlightedContent[]    findAll()
 * @method HighlightedContent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HighlightedContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HighlightedContent::class);
    }

    // /**
    //  * @return HighlightedContent[] Returns an array of HighlightedContent objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HighlightedContent
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

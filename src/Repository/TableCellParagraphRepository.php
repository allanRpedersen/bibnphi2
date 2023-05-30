<?php

namespace App\Repository;

use App\Entity\TableCellParagraph;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TableCellParagraph>
 *
 * @method TableCellParagraph|null find($id, $lockMode = null, $lockVersion = null)
 * @method TableCellParagraph|null findOneBy(array $criteria, array $orderBy = null)
 * @method TableCellParagraph[]    findAll()
 * @method TableCellParagraph[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TableCellParagraphRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TableCellParagraph::class);
    }

    public function add(TableCellParagraph $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TableCellParagraph $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return TableCellParagraph[] Returns an array of TableCellParagraph objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TableCellParagraph
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
	 * findByTitle
	 * 
	 * @param string $orderBy
     * @return Book[] Returns an array of Book objects
     */
    public function findByTitle($orderBy='ASC') : array
    {
        return $this->createQueryBuilder('t')
            //->andWhere('t.title = :val')
            //->setParameter('val', $value)
            ->orderBy('t.title', $orderBy)
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
	 * findByTitleQuery
	 * 
	 * @param string $orderBy
     * @return Query Returns a query
     */
    public function findByTitleQuery($orderBy='ASC') : Query
    {
        return $this->createQueryBuilder('t')
            //->andWhere('t.title = :val')
            //->setParameter('val', $value)
            ->orderBy('t.title', $orderBy)
            //->setMaxResults(10)
            ->getQuery()
        ;
    }

    /**
	 * findByNbParagraphs
	 * 
	 * @param string $orderBy
     * @return Book[] Returns an array of Book objects
     */
    public function findByNbParagraphs($orderBy='DESC') : array
    {
        return $this->createQueryBuilder('t')
            //->andWhere('t.title = :val')
            //->setParameter('val', $value)
            ->orderBy('t.nbParagraphs', $orderBy)
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
	 * findByParsingTime
	 * 
	 * @param string $orderBy
     * @return Book[] Returns an array of Book objects
     */
    public function findByParsingTime($orderBy='DESC') : array
    {
        return $this->createQueryBuilder('t')
            //->andWhere('t.title = :val')
            //->setParameter('val', $value)
            ->orderBy('t.parsingTime', $orderBy)
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    /**
	 * findByXmlFileSize
	 * 
	 * @param string $orderBy
     * @return Book[] Returns an array of Book objects
     */
    public function findByXmlFileSize($orderBy='DESC') : array
    {
        return $this->createQueryBuilder('t')
            //->andWhere('t.title = :val')
            //->setParameter('val', $value)
            ->orderBy('t.xmlFileSize', $orderBy)
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
	 * findByOdtFileName
	 * 
	 * @param string $orderBy
     * @return Book[] Returns an array of Book objects
     */
    public function findByOdtFileName($orderBy='ASC') : array
    {
        return $this->createQueryBuilder('t')
            //->andWhere('t.title = :val')
            //->setParameter('val', $value)
            ->orderBy('t.OdtFileName', $orderBy)
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
	 * findByAuthor
	 * 
	 * @param Author $author
     * @return Book[] Returns an array of Book objects
     */
    public function findByAuthor($author, $orderBy='ASC') : array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.author = :id')
            ->setParameter('id', $author->getid())
            ->orderBy('t.author', $orderBy)
            //->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
	 * findByDate
	 * 
	 * @param Date $author
     * @return Book[] Returns an array of Book objects
     */
    public function findByDate($orderBy='DESC') : array
    {
        return $this->createQueryBuilder('t')
            // ->andWhere('t.author = :id')
            // ->setParameter('id', $author->getid())
            ->orderBy('t.updatedAt', $orderBy)
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneBySlug($value): ?Book
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.slug = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByOdtBookName($value): ?Book
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.odtBookName = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }



    // /**
    //  * @return Book[] Returns an array of Book objects
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
    public function findOneBySomeField($value): ?Book
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

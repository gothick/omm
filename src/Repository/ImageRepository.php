<?php

namespace App\Repository;

use App\Entity\Image;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function findBetweenDates(DateTime $from, DateTime $to)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.capturedAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('i.capturedAt')
            ->getQuery()
            ->getResult();
    }

    public function findFromIdOnwards(int $id)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id >= :id')
            ->setParameter('id', $id)
            ->orderBy('i.id')
            ->getQuery()
            ->getResult();
    }

    /*
    public function findPrevFromDate(DateTime $from): ?Image
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id  :id')
            ->setParameter('id', $id)
            ->orderBy('i.capturedAt DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    */

    public function findNextByCapturedAtAndId(?DateTimeInterface $capturedAt, ?int $id): ?Image
    {
        // Needs to match the exact sort order of @ORM\OrderBy({"capturedAt" = "ASC", "id" = "ASC"}) in
        // Wander entity images property

        if ($capturedAt == null || $id == null)
            return null;

        // Somewhat tricksy, but we don't have a clean order for images
        return $this->createQueryBuilder('i')
            ->andWhere('i.capturedAt > :capturedAt')
            ->orWhere('i.capturedAt = :capturedAt AND i.id > :id')
            ->setParameter('capturedAt', $capturedAt)
            ->setParameter('id', $id)
            ->orderBy('i.capturedAt')
            ->addOrderBy('i.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPrevByCapturedAtAndId(?DateTimeInterface $capturedAt, ?int $id): ?Image
    {
        // Needs to match the reverse of the exact sort order of
        // @ORM\OrderBy({"capturedAt" = "ASC", "id" = "ASC"}) in Wander entity images property

        if ($capturedAt == null || $id == null)
            return null;

        // Somewhat tricksy, but we don't have a clean order for images
        return $this->createQueryBuilder('i')
            ->andWhere('i.capturedAt < :capturedAt')
            ->orWhere('i.capturedAt = :capturedAt AND i.id < :id')
            ->setParameter('capturedAt', $capturedAt)
            ->setParameter('id', $id)
            ->orderBy('i.capturedAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // /**
    //  * @return Image[] Returns an array of Image objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Image
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

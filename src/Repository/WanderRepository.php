<?php

namespace App\Repository;

use App\Entity\Wander;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Wander|null find($id, $lockMode = null, $lockVersion = null)
 * @method Wander|null findOneBy(array $criteria, array $orderBy = null)
 * @method Wander[]    findAll()
 * @method Wander[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WanderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wander::class);
    }

    public function findAll()
    {
        return $this->findBy(array(), array('startTime' => 'DESC'));
    }

    public function standardQueryBuilder() {
        return $this->createQueryBuilder('w')
            ->orderBy('w.startTime', 'desc');
    }

    public function addWhereHasImages(QueryBuilder $qb, ?bool $hasImages = null)
    {
        if ($hasImages !== null) {
            return $qb->andWhere('w.images is ' . ($hasImages ? 'not' : '') . ' empty');
        }
        return $qb;
    }

    public function findWhereIncludesDate(DateTimeInterface $target)
    {
        return $this->createQueryBuilder('w')
            ->andWhere(':target BETWEEN w.startTime AND w.endTime')
            ->setParameter('target', $target)
            ->orderBy('w.startTime')
            ->getQuery()
            ->getResult();
    }

    public function findShortest()
    {
        return $this->createQueryBuilder('w')
            ->select('w.id, w.distance')
            ->orderBy('w.distance', 'asc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLongest()
    {
        return $this->createQueryBuilder('w')
            ->select('w.id, w.distance')
            ->orderBy('w.distance', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAverageDistance()
    {
        return $this->createQueryBuilder('w')
            ->select('AVG(w.distance)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    // /**
    //  * @return Wander[] Returns an array of Wander objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Wander
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

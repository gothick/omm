<?php

namespace App\Repository;

use App\Entity\Neighbourhood;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Neighbourhood|null find($id, $lockMode = null, $lockVersion = null)
 * @method Neighbourhood|null findOneBy(array $criteria, array $orderBy = null)
 * @method Neighbourhood[]    findAll()
 * @method Neighbourhood[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NeighbourhoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Neighbourhood::class);
    }

    public function findByLatlng(float $lat, float $lng): ?Neighbourhood
    {
        $point = new Point($lng, $lat);
        $queryBuilder = $this->createQueryBuilder('n');

        /** @var ?Neighbourhood $result */
        $result = $queryBuilder
            ->andWhere("st_contains(n.boundingPolygon, :p) = true")
            ->setParameter('p', $point, 'point')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return $result;
    }

    // /**
    //  * @return Neighbourhood[] Returns an array of Neighbourhood objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Neighbourhood
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

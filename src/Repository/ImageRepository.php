<?php

namespace App\Repository;

use App\Entity\Image;
use App\Entity\Wander;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Paginator;

/**
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<\App\Entity\Image>
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function standardQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('i');
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

    public function findWithNoWander()
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.wander IS NULL')
            ->orderBy('i.capturedAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    public function findWithHasLatLng()
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.latlng IS NOT NULL')
            ->orderBy('i.capturedAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    public function findWithNoStreetButHasLatLng()
    {
        $qb = $this->createQueryBuilder('i');
        return $qb
            ->add('where',
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('i.street', "''"),
                        $qb->expr()->isNull('i.street')
                    ),
                    $qb->expr()->isNotNull('i.latlng')
                )
            )
            ->addOrderBy('i.capturedAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    public function findWithNoStreet()
    {
        return $this->createQueryBuilder('i')
        ->andWhere('i.street IS NULL')
        ->orWhere("i.street = ''")
        ->orderBy('i.capturedAt', 'desc')
        ->getQuery()
        ->getResult();
    }


    public function findWithNoNeighbourhood()
    {
        return $this->createQueryBuilder('i')
        ->andWhere('i.neighbourhood IS NULL')
        ->orWhere("i.neighbourhood = ''")
        ->orderBy('i.capturedAt', 'desc')
        ->getQuery()
        ->getResult();
    }

    public function findWithNoNeighbourhoodButHasLatLng()
    {
        $qb = $this->createQueryBuilder('i');
        return $qb
            ->add('where',
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('i.neighbourhood', "''"),
                        $qb->expr()->isNull('i.neighbourhood')
                    ),
                    $qb->expr()->isNotNull('i.latlng')
                )
            )
            ->addOrderBy('i.capturedAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    public function getAllNeighbourhoods(): array
    {
        // TODO: What happens when there aren't any images/neighbourhoods?
        $result = $this->createQueryBuilder('i')
            ->select('i.neighbourhood')
            ->where('i.neighbourhood IS NOT NULL')
            ->groupBy('i.neighbourhood')
            ->orderBy('i.neighbourhood', 'ASC')
            ->getQuery()
            ->getArrayResult();
        return array_column($result, 'neighbourhood');
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

    /**
     * Returns the earliest Image by capturedAt date. Ignores any
     * images without capturedAt dates. Will return null only if
     * there are no Images with capturedAt dates at all.
     */
    public function getEarliestImageOrNull(): ?Image
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.capturedAt')
            ->orderBy('i.id') // might as well be deterministic
            ->andWhere('i.capturedAt IS NOT NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getEarliestImageCaptureDate(): ?DateTimeInterface
    {
        $image = $this->getEarliestImageOrNull();
        if ($image === null) {
            return null;
        }
        return $image->getCapturedAt();
    }

    /**
     * Returns the most recent Image by capturedAt date. Ignores any
     * images without capturedAt dates. Will return null only if
     * there are no Images with capturedAt dates at all.
     */
    public function getLatestImageOrNull(): ?Image
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.capturedAt', 'desc')
            ->orderBy('i.id', 'desc') // might as well be deterministic
            ->andWhere('i.capturedAt IS NOT NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getLatestImageCaptureDate(): ?DateTimeInterface
    {
        $image = $this->getLatestImageOrNull();
        if ($image === null) {
            return null;
        }
        return $image->getCapturedAt();
    }

    public function getPaginatorQueryBuilder(?Wander $wander = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i')
            ->addOrderBy('i.capturedAt')
            ->addOrderBy('i.id'); // tie-breaker
        if ($wander !== null) {
            $qb
                ->andWhere('i.wander = :wander')
                ->setParameter('wander', $wander);
        }
        return $qb;
    }

    public function getReversePaginatorQueryBuilder(?Wander $wander = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i')
            ->addOrderBy('i.capturedAt', 'desc')
            ->addOrderBy('i.id', 'desc'); // tie-breaker
        if ($wander !== null) {
            $qb
                ->andWhere('i.wander = :wander')
                ->setParameter('wander', $wander);
        }
        return $qb;
    }

    public function findNext(Image $image)
    {
        $wander = $image->getWander();
        if ($wander === null) {
            return null;
        }

        $qb = $this->createQueryBuilder('i');
        $qb->add('where', $qb->expr()->andX(
            $qb->expr()->eq('i.wander', ':wander'),
            $qb->expr()->orX(
                $qb->expr()->gt('i.capturedAt', ':capturedAt'),
                $qb->expr()->andX(
                    $qb->expr()->eq('i.capturedAt', ':capturedAt'),
                    $qb->expr()->gt('i.id', ':id')
                )
            )
        ));
        $qb->setParameter('wander', $wander)
            ->setParameter('capturedAt', $image->getCapturedAt())
            ->setParameter('id', $image->getId());

        $qb->addOrderBy('i.capturedAt')
            ->addOrderBy('i.id');
        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findPrev(Image $image)
    {
        $wander = $image->getWander();
        if ($wander === null) {
            return null;
        }

        $qb = $this->createQueryBuilder('i');
        $qb->add('where', $qb->expr()->andX(
            $qb->expr()->eq('i.wander', ':wander'),
            $qb->expr()->orX(
                $qb->expr()->lt('i.capturedAt', ':capturedAt'),
                $qb->expr()->andX(
                    $qb->expr()->eq('i.capturedAt', ':capturedAt'),
                    $qb->expr()->lt('i.id', ':id')
                )
            )
        ));
        $qb->setParameter('wander', $wander)
            ->setParameter('capturedAt', $image->getCapturedAt())
            ->setParameter('id', $image->getId());

        $qb->addOrderBy('i.capturedAt', 'desc')
            ->addOrderBy('i.id', 'desc');
        $qb->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
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

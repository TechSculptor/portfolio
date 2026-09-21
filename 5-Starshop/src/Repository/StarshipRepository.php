<?php

namespace App\Repository;

use App\Entity\Starship;
use App\Model\StarshipStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<Starship>
 */
class StarshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Starship::class);
    }

    public function findIncompleteOrderedByDroidCount(): Pagerfanta
    {
        $query = $this->createQueryBuilder('s')
            ->orderBy('COUNT(starshipDroid)', 'ASC')
            ->leftJoin('s.starshipDroids', 'starshipDroid')
            ->groupBy('s.id')
            ->getQuery()
        ;

        return new Pagerfanta(new QueryAdapter($query));
    }

    /**
     * @return array<int, array{id: int, name: string, status: StarshipStatusEnum, partsCount: int, droidsCount: int, partsValue: string}>
     */
    public function findAllForReport(?StarshipStatusEnum $status = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.id, s.name, s.status')
            ->addSelect('COUNT(DISTINCT part.id) AS partsCount')
            ->addSelect('COUNT(DISTINCT starshipDroid.id) AS droidsCount')
            ->addSelect('COALESCE(SUM(part.price), 0) AS partsValue')
            ->leftJoin('s.parts', 'part')
            ->leftJoin('s.starshipDroids', 'starshipDroid')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
        ;

        if ($status) {
            $qb->andWhere('s.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}

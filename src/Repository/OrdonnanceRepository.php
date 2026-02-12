<?php

namespace App\Repository;

use App\Entity\Ordonnance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ordonnance>
 */
class OrdonnanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ordonnance::class);
    }

    public function findBySearchAndSort(?string $search, ?string $sortBy, ?string $sortOrder = 'ASC')
    {
        $qb = $this->createQueryBuilder('o');

        if ($search) {
            $qb->andWhere('o.posologie LIKE :search OR o.frequence LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSortFields = ['posologie', 'frequence', 'dureeTraitement'];
        if ($sortBy && in_array($sortBy, $allowedSortFields)) {
            $qb->orderBy('o.' . $sortBy, $sortOrder === 'DESC' ? 'DESC' : 'ASC');
        }

        return $qb->getQuery()->getResult();
    }
}

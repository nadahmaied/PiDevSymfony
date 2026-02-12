<?php

namespace App\Repository;

use App\Entity\Fiche;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fiche>
 */
class FicheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fiche::class);
    }

    public function findBySearchAndSort(?string $search, ?string $sortBy, ?string $sortOrder = 'ASC')
    {
        $qb = $this->createQueryBuilder('f');

        if ($search) {
            $qb->andWhere('f.libelleMaladie LIKE :search OR f.gravite LIKE :search OR f.recommandation LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSortFields = ['libelleMaladie', 'date', 'gravite'];
        if ($sortBy && in_array($sortBy, $allowedSortFields)) {
            $qb->orderBy('f.' . $sortBy, $sortOrder === 'DESC' ? 'DESC' : 'ASC');
        }

        return $qb->getQuery()->getResult();
    }
}

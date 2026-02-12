<?php

namespace App\Repository;

use App\Entity\Medicament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Medicament>
 */
class MedicamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medicament::class);
    }

    public function findBySearchAndSort(?string $search, ?string $sortBy, ?string $sortOrder = 'ASC')
    {
        $qb = $this->createQueryBuilder('m');

        if ($search) {
            $qb->andWhere('m.nomMedicament LIKE :search OR m.categorie LIKE :search OR m.dosage LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSortFields = ['nomMedicament', 'categorie', 'dateExpiration'];
        if ($sortBy && in_array($sortBy, $allowedSortFields)) {
            $qb->orderBy('m.' . $sortBy, $sortOrder === 'DESC' ? 'DESC' : 'ASC');
        }

        return $qb->getQuery()->getResult();
    }
}

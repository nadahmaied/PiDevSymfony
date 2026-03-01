<?php

namespace App\Repository;

use App\Entity\MissionVolunteer;
use App\Entity\Volunteer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<MissionVolunteer>
 */
class MissionVolunteerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MissionVolunteer::class);
    }

//    /**
//     * @return MissionVolunteer[] Returns an array of MissionVolunteer objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MissionVolunteer
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function findBySearchQuery(?string $searchTerm, ?string $statut = null, string $applicationsFilter = 'all'): Query
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.dateDebut', 'DESC');

        if ($searchTerm) {
            $qb->andWhere('m.titre LIKE :term OR m.description LIKE :term OR m.lieu LIKE :term')
                ->setParameter('term', '%' . $searchTerm . '%');
        }

        if ($statut) {
            $qb->andWhere('m.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($applicationsFilter === 'with') {
            $qb->andWhere(
                $qb->expr()->exists(
                    'SELECT 1 FROM ' . Volunteer::class . ' v2 WHERE v2.mission = m'
                )
            );
        } elseif ($applicationsFilter === 'without') {
            $qb->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        'SELECT 1 FROM ' . Volunteer::class . ' v2 WHERE v2.mission = m'
                    )
                )
            );
        }

        return $qb->getQuery();
    }
}

<?php

namespace App\Repository;

use App\Entity\MissionVolunteer;
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
public function findBySearchQuery(?string $searchTerm, ?string $statut = null): Query
{
    $qb = $this->createQueryBuilder('m')
        ->orderBy('m.dateDebut', 'DESC'); // Tri par défaut : les plus récentes d'abord

    // 1. Recherche par mot-clé (Titre, Ville ou Description)
    if ($searchTerm) {
        $qb->andWhere('m.titre LIKE :term OR m.description LIKE :term OR m.lieu LIKE :term')
           ->setParameter('term', '%' . $searchTerm . '%');
    }

    // 2. Filtre par statut (pour le Front qui ne veut que les "Ouverte")
    if ($statut) {
        $qb->andWhere('m.statut = :statut')
           ->setParameter('statut', $statut);
    }

    return $qb->getQuery();
}
}

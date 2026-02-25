<?php

namespace App\Repository;

use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Medecin>
 */
class MedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medecin::class);
    }
    public function search(?string $specialite, ?string $nom, ?string $type): array
    {
        $qb = $this->createQueryBuilder('m');

        if ($specialite) {
            $qb->andWhere('m.specialite = :specialite')
               ->setParameter('specialite', $specialite);
        }

        if ($nom) {
            $qb->andWhere('m.nom LIKE :nom OR m.prenom LIKE :nom')
               ->setParameter('nom', '%' . $nom . '%');
        }

        if ($type) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('m.nom', 'ASC')->getQuery()->getResult();
    }
    //    /**
    //     * @return Medecin[] Returns an array of Medecin objects
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

    //    public function findOneBySomeField($value): ?Medecin
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findSpecialitesByIds(array $ids): array
{
    if (empty($ids)) return [];
    $result = $this->createQueryBuilder('m')
        ->select('DISTINCT m.specialite')
        ->where('m.id IN (:ids)')
        ->andWhere('m.disponible = true')
        ->setParameter('ids', $ids)
        ->orderBy('m.specialite', 'ASC')
        ->getQuery()->getResult();
    return array_column($result, 'specialite');
}

public function findBySpecialiteAndIds(string $specialite, array $ids): array
{
    if (empty($ids)) return [];
    return $this->createQueryBuilder('m')
        ->where('m.specialite = :specialite')
        ->andWhere('m.id IN (:ids)')
        ->andWhere('m.disponible = true')
        ->setParameter('specialite', $specialite)
        ->setParameter('ids', $ids)
        ->orderBy('m.nom', 'ASC')
        ->getQuery()->getResult();
}
}

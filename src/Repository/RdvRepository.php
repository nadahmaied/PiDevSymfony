<?php

namespace App\Repository;

use App\Entity\Rdv;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rdv>
 */
class RdvRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rdv::class);
    }

//    /**
//     * @return Rdv[] Returns an array of Rdv objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Rdv
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
public function searchGlobal(string $query): array
{
    return $this->createQueryBuilder('r')
        ->where('r.medecin LIKE :q')
        ->orWhere('r.statut LIKE :q')
        ->orWhere('r.motif LIKE :q')
        ->orWhere('r.message LIKE :q')
        ->setParameter('q', '%' . $query . '%')
        ->orderBy('r.date', 'ASC')
        ->addOrderBy('r.hdebut', 'ASC')
        ->getQuery()
        ->getResult();
}
public function findPasses(): array
{
    $now = new \DateTime();
    return $this->createQueryBuilder('r')
        ->where('r.date < :today')
        ->orWhere('r.date = :today AND r.hdebut < :now')
        ->setParameter('today', $now->format('Y-m-d'))
        ->setParameter('now', $now->format('H:i:s'))
        ->orderBy('r.date', 'DESC')
        ->addOrderBy('r.hdebut', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findByDate(\DateTime $date): array
{
    return $this->createQueryBuilder('r')
        ->where('r.date = :date')
        ->setParameter('date', $date->format('Y-m-d'))
        ->getQuery()
        ->getResult();
}
public function findByMedecinAndDate(int $medecinId, \DateTime $date): array
{
    return $this->createQueryBuilder('r')
        ->where('r.date = :date')
        ->andWhere('r.statut != :annule')
        ->setParameter('date', $date->format('Y-m-d'))
        ->setParameter('annule', 'Annulé')
        ->getQuery()
        ->getResult();
}

// ✅ Ajouter cette méthode dans RdvRepository.php

public function findPassesByPatient(User $patient): array
{
    return $this->createQueryBuilder('r')
        ->where('r.date < :today')
        ->andWhere('r.patient = :patient')
        ->setParameter('today', new \DateTime('today'))
        ->setParameter('patient', $patient)
        ->orderBy('r.date', 'DESC')
        ->getQuery()
        ->getResult();
}
}

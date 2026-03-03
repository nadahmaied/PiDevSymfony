<?php

namespace App\Repository;

use App\Entity\Disponibilite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Disponibilite>
 */
class DisponibiliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Disponibilite::class);
    }

    /**
     * Toutes les dispos d'un médecin pour une date (tous statuts)
     */
    /** @return list<Disponibilite> */
    public function findByMedecinAndDate(int $medId, \DateTime $date): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.MedId = :medId')
            ->andWhere('d.dateDispo = :date')
            ->setParameter('medId', $medId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('d.hdebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cherche si une séance fixe est annulée (statut=non_disponible)
     * Utilisé pour toggle annulation séance matin/soir
     */
    public function findSeanceAnnulee(int $medId, \DateTime $date, string $hdebut, string $hfin): ?Disponibilite
    {
        return $this->createQueryBuilder('d')
            ->where('d.MedId = :medId')
            ->andWhere('d.dateDispo = :date')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.hdebut = :hdebut')
            ->andWhere('d.hFin = :hfin')
            ->setParameter('medId', $medId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('statut', 'non_disponible')
            ->setParameter('hdebut', \DateTime::createFromFormat('H:i', $hdebut))
            ->setParameter('hfin', \DateTime::createFromFormat('H:i', $hfin))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Cherche si le créneau extra (12h-14h ou 10h-14h) existe déjà (statut=disponible)
     * Utilisé pour toggle ajout/suppression du créneau extra
     */
    public function findExtraExistant(int $medId, \DateTime $date, string $hdebut, string $hfin): ?Disponibilite
    {
        return $this->createQueryBuilder('d')
            ->where('d.MedId = :medId')
            ->andWhere('d.dateDispo = :date')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.hdebut = :hdebut')
            ->andWhere('d.hFin = :hfin')
            ->setParameter('medId', $medId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('statut', 'disponible')
            ->setParameter('hdebut', \DateTime::createFromFormat('H:i', $hdebut))
            ->setParameter('hfin', \DateTime::createFromFormat('H:i', $hfin))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les IDs des médecins disponibles pour une date donnée
     */
    /** @return list<int> */
    public function findMedecinIdsDisponibles(\DateTime $date): array
    {
        $result = $this->createQueryBuilder('d')
            ->select('DISTINCT d.MedId')
            ->where('d.dateDispo = :date')
            ->andWhere('d.statut = :statut')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('statut', 'disponible')
            ->getQuery()
            ->getResult();

        return array_column($result, 'MedId');
    }

    /**
     * Toutes les dispos actives d'un médecin (calendrier)
     */
    /** @return list<Disponibilite> */
    public function findByMedecin(int $medecinId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.MedId = :medId')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.dateDispo >= :today')
            ->setParameter('medId', $medecinId)
            ->setParameter('statut', 'disponible')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->orderBy('d.dateDispo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Dates disponibles pour un mois donné
     */
    /** @return list<string> */
    public function findDatesDisponiblesDuMois(int $year, int $month): array
    {
        $debut = new \DateTime("$year-$month-01");
        $fin   = (clone $debut)->modify('last day of this month');

        $result = $this->createQueryBuilder('d')
            ->select('DISTINCT d.dateDispo')
            ->where('d.dateDispo BETWEEN :debut AND :fin')
            ->andWhere('d.statut = :statut')
            ->setParameter('debut', $debut->format('Y-m-d'))
            ->setParameter('fin', $fin->format('Y-m-d'))
            ->setParameter('statut', 'disponible')
            ->getQuery()
            ->getResult();

        return array_values(array_map(
            static fn (array $r): string => $r['dateDispo']->format('Y-m-d'),
            $result
        ));
    }
}

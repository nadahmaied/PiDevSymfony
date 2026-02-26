<?php

namespace App\Repository;

use App\Entity\Ordonnance;
use App\Entity\User;
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

    /**
     * @return Ordonnance[]
     */
    public function findByPatient(User $patient, int $limit = null, int $offset = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.idRdv', 'r')
            ->andWhere('r.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('o.id', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
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

    public function findOneByScanToken(string $token): ?Ordonnance
    {
        return $this->findOneBy(['scanToken' => $token], null);
    }

    /**
     * Counts ordonnances per month for the last N months (patient via rdv).
     *
     * @return array<array{month: string, count: int}>
     */
    public function countByMonthForPatient(User $patient, int $months = 6): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $months = (int) $months;
        $rows = $conn->executeQuery("
            SELECT DATE_FORMAT(o.date_ordonnance, '%Y-%m') AS month, COUNT(*) AS cnt
            FROM ordonnance o
            INNER JOIN rdv r ON r.id = o.id_rdv_id
            WHERE r.patient_id = :patientId
            AND o.date_ordonnance >= DATE_SUB(NOW(), INTERVAL {$months} MONTH)
            GROUP BY DATE_FORMAT(o.date_ordonnance, '%Y-%m')
            ORDER BY month ASC
        ", ['patientId' => $patient->getId()])->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['month' => $row['month'], 'count' => (int) $row['cnt']];
        }
        return $result;
    }
}

<?php

namespace App\Repository;

use App\Entity\LigneOrdonnance;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneOrdonnance>
 */
class LigneOrdonnanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneOrdonnance::class);
    }

    /**
     * Counts occurrences of momentPrise values for a patient's prescriptions.
     *
     * @return array<string, int>
     */
    public function countByMomentPrise(User $patient): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->executeQuery("
            SELECT lo.moment_prise AS momentPrise, COUNT(*) AS cnt
            FROM ligne_ordonnance lo
            INNER JOIN ordonnance o ON o.id = lo.ordonnance_id
            INNER JOIN rdv r ON r.id = o.id_rdv_id
            WHERE lo.moment_prise IS NOT NULL AND r.patient_id = :patientId
            GROUP BY lo.moment_prise
            ORDER BY cnt DESC
        ", ['patientId' => $patient->getId()])->fetchAllAssociative();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['momentPrise']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Counts occurrences of avantRepas (true/false) for a patient's prescriptions.
     *
     * @return array{avant: int, apres: int}
     */
    public function countByAvantRepas(User $patient): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->executeQuery("
            SELECT lo.avant_repas AS avantRepas, COUNT(*) AS cnt
            FROM ligne_ordonnance lo
            INNER JOIN ordonnance o ON o.id = lo.ordonnance_id
            INNER JOIN rdv r ON r.id = o.id_rdv_id
            WHERE r.patient_id = :patientId
            GROUP BY lo.avant_repas
        ", ['patientId' => $patient->getId()])->fetchAllAssociative();
        $avant = 0;
        $apres = 0;
        foreach ($rows as $row) {
            if ((bool) $row['avantRepas']) {
                $avant = (int) $row['cnt'];
            } else {
                $apres = (int) $row['cnt'];
            }
        }
        return ['avant' => $avant, 'apres' => $apres];
    }

    /**
     * Top N prescribed medicaments for a patient (via rdv).
     *
     * @return array<array{name: string, count: int}>
     */
    public function getTopMedicaments(User $patient, int $limit = 6): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $limit = (int) $limit;
        $rows = $conn->executeQuery("
            SELECT m.nom_medicament AS name, COUNT(lo.id) AS cnt
            FROM ligne_ordonnance lo
            INNER JOIN ordonnance o ON o.id = lo.ordonnance_id
            INNER JOIN rdv r ON r.id = o.id_rdv_id
            INNER JOIN medicament m ON m.id = lo.medicament_id
            WHERE r.patient_id = :patientId
            GROUP BY m.nom_medicament
            ORDER BY cnt DESC
            LIMIT {$limit}
        ", ['patientId' => $patient->getId()])->fetchAllAssociative();
        return array_map(fn ($r) => ['name' => $r['name'], 'count' => (int) $r['cnt']], $rows);
    }

    /**
     * Distribution of frequenceParJour for a patient.
     *
     * @return array<string, int>  e.g. ["1x/jour" => 3, "2x/jour" => 5]
     */
    public function getFrequenceParJourDistribution(User $patient): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->executeQuery("
            SELECT lo.frequence_par_jour AS freq, COUNT(*) AS cnt
            FROM ligne_ordonnance lo
            INNER JOIN ordonnance o ON o.id = lo.ordonnance_id
            INNER JOIN rdv r ON r.id = o.id_rdv_id
            WHERE r.patient_id = :patientId
            GROUP BY lo.frequence_par_jour
            ORDER BY freq ASC
        ", ['patientId' => $patient->getId()])->fetchAllAssociative();
        $result = [];
        foreach ($rows as $row) {
            $label = $row['freq'] . 'x/jour';
            $result[$label] = (int) $row['cnt'];
        }
        return $result;
    }
}

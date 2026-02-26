<?php

namespace App\Repository;

use App\Entity\Fiche;
use App\Entity\User;
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

    public function findLatestByPatient(User $patient): ?Fiche
    {
        return $this->findOneBy(
            ['idU' => $patient],
            ['date' => 'DESC']
        );
    }

    /**
     * Aggregates average tension (systolic) and glycémie by month for the last 6 months.
     * Tension is parsed as "X/Y" (e.g. "12/8") - uses first number for average.
     *
     * @return array<int, array{month: string, tension: float, glycemie: float}>
     */
    public function getVitalsByMonthLast6Months(User $patient): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                DATE_FORMAT(f.date, '%Y-%m') AS month,
                AVG(CAST(SUBSTRING_INDEX(f.tension, '/', 1) AS DECIMAL(10,2))) AS tension,
                AVG(f.glycemie) AS glycemie
            FROM fiche f
            WHERE f.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            AND f.id_u_id = :patientId
            GROUP BY DATE_FORMAT(f.date, '%Y-%m')
            ORDER BY month ASC
        ";
        $result = $conn->executeQuery($sql, ['patientId' => $patient->getId()])->fetchAllAssociative();
        $out = [];
        foreach ($result as $row) {
            $out[] = [
                'month' => $row['month'],
                'tension' => round((float) $row['tension'], 1),
                'glycemie' => round((float) $row['glycemie'], 1),
            ];
        }
        return $out;
    }

    /**
     * Returns top N symptômes by frequency for a patient (symptomes is TEXT, may contain comma-separated values).
     *
     * @return array<array{symptome: string, count: int}>
     */
    public function getTopSymptomes(User $patient, int $limit = 5): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->executeQuery(
            "SELECT symptomes FROM fiche WHERE symptomes IS NOT NULL AND symptomes != '' AND id_u_id = :patientId",
            ['patientId' => $patient->getId()]
        )->fetchAllAssociative();
        $counts = [];
        foreach ($rows as $row) {
            $symptomes = array_map('trim', explode(',', (string) $row['symptomes']));
            foreach ($symptomes as $s) {
                if ($s !== '') {
                    $counts[$s] = ($counts[$s] ?? 0) + 1;
                }
            }
        }
        arsort($counts);
        $top = array_slice($counts, 0, $limit, true);
        return array_map(fn ($s, $c) => ['symptome' => $s, 'count' => $c], array_keys($top), $top);
    }
}

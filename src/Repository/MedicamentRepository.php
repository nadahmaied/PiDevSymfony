<?php

namespace App\Repository;

use App\Entity\Medicament;
use App\Entity\User;
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

    /**
     * Returns each medicament with how many prescription lines reference it.
     */
    public function findWithPrescriptionUsage(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.lignesOrdonnance', 'lo')
            ->addSelect('COUNT(lo.id) AS usageCount')
            ->groupBy('m.id')
            ->orderBy('m.nomMedicament', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns prescribed count (for this patient) and stock count (medicament count) per category for radar chart.
     *
     * @return array<array{categorie: string, prescribed: int, stock: int}>
     */
    public function getCategoryPrescribedVsStock(User $patient): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $prescribed = $conn->executeQuery("
            SELECT m.categorie, COUNT(lo.id) AS prescribed
            FROM ligne_ordonnance lo
            INNER JOIN ordonnance o ON lo.ordonnance_id = o.id
            INNER JOIN rdv r ON r.id = o.id_rdv_id
            INNER JOIN medicament m ON lo.medicament_id = m.id
            WHERE r.patient_id = :patientId
            GROUP BY m.categorie
        ", ['patientId' => $patient->getId()])->fetchAllAssociative();
        $stock = $conn->executeQuery("
            SELECT categorie, COUNT(*) AS stock FROM medicament GROUP BY categorie
        ")->fetchAllAssociative();
        $stockMap = [];
        foreach ($stock as $row) {
            $stockMap[$row['categorie']] = (int) $row['stock'];
        }
        $result = [];
        foreach ($prescribed as $row) {
            $cat = $row['categorie'];
            $result[] = [
                'categorie' => $cat,
                'prescribed' => (int) $row['prescribed'],
                'stock' => $stockMap[$cat] ?? 0,
            ];
        }
        return $result;
    }
}

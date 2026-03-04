<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\Annonce;
use App\Repository\AnnonceRepository;

final class DonationMatcherService
{
    public function __construct(
        private readonly AnnonceRepository $annonceRepository,
    ) {
    }

    /**
     * If the donation is not linked to an annonce, try to find a compatible one
     * based on typeDon appearing in the annonce title or description.
     *
     * Returns the matched annonce or null if none found.
     */
    public function autoLink(Donation $donation): ?Annonce
    {
        if ($donation->getAnnonce() !== null) {
            return null;
        }

        $type = trim(mb_strtolower((string) $donation->getTypeDon()));
        if ($type === '') {
            return null;
        }

        $qb = $this->annonceRepository->createQueryBuilder('a')
            ->andWhere('LOWER(a.titreAnnonce) LIKE :type OR LOWER(a.description) LIKE :type')
            ->andWhere('a.etatAnnonce = :state')
            ->setParameter('type', '%' . $type . '%')
            ->setParameter('state', 'active')
            ->setMaxResults(1);

        /** @var Annonce|null $annonce */
        $annonce = $qb->getQuery()->getOneOrNullResult();

        if ($annonce !== null) {
            $donation->setAnnonce($annonce);
        }

        return $annonce;
    }
}


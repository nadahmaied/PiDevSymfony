<?php

namespace App\Controller;

use App\Entity\Rdv;
use App\Repository\RdvRepository;
use App\Repository\MedecinRepository;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/rdv')]
final class AdminRdvController extends AbstractController
{
    #[Route('', name: 'admin_rdv_index')]
    public function index(Request $request, RdvRepository $rdvRepo, MedecinRepository $medecinRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $filterMedecin = $request->query->get('medecin', '');
        $filterStatut  = $request->query->get('statut', '');
        $filterDate    = $request->query->get('date', '');

        $qb = $rdvRepo->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC')
            ->addOrderBy('r.hdebut', 'DESC');

        if ($filterMedecin) {
            $qb->andWhere('r.medecin = :medecin')->setParameter('medecin', $filterMedecin);
        }
        if ($filterStatut) {
            $qb->andWhere('r.statut = :statut')->setParameter('statut', $filterStatut);
        }
        if ($filterDate) {
            $qb->andWhere('r.date = :date')->setParameter('date', new \DateTime($filterDate));
        }

        $rdvs    = $qb->getQuery()->getResult();
        $allRdvs = $rdvRepo->findAll();
        $today   = new \DateTime('today');

        $stats = [
            'total'      => count($allRdvs),
            'aujourdhui' => count(array_filter($allRdvs, fn($r) =>
                $r->getDate() && $r->getDate()->format('Y-m-d') === $today->format('Y-m-d')
            )),
            'en_attente' => count(array_filter($allRdvs, fn($r) => $r->getStatut() === 'En attente')),
            'confirmes'  => count(array_filter($allRdvs, fn($r) => $r->getStatut() === 'Confirmé')),
            'annules'    => count(array_filter($allRdvs, fn($r) => $r->getStatut() === 'Annulé')),
        ];

        return $this->render('rdv_admin/rdv_index.html.twig', [
            'rdvs'          => $rdvs,
            'medecins'      => $medecinRepo->findAll(),
            'stats'         => $stats,
            'filterMedecin' => $filterMedecin,
            'filterStatut'  => $filterStatut,
            'filterDate'    => $filterDate,
        ]);
    }

    #[Route('/stats', name: 'admin_rdv_stats')]
    public function stats(RdvRepository $rdvRepo, MedecinRepository $medecinRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $allRdvs  = $rdvRepo->findAll();
        $medecins = $medecinRepo->findAll();
        $today    = new \DateTime('today');

        $total      = count($allRdvs);
        $confirmes  = count(array_filter($allRdvs, fn($r) => $r->getStatut() === 'Confirmé'));
        $annules    = count(array_filter($allRdvs, fn($r) => $r->getStatut() === 'Annulé'));
        $enAttente  = count(array_filter($allRdvs, fn($r) => $r->getStatut() === 'En attente'));
        $aujourdhui = count(array_filter($allRdvs, fn($r) =>
            $r->getDate() && $r->getDate()->format('Y-m-d') === $today->format('Y-m-d')
        ));

        $rdvParMedecin = [];
        foreach ($medecins as $med) {
            $nomMed = 'Dr. ' . $med->getPrenom() . ' ' . $med->getNom();
            $count  = count(array_filter($allRdvs, fn($r) => $r->getMedecin() === $nomMed));
            if ($count > 0) $rdvParMedecin[$nomMed] = $count;
        }
        arsort($rdvParMedecin);

        $rdvParMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = new \DateTime("first day of -$i month");
            $rdvParMois[$mois->format('M Y')] = count(array_filter($allRdvs, fn($r) =>
                $r->getDate() && $r->getDate()->format('Y-m') === $mois->format('Y-m')
            ));
        }

        $rdvParSpecialite = [];
        foreach ($allRdvs as $rdv) {
            if (!$rdv->getMedecin()) continue;
            foreach ($medecins as $med) {
                if ($rdv->getMedecin() === 'Dr. ' . $med->getPrenom() . ' ' . $med->getNom()) {
                    $spec = $med->getSpecialite() ?? 'Autre';
                    $rdvParSpecialite[$spec] = ($rdvParSpecialite[$spec] ?? 0) + 1;
                    break;
                }
            }
        }

        $rdvParMotif = [];
        foreach ($allRdvs as $rdv) {
            $motif = $rdv->getMotif() ?? 'Non précisé';
            $rdvParMotif[$motif] = ($rdvParMotif[$motif] ?? 0) + 1;
        }
        arsort($rdvParMotif);
        $rdvParMotif = array_slice($rdvParMotif, 0, 5, true);

        return $this->render('rdv_admin/rdv_stats.html.twig', [
            'total'            => $total,
            'confirmes'        => $confirmes,
            'annules'          => $annules,
            'enAttente'        => $enAttente,
            'aujourdhui'       => $aujourdhui,
            'rdvParMedecin'    => $rdvParMedecin,
            'rdvParMois'       => $rdvParMois,
            'rdvParSpecialite' => $rdvParSpecialite,
            'rdvParMotif'      => $rdvParMotif,
            'tauxConfirmation' => $total > 0 ? round($confirmes / $total * 100) : 0,
            'tauxAnnulation'   => $total > 0 ? round($annules  / $total * 100) : 0,
        ]);
    }

    #[Route('/dispos', name: 'admin_dispo_index')]
    public function dispos(DisponibiliteRepository $dispoRepo, MedecinRepository $medecinRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $medecins        = $medecinRepo->findAll();
        $dispoParMedecin = [];

        foreach ($medecins as $med) {
            $dispos = $dispoRepo->findBy(['MedId' => $med->getId()], ['dateDispo' => 'ASC']);
            $dispoParMedecin[] = [
                'medecin' => $med,
                'nom'     => 'Dr. ' . $med->getPrenom() . ' ' . $med->getNom(),
                'dispos'  => $dispos,
                'total'   => count($dispos),
            ];
        }

        return $this->render('rdv_admin/dispo_index.html.twig', [
            'dispoParMedecin' => $dispoParMedecin,
        ]);
    }

    #[Route('/confirm/{id}', name: 'admin_rdv_confirm', methods: ['POST'])]
    public function confirm(Rdv $rdv, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $rdv->setStatut('Confirmé');
        $em->flush();
        $this->addFlash('success', 'RDV confirmé avec succès.');
        return $this->redirectToRoute('admin_rdv_index');
    }

    #[Route('/cancel/{id}', name: 'admin_rdv_cancel', methods: ['POST'])]
    public function cancel(Rdv $rdv, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $rdv->setStatut('Annulé');
        $em->flush();
        $this->addFlash('success', 'RDV annulé.');
        return $this->redirectToRoute('admin_rdv_index');
    }

    #[Route('/delete/{id}', name: 'admin_rdv_delete', methods: ['POST'])]
    public function delete(Request $request, Rdv $rdv, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('admin_delete_rdv_' . $rdv->getId(), $request->request->get('_token'))) {
            $em->remove($rdv);
            $em->flush();
            $this->addFlash('success', 'RDV supprimé.');
        }
        return $this->redirectToRoute('admin_rdv_index');
    }
}
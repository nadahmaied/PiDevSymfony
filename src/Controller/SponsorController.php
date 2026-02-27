<?php

namespace App\Controller;

use App\Entity\Sponsor;
use App\Form\SponsorType;
use App\Repository\MissionVolunteerRepository;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/sponsor')]
final class SponsorController extends AbstractController
{
    #[Route(name: 'app_sponsor_index', methods: ['GET'])]
    public function index(SponsorRepository $sponsorRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $searchTerm = $request->query->get('q');

        $qb = $sponsorRepository->createQueryBuilder('s');

        if ($searchTerm) {
            $qb->andWhere('s.nomSociete LIKE :search OR s.contactEmail LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        if (!$request->query->get('sort')) {
            $qb->orderBy('s.nomSociete', 'ASC');
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('sponsor/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_sponsor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, MailerInterface $mailer): Response
    {
        $sponsor = new Sponsor();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('mission_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Erreur upload
                }

                $sponsor->setLogo($newFilename);
            }

            $entityManager->persist($sponsor);
            $entityManager->flush();

            $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'Arial');
            $dompdf = new Dompdf($pdfOptions);

            $html = $this->renderView('sponsor/invoice.html.twig', [
                'sponsor' => $sponsor,
                'date' => new \DateTime()
            ]);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdfContent = $dompdf->output();

            $email = (new Email())
                ->from('benhamoudafiras19@gmail.com')
                ->to($sponsor->getContactEmail())
                ->subject('Merci pour votre soutien ! Voici votre reçu.')
                ->html('<p>Bonjour,</p><p>Nous vous remercions chaleureusement pour votre engagement envers HealthTrack.</p><p>Veuillez trouver ci-joint votre reçu de sponsoring officiel au format PDF.</p><p>Cordialement,<br>L\'équipe VitalTech</p>')
                ->attach($pdfContent, 'Recu_HealthTrack_'.$sponsor->getId().'.pdf', 'application/pdf');

            $mailer->send($email);

            $this->addFlash('success', 'Sponsor ajouté, logo uploadé et reçu envoyé par e-mail avec succès !');

            return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor/new.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/missions/search', name: 'app_sponsor_missions_search', methods: ['GET'])]
    public function searchMissions(Request $request, MissionVolunteerRepository $missionRepository): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(20, max(5, (int) $request->query->get('limit', 10)));

        $qb = $missionRepository->createQueryBuilder('m')
            ->orderBy('m.dateDebut', 'DESC');

        if ($query !== '') {
            $qb
                ->andWhere('m.titre LIKE :term OR m.lieu LIKE :term OR m.description LIKE :term')
                ->setParameter('term', '%' . $query . '%');
        }

        $offset = ($page - 1) * $limit;

        $missions = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit + 1)
            ->getQuery()
            ->getResult();

        $hasMore = count($missions) > $limit;
        if ($hasMore) {
            array_pop($missions);
        }

        $items = array_map(static function ($mission): array {
            return [
                'id' => $mission->getId(),
                'title' => (string) $mission->getTitre(),
                'location' => (string) $mission->getLieu(),
                'status' => (string) $mission->getStatut(),
            ];
        }, $missions);

        return $this->json([
            'items' => $items,
            'page' => $page,
            'hasMore' => $hasMore,
        ]);
    }

    #[Route('/{id}', name: 'app_sponsor_show', methods: ['GET'])]
    public function show(Sponsor $sponsor): Response
    {
        return $this->render('sponsor/show.html.twig', [
            'sponsor' => $sponsor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sponsor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sponsor $sponsor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor/edit.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sponsor_delete', methods: ['POST'])]
    public function delete(Request $request, Sponsor $sponsor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sponsor->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($sponsor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
    }
}

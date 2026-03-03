<?php

namespace App\Service;

use App\Entity\Ordonnance;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PrescriptionMailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $siteBaseUrl,
    ) {
    }

    /**
     * Sends the prescription email to the patient.
     *
     * @return bool True if sent successfully, false otherwise
     */
    public function sendPrescription(Ordonnance $ordonnance): bool
    {
        $patient = $ordonnance->getIdRdv()?->getPatient();
        if (!$patient || !$patient->getEmail()) {
            return false;
        }

        $scanUrl = $this->buildScanUrl($ordonnance);
        $qrDataUri = $this->generateQrDataUri($ordonnance);

        $medications = [];
        foreach ($ordonnance->getLignesOrdonnance() as $ligne) {
            $med = $ligne->getMedicament();
            if ($med) {
                $medications[] = [
                    'name' => $med->getNomMedicament(),
                    'dosage' => $med->getDosage(),
                    'forme' => $med->getForme(),
                    'nbJours' => $ligne->getNbJours(),
                    'frequenceParJour' => $ligne->getFrequenceParJour(),
                    'momentPrise' => $ligne->getMomentPrise(),
                    'periode' => $ligne->getPeriode(),
                ];
            }
        }

        $patientName = trim(($patient->getPrenom() ?? '') . ' ' . ($patient->getNom() ?? ''));
        $dateOrdonnance = $ordonnance->getDateOrdonnance()?->format('d/m/Y') ?? date('d/m/Y');

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@prescription.local', 'Prescription Médicale'))
            ->to(new Address($patient->getEmail(), $patientName))
            ->subject('Votre ordonnance médicale – ' . $dateOrdonnance)
            ->htmlTemplate('emails/prescription.html.twig')
            ->context([
                'patientName' => $patientName ?: 'Patient',
                'dateOrdonnance' => $dateOrdonnance,
                'ordonnance' => $ordonnance,
                'medications' => $medications,
                'scanUrl' => $scanUrl,
                'qrDataUri' => $qrDataUri,
            ]);

        try {
            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            return false;
        }
    }

    private function buildScanUrl(Ordonnance $ordonnance): string
    {
        $token = $ordonnance->getScanToken();
        if (!$token) {
            return rtrim($this->siteBaseUrl, '/') . '/ordonnance';
        }

        return rtrim($this->siteBaseUrl, '/') . $this->urlGenerator->generate(
            'app_ordonnance_scan',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    private function generateQrDataUri(Ordonnance $ordonnance): string
    {
        $scanUrl = $this->buildScanUrl($ordonnance);

        $builder = Builder::create()
            ->writer(new SvgWriter())
            ->data($scanUrl)
            ->size(200)
            ->margin(8);
        $result = $builder->build();

        return $result->getDataUri();
    }
}

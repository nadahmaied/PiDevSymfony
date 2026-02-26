<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Fiche;
use App\Entity\LigneOrdonnance;
use App\Entity\Medicament;
use App\Entity\Ordonnance;
use App\Entity\Rdv;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SeederController extends AbstractController
{
    #[Route('/seed', name: 'app_seed')]
    public function seed(EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        // 1. Create a Doctor
        $doctor = $em->getRepository(User::class)->findOneBy(['email' => 'doctor@healthtrack.com']);
        if (!$doctor) {
            $doctor = new User();
            $doctor->setEmail('doctor@healthtrack.com');
            $doctor->setNom('Dupont');
            $doctor->setPrenom('Jean');
            $doctor->setRole('ROLE_MEDECIN');
            $doctor->setPassword($hasher->hashPassword($doctor, 'password123'));
            $em->persist($doctor);
        }

        // 2. Create 3 Patients
        $patientsData = [
            ['Alice', 'Martin', 'patient@healthtrack.com'],
            ['Bob', 'Durand', 'bob@healthtrack.com'],
            ['Charlie', 'Lefebvre', 'charlie@healthtrack.com']
        ];

        $patients = [];
        foreach ($patientsData as $data) {
            $p = $em->getRepository(User::class)->findOneBy(['email' => $data[2]]);
            if (!$p) {
                $p = new User();
                $p->setPrenom($data[0]);
                $p->setNom($data[1]);
                $p->setEmail($data[2]);
                $p->setRole('ROLE_USER');
                $p->setPassword($hasher->hashPassword($p, 'password123'));
                $em->persist($p);
            }
            $patients[] = $p;
        }

        // 3. Create 5 Medicaments
        $meds = [
            ['Doliprane', 'Antalgique', '1000mg', 'Comprimé'],
            ['Amoxicilline', 'Antibiotique', '500mg', 'Gélule'],
            ['Spasfon', 'Antispasmodique', '80mg', 'Lyoc'],
            ['Voltarene', 'Anti-inflammatoire', '75mg', 'Gel'],
            ['Zyrtec', 'Antihistaminique', '10mg', 'Comprimé'],
        ];

        $medicamentEntities = [];
        foreach ($meds as $m) {
            $med = $em->getRepository(Medicament::class)->findOneBy(['nomMedicament' => $m[0]]);
            if (!$med) {
                $med = new Medicament();
                $med->setNomMedicament($m[0]);
                $med->setCategorie($m[1]);
                $med->setDosage($m[2]);
                $med->setForme($m[3]);
                $med->setDateExpiration(new \DateTime('+2 years'));
                $em->persist($med);
            }
            $medicamentEntities[] = $med;
        }

        // Apply changes to ensure IDs exist for linking
        $em->flush();

        // 4. Create 3 Fiches & 3 Rdv & 3 Ordonnances
        $fichesData = [
            [65.5, 170, 'O+', '12/8', 0.95, 'Contrôle annuel', 'faible'],
            [82.0, 185, 'A-', '13/9', 1.10, 'Grippe saisonnière', 'moyenne'],
            [54.0, 162, 'B+', '11/7', 0.88, 'Hypertension légère', 'élevée']
        ];

        for ($i = 0; $i < 3; $i++) {
            $fiche = new Fiche();
            $fiche->setPoids($fichesData[$i][0]);
            $fiche->setTaille($fichesData[$i][1]);
            $fiche->setGrpSanguin($fichesData[$i][2]);
            $fiche->setTension($fichesData[$i][3]);
            $fiche->setGlycemie($fichesData[$i][4]);
            $fiche->setDate(new \DateTime());
            $fiche->setLibelleMaladie($fichesData[$i][5]);
            $fiche->setGravite($fichesData[$i][6]);
            $fiche->setSymptomes('Symptômes patient #' . ($i + 1));
            $fiche->setIdU($patients[$i]);
            $em->persist($fiche);

            $rdv = new Rdv();
            $rdv->setPatient($patients[$i]);
            $em->persist($rdv);

            $ord = new Ordonnance();
            $ord->setPosologie('Traitement ' . ($i + 1));
            $ord->setFrequence('Matin et Soir');
            $ord->setDureeTraitement(7 + $i);
            $ord->setDateOrdonnance(new \DateTime());
            $ord->setIdU($doctor);
            $ord->setIdRdv($rdv);

            $ligne1 = new LigneOrdonnance();
            $ligne1->setMedicament($medicamentEntities[$i]);
            $ligne1->setNbJours(7 + $i);
            $ligne1->setFrequenceParJour(2);
            $ligne1->setMomentPrise('Matin');
            $ligne1->setAvantRepas(false);
            $ligne1->setPeriode('Quotidien');
            $ord->addLignesOrdonnance($ligne1);

            $ligne2 = new LigneOrdonnance();
            $ligne2->setMedicament($medicamentEntities[$i + 1]);
            $ligne2->setNbJours(7 + $i);
            $ligne2->setFrequenceParJour(1);
            $ligne2->setMomentPrise('Soir');
            $ligne2->setAvantRepas(true);
            $ligne2->setPeriode('Quotidien');
            $ord->addLignesOrdonnance($ligne2);

            $em->persist($ord);
        }

        $em->flush();

        // 5. Create test patient azer58134@gmail.com with Fiche + Ordonnance
        $testEmail = 'azer58134@gmail.com';
        $testPatient = $em->getRepository(User::class)->findOneBy(['email' => $testEmail]);
        if (!$testPatient) {
            $testPatient = new User();
            $testPatient->setEmail($testEmail);
            $testPatient->setNom('Test');
            $testPatient->setPrenom('Patient');
            $testPatient->setRole('ROLE_USER');
            $testPatient->setPassword($hasher->hashPassword($testPatient, 'password123'));
            $em->persist($testPatient);
        }

        $testFiche = $em->getRepository(Fiche::class)->findOneBy(['idU' => $testPatient]);
        if (!$testFiche) {
            $testFiche = new Fiche();
            $testFiche->setPoids(72.0);
            $testFiche->setTaille(175);
            $testFiche->setGrpSanguin('O+');
            $testFiche->setTension('12/8');
            $testFiche->setGlycemie(1.0);
            $testFiche->setDate(new \DateTime());
            $testFiche->setLibelleMaladie('Contrôle de routine avec symptômes légers');
            $testFiche->setGravite('faible');
            $testFiche->setRecommandation('Repos et hydratation. Suivi si persistance des symptômes.');
            $testFiche->setSymptomes('Fatigue, maux de tête légers, tension normale');
            $testFiche->setIdU($testPatient);
            $em->persist($testFiche);
        }

        $testRdv = new Rdv();
        $testRdv->setPatient($testPatient);
        $em->persist($testRdv);

        $testOrd = new Ordonnance();
        $testOrd->setPosologie('Traitement test - Doliprane et Zyrtec selon besoin');
        $testOrd->setFrequence('Matin et Soir');
        $testOrd->setDureeTraitement(7);
        $testOrd->setDateOrdonnance(new \DateTime());
        $testOrd->setIdU($doctor);
        $testOrd->setIdRdv($testRdv);

        $l1 = new LigneOrdonnance();
        $l1->setMedicament($medicamentEntities[0]);
        $l1->setNbJours(7);
        $l1->setFrequenceParJour(2);
        $l1->setMomentPrise('Matin et Soir');
        $l1->setAvantRepas(false);
        $l1->setPeriode('Quotidien');
        $testOrd->addLignesOrdonnance($l1);

        $l2 = new LigneOrdonnance();
        $l2->setMedicament($medicamentEntities[4]);
        $l2->setNbJours(5);
        $l2->setFrequenceParJour(1);
        $l2->setMomentPrise('Matin');
        $l2->setAvantRepas(true);
        $l2->setPeriode('Quotidien');
        $testOrd->addLignesOrdonnance($l2);

        $em->persist($testOrd);
        $em->flush();

        return new Response('Database seeded with 3 records for each entity + test patient azer58134@gmail.com! <br><br>
            <strong>Test patient:</strong> ' . $testEmail . ' (password: password123)<br><br>
            <a href="/login">Se connecter</a> puis :<br>
            <a href="/patient/dossier">Mon Dossier (patient)</a> | 
            <a href="/patient/fiche/' . $testFiche->getId() . '">Voir ma fiche + IA</a> | 
            <a href="/">Accueil</a>');
    }
}

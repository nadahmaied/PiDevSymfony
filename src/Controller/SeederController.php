<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Fiche;
use App\Entity\Medicament;
use App\Entity\Ordonnance;
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
        // 1. Clear some data if needed (optional, safer to just add)
        
        // 2. Create a Doctor
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

        // 3. Create a Patient
        $patient = $em->getRepository(User::class)->findOneBy(['email' => 'patient@healthtrack.com']);
        if (!$patient) {
            $patient = new User();
            $patient->setEmail('patient@healthtrack.com');
            $patient->setNom('Martin');
            $patient->setPrenom('Alice');
            $patient->setRole('ROLE_USER');
            $patient->setPassword($hasher->hashPassword($patient, 'password123'));
            $em->persist($patient);
        }

        // 4. Create Medicaments
        $meds = [
            ['Doliprane', 'Antalgique', '1000mg', 'Comprimé'],
            ['Amoxicilline', 'Antibiotique', '500mg', 'Gélule'],
            ['Spasfon', 'Antispasmodique', '80mg', 'Lyoc'],
            ['Voltarene', 'Anti-inflammatoire', '75mg', 'Gel'],
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

        // 5. Create Fiche
        $fiche = new Fiche();
        $fiche->setPoids(65.5);
        $fiche->setTaille(170);
        $fiche->setGrpSanguin('O+');
        $fiche->setAllergie('Pénicilline');
        $fiche->setMaladieChronique('Néant');
        $fiche->setTension('12/8');
        $fiche->setGlycemie(0.95);
        $fiche->setDate(new \DateTime());
        $fiche->setLibelleMaladie('Contrôle annuel');
        $fiche->setGravite('Faible');
        $fiche->setRecommandation('Continuer l\'exercice régulier.');
        $fiche->setIdU($patient);
        $em->persist($fiche);

        // 6. Create Ordonnance
        $ordonnance = new Ordonnance();
        $ordonnance->setPosologie('1 comprimé 3 fois par jour');
        $ordonnance->setFrequence('Matin, Midi, Soir');
        $ordonnance->setDureeTraitement(7);
        $ordonnance->setIdU($doctor);
        $ordonnance->setIdFiche($fiche);
        $ordonnance->addMedicament($medicamentEntities[0]);
        $ordonnance->addMedicament($medicamentEntities[2]);
        $em->persist($ordonnance);

        $em->flush();

        return new Response('Database seeded successfully with test data! <br> 
            Doctor: doctor@healthtrack.com / password123 <br>
            Patient: patient@healthtrack.com / password123 <br>
            <a href="/">Go to Home</a>');
    }
}

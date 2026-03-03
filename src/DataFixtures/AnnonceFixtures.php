<?php

namespace App\DataFixtures;

use App\Entity\Annonce;
use DateTime;
use Faker\Factory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AnnonceFixtures extends Fixture
{
    /** Nombre d'annonces à générer (modifier ici pour changer) */
    private const NUMBER_OF_ANNONCES = 20;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        
        // Niveaux d'urgence possibles
        $niveauxUrgence = ['faible', 'moyenne', 'élevée'];
        
        // États possibles
        $etats = ['active', 'clôturée'];
        
        // Exemples de titres réalistes pour des annonces médicales/santé
        $titresExemples = [
            'Besoin urgent de matériel médical',
            'Recherche de dons de sang',
            'Appel à la solidarité pour un patient',
            'Besoin de médicaments spécifiques',
            'Urgence : équipement médical requis',
            'Appel aux dons pour une intervention chirurgicale',
            'Besoin de matériel de première nécessité',
            'Recherche de bénévoles pour une campagne',
            'Urgence médicale : besoin de soutien',
            'Appel à la communauté pour un cas urgent',
            'Besoin de fournitures médicales',
            'Recherche de dons pour traitement médical',
            'Urgence : aide médicale nécessaire',
            'Besoin de matériel pour soins intensifs',
            'Appel aux dons pour une famille dans le besoin',
            'Besoin urgent de fauteuil roulant',
            'Recherche de prothèses médicales',
            'Appel à la solidarité pour soins pédiatriques',
            'Besoin de matériel de diagnostic',
            'Urgence : équipement de réanimation',
        ];
        
        for ($i = 0; $i < self::NUMBER_OF_ANNONCES; $i++) {
            $annonce = new Annonce();
            
            // Générer un titre réaliste en français
            $titreBaseRaw = $faker->randomElement($titresExemples);
            $titreBase = is_array($titreBaseRaw) ? implode(' ', $titreBaseRaw) : $titreBaseRaw;
            // Parfois ajouter un complément pour varier
            if ($faker->boolean(30)) {
                $words = $faker->words(rand(2, 4), true);
                $complement = ' - ' . (is_array($words) ? implode(' ', $words) : $words);
                $titreComplet = $titreBase . $complement;
            } else {
                $titreComplet = $titreBase;
            }
            
            // S'assurer que le titre respecte les contraintes (5-150 caractères)
            if (strlen($titreComplet) > 150) {
                $titreComplet = substr($titreComplet, 0, 147) . '...';
            }
            $annonce->setTitreAnnonce($titreComplet);
            
            // Générer une description cohérente avec le titre
            $description = match ($titreBase) {
                'Besoin urgent de matériel médical' =>
                    "Nous recherchons en urgence du matériel médical (gants stériles, compresses, désinfectant, pansements, etc.) afin d'assurer des soins dans de bonnes conditions. Les stocks actuels sont insuffisants et chaque contribution, même modeste, permettra d'améliorer la prise en charge des patients.",
                'Recherche de dons de sang' =>
                    "Un patient est actuellement hospitalisé et nécessite plusieurs transfusions de sang. Nous lançons un appel aux donneurs compatibles pour effectuer un don dans les plus brefs délais. Votre geste peut réellement sauver une vie et offrir une nouvelle chance à cette personne.",
                'Appel à la solidarité pour un patient' =>
                    "Nous sollicitons la solidarité de chacun pour venir en aide à un patient en situation médicale et sociale très fragile. Les dons serviront à couvrir une partie des frais médicaux et des besoins de base. Toute forme de soutien, matériel ou financier, est la bienvenue.",
                'Besoin de médicaments spécifiques' =>
                    "Certains médicaments indispensables au traitement d'un patient ne sont plus disponibles dans son entourage immédiat. Nous recherchons des personnes disposées à faire don de ces médicaments, dans le respect des dates de péremption et des conditions de conservation.",
                'Urgence : équipement médical requis' =>
                    "Un service de soins est confronté à un manque critique d'équipement médical (tensiomètres, thermomètres, oxymètres, etc.). Sans ce matériel, le suivi des patients est fortement compromis. Nous faisons appel à votre générosité pour nous aider à compléter cet équipement.",
                'Appel aux dons pour une intervention chirurgicale' =>
                    "Un patient doit subir une intervention chirurgicale importante dont le coût dépasse largement les moyens de sa famille. Les dons collectés serviront à financer l'opération, les examens préopératoires et le suivi post-opératoire. Chaque don rapproche cette famille de l'objectif.",
                'Besoin de matériel de première nécessité' =>
                    "Nous soutenons des patients et leurs proches qui manquent de matériel de première nécessité (produits d'hygiène, couvertures, vêtements de base). Vos dons permettront d'améliorer leur quotidien pendant la période de soins et d'hospitalisation.",
                'Recherche de bénévoles pour une campagne' =>
                    "Une campagne de sensibilisation et de dépistage est en cours d'organisation et nous avons besoin de bénévoles pour l'accueil, l'orientation et le soutien logistique. Aucune compétence médicale n'est requise, uniquement de la motivation et de la bienveillance.",
                'Urgence médicale : besoin de soutien' =>
                    "Face à une situation médicale urgente touchant plusieurs patients, nous avons besoin d'un soutien rapide sous forme de dons matériels et financiers. Ces ressources permettront de couvrir les besoins immédiats en soins, en médicaments et en accompagnement.",
                'Appel à la communauté pour un cas urgent' =>
                    "Nous faisons appel à la communauté pour soutenir un cas médical particulièrement urgent. Les fonds collectés serviront à financer les examens, traitements et déplacements nécessaires. Votre aide, quelle que soit son importance, sera précieuse pour cette famille.",
                'Besoin de fournitures médicales' =>
                    "Plusieurs patients suivis en ambulatoire manquent de fournitures médicales essentielles (seringues, compresses, pansements, gants, etc.). Nous recherchons des dons de matériel neuf afin de garantir des soins sûrs et de limiter les risques d'infection.",
                'Recherche de dons pour traitement médical' =>
                    "Un traitement médical de longue durée doit être démarré au plus vite, mais son coût pèse lourdement sur le budget familial. Les dons permettront de financer les médicaments, les consultations régulières et les examens nécessaires au suivi de la maladie.",
                'Urgence : aide médicale nécessaire' =>
                    "Une situation d'urgence médicale nécessite une mobilisation rapide pour financer des soins immédiats. Les dons serviront à couvrir les frais d'hospitalisation, de médicaments et de suivi. Chaque participation contribue concrètement au rétablissement du patient.",
                'Besoin de matériel pour soins intensifs' =>
                    "Le service de soins intensifs manque de certains équipements et consommables indispensables pour la surveillance continue des patients. Votre soutien aidera à acquérir ce matériel et à garantir une prise en charge optimale des cas les plus graves.",
                'Appel aux dons pour une famille dans le besoin' =>
                    "Une famille est durement touchée par la maladie d'un de ses membres et rencontre d'importantes difficultés financières. Les dons collectés serviront à couvrir les frais médicaux ainsi que certaines dépenses de vie courante afin de leur apporter un minimum de stabilité.",
                'Besoin urgent de fauteuil roulant' =>
                    "Nous recherchons en urgence un fauteuil roulant en bon état pour une personne dont la mobilité est fortement réduite. Cet équipement est essentiel pour lui permettre de se déplacer, de se rendre aux rendez-vous médicaux et de préserver un minimum d'autonomie.",
                'Recherche de prothèses médicales' =>
                    "Un patient a besoin de prothèses médicales adaptées afin de retrouver une meilleure qualité de vie et de reprendre certaines activités du quotidien. Les dons serviront à financer l'achat ou l'adaptation de ces dispositifs, souvent très coûteux.",
                'Appel à la solidarité pour soins pédiatriques' =>
                    "Nous lançons un appel à la solidarité pour soutenir des enfants nécessitant des soins pédiatriques spécialisés. Les contributions permettront de financer des traitements, des examens complémentaires et un accompagnement adapté aux besoins de chaque enfant.",
                'Besoin de matériel de diagnostic' =>
                    "Afin d'améliorer la prise en charge des patients, nous recherchons du matériel de diagnostic (glucomètres, appareils de tension, oxymètres, etc.). Ce matériel permettra de détecter plus rapidement certaines pathologies et d'adapter les traitements.",
                'Urgence : équipement de réanimation' =>
                    "Un besoin urgent en équipement de réanimation a été identifié pour renforcer la capacité de prise en charge des urgences vitales. Les dons contribueront à l'acquisition de dispositifs indispensables comme les respirateurs, les moniteurs et les accessoires associés.",
                default => (function () use ($faker): string {
                    $paragraphs = $faker->paragraphs(rand(2, 4), true);
                    return is_array($paragraphs) ? implode("\n", $paragraphs) : $paragraphs;
                })(),
            };
            $annonce->setDescription($description);
            
            // Générer une date de publication aléatoire (entre 1 an avant et aujourd'hui)
            $datePublication = $faker->dateTimeBetween('-1 year', 'now');
            $annonce->setDatePublication($datePublication);
            
            // Sélectionner un niveau d'urgence aléatoire
            $urgence = $faker->randomElement($niveauxUrgence);
            $annonce->setUrgence($urgence);
            
            // Sélectionner un état aléatoire
            $etat = $faker->randomElement($etats);
            $annonce->setEtatAnnonce($etat);
            
            $manager->persist($annonce);
        }
        
        $manager->flush();
    }
}

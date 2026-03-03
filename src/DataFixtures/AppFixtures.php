<?php

namespace App\DataFixtures;

use App\Entity\Annonce;
use App\Entity\Donation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class AppFixtures extends Fixture
{
    private Generator $faker;

    /** Statuts possibles pour les dons */
    private const STATUTS = ['en attente', 'accepté', 'accepté', 'refusé'];

    /**
     * Pour chaque annonce (index dans ANNONCES_DATA), types de don cohérents avec le titre.
     * Ex. : annonce "Don de sang" → seulement sang (ou plasma/plaquettes si pertinent).
     */
    private const ANNONCE_ALLOWED_TYPES = [
        ['sang'],                                    // 0 Besoin urgent de sang
        ['plasma'],                                  // 1 Recherche donneur de plasma
        ['vêtements'],                               // 2 Collecte vêtements et couvertures
        ['médicaments'],                              // 3 Dons de médicaments dispensaire
        ['sang'],                                    // 4 Urgence sang rare O-
        ['matériel scolaire'],                        // 5 Matériel scolaire école
        ['plaquettes'],                               // 6 Besoin de plaquettes
        ['nourriture', 'produits d\'hygiène'],        // 7 Collecte nourriture et hygiène
        ['équipement médical'],                       // 8 Équipement médical handicap
        ['sang'],                                    // 9 Don de sang campagne hôpital
        ['vêtements'],                               // 10 Vêtements enfants réfugiées
        ['médicaments'],                              // 11 Médicaments recyclage solidaire
        ['soutien financier'],                        // 12 Enfant brûlé - greffe
        ['jouets et livres'],                         // 13 Jouets et livres Noël
        ['plasma'],                                  // 14 Plasma convalescent
    ];

    /**
     * Quantités réalistes par type de don (min, max).
     * Sang/plasma/plaquettes = unités (1-2), médicaments = boîtes, vêtements = articles, etc.
     */
    private const TYPE_QUANTITY_RANGE = [
        'sang' => [1, 2],
        'plasma' => [1, 2],
        'plaquettes' => [1, 2],
        'médicaments' => [1, 10],
        'vêtements' => [1, 15],
        'nourriture' => [1, 12],
        'équipement médical' => [1, 3],
        'matériel scolaire' => [1, 20],
        'produits d\'hygiène' => [1, 15],
        'soutien financier' => [1, 1],
        'jouets et livres' => [1, 10],
    ];

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $annonces = $this->createAnnonces($manager);
        $this->createDonations($manager, $annonces);

        $manager->flush();
    }

    /** Titres et descriptions réalistes (chaque description correspond au titre même indice) */
    private const ANNONCES_DATA = [
        [
            'titre' => 'Besoin urgent de sang pour opération cardiaque',
            'description' => "Un patient de 58 ans doit subir un pontage coronarien la semaine prochaine. L'établissement manque de réserves pour son groupe (A+). Toute personne éligible au don de sang peut se présenter au centre de collecte ou contacter l'EFS. Votre don peut sauver une vie.",
        ],
        [
            'titre' => 'Recherche donneur de plasma pour enfant malade',
            'description' => "Un enfant de 6 ans suit un traitement qui nécessite des transfusions de plasma régulières. Nous recherchons des donneurs compatibles (groupe AB de préférence) pour assurer la continuité des soins. Merci de vous manifester si vous pouvez donner.",
        ],
        [
            'titre' => 'Collecte de vêtements et couvertures pour familles démunies',
            'description' => "L'association Solidaire organise une collecte pour des familles hébergées en urgence. Nous avons besoin de vêtements chauds, couvertures et chaussures en bon état, toutes tailles. Les dons peuvent être déposés à notre local jusqu'au 15 du mois.",
        ],
        [
            'titre' => 'Appel aux dons de médicaments pour dispensaire rural',
            'description' => "Le dispensaire de [village] manque de médicaments essentiels (antalgiques, antipyrétiques, traitements chroniques). Si vous avez des boîtes non entamées et encore valides, merci de nous les confier. Nous assurons une redistribution gratuite aux patients.",
        ],
        [
            'titre' => 'Urgence : sang rare (groupe O-) pour accident de la route',
            'description' => "Un conducteur gravement blessé est en réanimation et nécessite des culots de sang O négatif (donneur universel). Les réserves régionales sont basses. Si vous êtes O-, merci de vous présenter au plus tôt à l'EFS ou à l'hôpital indiqué.",
        ],
        [
            'titre' => 'Don de matériel scolaire pour école en zone défavorisée',
            'description' => "Une école en zone rurale a besoin de fournitures : cahiers, stylos, livres, cartables. Les familles n'ont pas les moyens d'équiper tous les enfants. Nous collectons le matériel neuf ou en très bon état pour la rentrée prochaine.",
        ],
        [
            'titre' => 'Besoin de plaquettes pour patient en chimiothérapie',
            'description' => "Un adulte sous chimiothérapie présente une baisse importante des plaquettes. Les dons de plaquettes (aphérèse) sont indispensables pour éviter les risques hémorragiques. Une séance dure environ 1h30. Merci de contacter l'EFS pour prendre rendez-vous.",
        ],
        [
            'titre' => 'Collecte nourriture et produits d\'hygiène - sans-abri',
            'description' => "La maraude du samedi distribue repas chauds et kits d'hygiène aux personnes sans domicile. Nous manquons de conserves, pâtes, riz, et de savon, dentifrice, serviettes hygiéniques. Toute contribution est bienvenue.",
        ],
        [
            'titre' => 'Équipement médical recherché pour association handicap',
            'description' => "Notre association prête du matériel (déambulateurs, fauteuils roulants, lits médicalisés) aux familles. Plusieurs pièces sont en panne ou obsolètes. Nous recherchons du matériel en bon état ou des financements pour en racheter.",
        ],
        [
            'titre' => 'Don de sang : campagne hôpital régional',
            'description' => "L'hôpital lance sa campagne annuelle de don du sang. Les réserves doivent être reconstituées avant l'été. Collecte organisée dans le hall principal tous les mardis et jeudis. Collation offerte après le don. Venez nombreux.",
        ],
        [
            'titre' => 'Vêtements enfants et bébés pour familles réfugiées',
            'description' => "Des familles réfugiées récemment accueillies n'ont que les vêtements qu'elles portaient. Nous cherchons des vêtements pour enfants de 0 à 12 ans et bébés (bodys, pyjamas, chaussures), en bon état. Merci de laver les vêtements avant dépôt.",
        ],
        [
            'titre' => 'Médicaments chroniques non utilisés - recyclage solidaire',
            'description' => "Si vous avez des médicaments non utilisés (ordonnance modifiée, arrêt de traitement), ne les jetez pas. Notre association les récupère, vérifie les dates, et les redistribue à des patients dans le besoin via des partenaires agréés.",
        ],
        [
            'titre' => 'Appel aux dons pour enfant brûlé - greffe de peau',
            'description' => "Un enfant de 4 ans a été brûlé et doit subir plusieurs greffes de peau. La famille a besoin de soutien financier pour les frais non couverts et les déplacements. Chaque don compte. Merci pour votre solidarité.",
        ],
        [
            'titre' => 'Collecte jouets et livres pour Noël des oubliés',
            'description' => "Pour les fêtes, nous offrons des jouets et livres à des enfants qui n'en reçoivent pas. Nous acceptons jouets neufs ou en très bon état, livres jeunesse. Les dons sont remis à des structures sociales et familles identifiées.",
        ],
        [
            'titre' => 'Plasma convalescent recherché - service réanimation',
            'description' => "Le service de réanimation utilise du plasma de patients guéris pour certains protocoles. Si vous avez été infecté récemment et êtes guéri, votre plasma peut aider. Contacter le service don du sang de l'hôpital pour vérifier l'éligibilité.",
        ],
    ];

    /**
     * @return Annonce[]
     */
    private function createAnnonces(ObjectManager $manager): array
    {
        $urgenceLevels = ['faible', 'moyenne', 'élevée'];
        $etats = ['active', 'active', 'active', 'clôturée'];

        $annonces = [];
        $data = self::ANNONCES_DATA;

        for ($i = 0; $i < count($data); $i++) {
            $row = $data[$i];
            $annonce = new Annonce();
            $annonce->setTitreAnnonce($row['titre']);
            $annonce->setDescription($row['description']);
            $annonce->setDatePublication($this->faker->dateTimeBetween('today', '+1 month'));
            $annonce->setUrgence($urgenceLevels[array_rand($urgenceLevels)]);
            $annonce->setEtatAnnonce($etats[array_rand($etats)]);

            $manager->persist($annonce);
            $annonces[] = $annonce;
        }

        return $annonces;
    }

    /**
     * @param Annonce[] $annonces
     */
    private function createDonations(ObjectManager $manager, array $annonces): void
    {
        $countAnnonces = count($annonces);
        $allowedByAnnonce = self::ANNONCE_ALLOWED_TYPES;
        $quantityByType = self::TYPE_QUANTITY_RANGE;

        for ($i = 0; $i < 25; $i++) {
            $donation = new Donation();
            $typeDon = null;
            $quantite = 1;

            // Environ 80 % des dons liés à une annonce cohérente
            if ($this->faker->boolean(80) && $countAnnonces > 0) {
                $annonceIndex = $this->faker->numberBetween(0, $countAnnonces - 1);
                $donation->setAnnonce($annonces[$annonceIndex]);
                $allowedTypes = $allowedByAnnonce[$annonceIndex] ?? ['sang'];
                $typeDon = $allowedTypes[array_rand($allowedTypes)];
            } else {
                // 20 % sans annonce : type au hasard parmi tous les types définis
                $allTypes = array_keys($quantityByType);
                $typeDon = $allTypes[array_rand($allTypes)];
            }

            $range = $quantityByType[$typeDon];
            $quantite = $this->faker->numberBetween($range[0], $range[1]);

            $donation->setTypeDon($typeDon);
            $donation->setQuantite($quantite);
            $donation->setDateDonation($this->faker->dateTimeBetween('-2 months', 'now'));
            $donation->setStatut(self::STATUTS[array_rand(self::STATUTS)]);

            $manager->persist($donation);
        }
    }
}

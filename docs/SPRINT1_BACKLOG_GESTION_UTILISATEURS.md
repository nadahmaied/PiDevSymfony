# Sprint 1 – Backlog – Gestion des utilisateurs

**Projet :** VitalTech  
**Sprint :** 1  
**Domaine :** Gestion des utilisateurs (CRUD + fonctionnalités avancées)

---

## 1. User Stories et tâches – CRUD Utilisateurs

### US 1.1 – Inscription (Signup)

| ID US | User Story (US) | ID Tâche | Tâches (analyse → conception → dev → test) | Estimation | Responsable |
|-------|------------------|----------|---------------------------------------------|------------|-------------|
| 1.1 | En tant que visiteur, je peux m'inscrire avec email, nom, prénom, mot de passe et rôle (Médecin ou Patient) pour créer un compte. | T1.1 | En tant que concepteur, je dois préparer le diagramme de cas d'utilisation (inscription) et le diagramme de séquence. | 45 min | |
| 1.1 | | T1.2 | En tant qu'admin BD, je dois m'assurer que la table `user` existe avec les champs email (unique), password, nom, prenom, adresse, telephone, role. | 30 min | |
| 1.1 | | T1.3 | En tant que développeur, je dois créer le formulaire d'inscription (FrontRegistrationFormType) avec contrôle de saisie (email, nom/prénom sans chiffres, téléphone chiffres uniquement, mot de passe min 6 caractères). | 1h 30 | |
| 1.1 | | T1.4 | En tant que développeur, je dois implémenter l'action d'inscription (FrontAuthController::register), hash du mot de passe, attribution du rôle (ROLE_MEDECIN ou ROLE_PATIENT). | 1h | |
| 1.1 | | T1.5 | En tant que développeur, je dois gérer le cas "email déjà existant" (UniqueEntity sur User) et afficher le message d'erreur. | 30 min | |
| 1.1 | | T1.6 | En tant que testeur, je dois préparer et exécuter les scénarios de test (inscription valide, email dupliqué, champs invalides). | 1h | |

---

### US 1.2 – Connexion / Déconnexion (Login / Logout)

| ID US | User Story (US) | ID Tâche | Tâches | Estimation | Responsable |
|-------|------------------|----------|--------|------------|-------------|
| 1.2 | En tant qu'utilisateur, je peux me connecter avec mon email et mot de passe et me déconnecter. | T2.1 | En tant que concepteur, je dois documenter le flux d'authentification (form_login, firewall, redirection). | 30 min | |
| 1.2 | | T2.2 | En tant que développeur, je dois configurer Security (security.yaml) : provider User, form_login, logout, access_control. | 45 min | |
| 1.2 | | T2.3 | En tant que développeur, je dois créer la page de connexion unique (front_login) et le lien "Mot de passe oublié". | 45 min | |
| 1.2 | | T2.4 | En tant que développeur, je dois afficher le bouton "Backoffice" dans la navbar front uniquement pour ROLE_ADMIN après connexion. | 30 min | |
| 1.2 | | T2.5 | En tant que testeur, je dois tester connexion (succès/échec), déconnexion, redirection selon le rôle. | 1h | |

---

### US 1.3 – Consultation et modification du profil (Mon compte)

| ID US | User Story (US) | ID Tâche | Tâches | Estimation | Responsable |
|-------|------------------|----------|--------|------------|-------------|
| 1.3 | En tant qu'utilisateur connecté, je peux consulter et modifier mes informations (email, nom, prénom, adresse, téléphone) et supprimer mon compte. | T3.1 | En tant que concepteur, je dois préparer le diagramme de séquence "Consultation / Modification profil". | 30 min | |
| 1.3 | | T3.2 | En tant que développeur, je dois créer AccountController (show, edit, delete) avec CSRF pour la suppression. | 1h | |
| 1.3 | | T3.3 | En tant que développeur, je dois créer le formulaire UserProfileType et les vues account/show, account/edit avec contrôle de saisie. | 1h | |
| 1.3 | | T3.4 | En tant que testeur, je dois tester affichage profil, modification, suppression de compte (avec confirmation). | 45 min | |

---

### US 1.4 – Backoffice : liste, recherche, tri, pagination des utilisateurs

| ID US | User Story (US) | ID Tâche | Tâches | Estimation | Responsable |
|-------|------------------|----------|--------|------------|-------------|
| 1.4 | En tant qu'administrateur, je peux consulter la liste des utilisateurs avec recherche (email, nom, prénom), tri par colonne et pagination (3 par page). | T4.1 | En tant que concepteur, je dois décrire le flux "liste utilisateurs" (query params q, sort, direction, page). | 30 min | |
| 1.4 | | T4.2 | En tant que développeur, je dois intégrer KnpPaginatorBundle et adapter AdminUserController::index (searchAndSort + paginate). | 1h | |
| 1.4 | | T4.3 | En tant que développeur, je dois créer la vue admin/user_index avec formulaire de recherche, en-têtes de tableau cliquables pour le tri, et knp_pagination_render. | 1h | |
| 1.4 | | T4.4 | En tant que testeur, je dois tester recherche, tri (ASC/DESC), pagination et accès réservé ROLE_ADMIN. | 45 min | |

---

### US 1.5 – Backoffice : modification et suppression d’un utilisateur

| ID US | User Story (US) | ID Tâche | Tâches | Estimation | Responsable |
|-------|------------------|----------|--------|------------|-------------|
| 1.5 | En tant qu'administrateur, je peux modifier les informations d'un utilisateur et supprimer un utilisateur. | T5.1 | En tant que développeur, je dois ajouter les routes admin_user_edit et admin_user_delete (POST, CSRF). | 30 min | |
| 1.5 | | T5.2 | En tant que développeur, je dois créer la vue admin/user_edit (formulaire UserProfileType) et les boutons Modifier / Supprimer dans la liste. | 1h | |
| 1.5 | | T5.3 | En tant que testeur, je dois tester édition et suppression d'un utilisateur depuis le backoffice. | 45 min | |

---

## 2. User Stories – Fonctionnalités avancées

### US 2.1 – Réinitialisation du mot de passe (envoi d’e-mails)

| ID US | User Story (US) | ID Tâche | Tâches | Estimation | Responsable |
|-------|------------------|----------|--------|------------|-------------|
| 2.1 | En tant qu'utilisateur, je peux demander une réinitialisation de mot de passe par e-mail et définir un nouveau mot de passe via un lien sécurisé. | T6.1 | En tant que concepteur, je dois préparer le diagramme de séquence "Demande reset password" (forgot → token → email → reset). | 45 min | |
| 2.1 | | T6.2 | En tant qu'admin BD, je dois créer l'entité PasswordResetToken (token, user, expiresAt, usedAt) et la table associée. | 45 min | |
| 2.1 | | T6.3 | En tant que développeur, je dois configurer le mailer (ex. Gmail avec symfony/google-mailer) et le template d'e-mail (emails/reset_password.html.twig). | 1h | |
| 2.1 | | T6.4 | En tant que développeur, je dois implémenter ResetPasswordController (forgot_password, reset_password avec token) et les formulaires ForgotPasswordRequestType, ResetPasswordFormType. | 1h 30 | |
| 2.1 | | T6.5 | En tant que développeur, je dois ajouter le lien "Mot de passe oublié ?" sur la page de connexion. | 15 min | |
| 2.1 | | T6.6 | En tant que testeur, je dois tester le scénario complet : demande → réception email → clic lien → nouveau mot de passe → connexion. | 1h | |

---

### US 2.2 – Upload d’image de profil (avatar)

| ID US | User Story (US) | ID Tâche | Tâches | Estimation | Responsable |
|-------|------------------|----------|--------|------------|-------------|
| 2.2 | En tant qu'utilisateur, je peux uploader une photo de profil qui s'affiche dans la navbar et sur ma page compte. | T7.1 | En tant que concepteur, je dois décrire le flux d'upload (formulaire → stockage fichier → champ User). | 30 min | |
| 2.2 | | T7.2 | En tant qu'admin BD, je dois ajouter un champ (ex. photoFilename ou avatarPath) à l'entité User et une migration. | 30 min | |
| 2.2 | | T7.3 | En tant que développeur, je dois installer et configurer VichUploaderBundle (ou équivalent) et lier le champ à l'entité User. | 1h | |
| 2.2 | | T7.4 | En tant que développeur, je dois ajouter le champ image au formulaire de profil (édition) et afficher l'avatar dans baseFront, baseBack et account/show. | 1h | |
| 2.2 | | T7.5 | En tant que testeur, je dois tester upload (taille, type), affichage et suppression/remplacement de la photo. | 45 min | |

---

### US 2.3 – Contrôle de saisie des formulaires

| ID US | User Story (US) | ID Tâche | Tâches | Estimation | Responsable |
|-------|------------------|----------|--------|------------|-------------|
| 2.3 | En tant qu'utilisateur, je bénéficie de contrôles de saisie (validation) sur tous les formulaires (email valide, noms sans chiffres, téléphone chiffres uniquement, mot de passe minimal, email unique). | T8.1 | En tant que concepteur, je dois lister les règles de validation par champ (User entity + formulaires). | 30 min | |
| 2.3 | | T8.2 | En tant que développeur, je dois ajouter les contraintes sur l'entité User (Assert\Email, Assert\Regex pour nom/prenom/telephone, UniqueEntity pour email). | 45 min | |
| 2.3 | | T8.3 | En tant que développeur, je dois afficher les messages d'erreur de validation dans les vues (form_errors) et gérer le cas "email déjà existant". | 30 min | |
| 2.3 | | T8.4 | En tant que testeur, je dois exécuter les cas de test (champs vides, formats invalides, doublon email). | 45 min | |

---

### US 2.4 – Validation du compte Médecin (API KYC)

| ID US | User Story (US) | ID Tâche | Tâches | Estimation | Responsable |
|-------|------------------|----------|--------|------------|-------------|
| 2.4 | En tant que système, je peux valider l'identité d'un médecin via une API externe KYC (ex. Sumsub ou Onfido) pour activer son compte sans validation manuelle par l'admin. | T9.1 | En tant que concepteur, je dois préparer le diagramme de séquence "Validation Médecin par API KYC" (inscription médecin → création applicant → envoi document / lien → appel API → mise à jour statut compte). | 1h | |
| 2.4 | | T9.2 | En tant que développeur, je dois créer un service MedecinKycService (ou équivalent) qui appelle l'API externe (HttpClient) avec les credentials (sandbox). | 1h 30 | |
| 2.4 | | T9.3 | En tant que développeur, je dois ajouter un statut de vérification au compte (ex. User.kycStatus ou isVerified) et une transition "validé" après réponse positive de l'API. | 1h | |
| 2.4 | | T9.4 | En tant que développeur, je dois intégrer l'appel KYC dans le parcours médecin (après inscription ou depuis "Mon compte") et afficher le statut (en attente / validé / refusé). | 1h 30 | |
| 2.4 | | T9.5 | En tant que testeur, je dois tester le scénario avec l'API en sandbox (succès / échec) et l’affichage du statut. | 1h | |

---

## 3. Récapitulatif des livrables Sprint 1 (aligné avec les instructions)

- **Sprint Backlog (complet)** : ce document.
- **Diagramme de séquence objets** : à produire pour au moins une fonctionnalité avancée (recommandé : US 2.1 Reset password ou US 2.4 Validation Médecin KYC).
- **Sprint Burndown chart** : à mettre à jour à partir des estimations de ce backlog.
- **Tableau blanc (ex. Trello)** : une semaine type avec les tâches réparties par jour/membre.

---

## 4. Légende des rôles (Scrum team)

- **Concepteur** : diagrammes (cas d’utilisation, séquence, classes), spécifications.
- **Admin BD** : schéma BDD, migrations Doctrine.
- **Développeur** : code (contrôleurs, formulaires, entités, services, vues).
- **Testeur** : cas de test, scénarios, exécution des tests.

*Renseigner la colonne "Responsable" avec Nom Prénom pour chaque tâche.*

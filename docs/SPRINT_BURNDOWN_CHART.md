# Sprint Burndown Chart – Comment le faire

Le **Sprint Burndown chart** montre la **charge de travail restante** jour après jour pendant le sprint. Plus la courbe descend, plus l’équipe “brûle” le travail. L’objectif est d’arriver à **0** en fin de sprint.

---

## 1. Ce qu’il te faut

- **Axe vertical (Y)** : travail restant (en **heures** ou en **points**).
- **Axe horizontal (X)** : **jours du sprint** (ex. Jour 1, Jour 2, … Jour 5 ou J1–J5).
- **Deux lignes** :
  - **Idéale** : droite qui part du total en J1 et descend linéairement jusqu’à 0 au dernier jour.
  - **Réelle** : points “travail restant” à la fin de chaque jour, reliés entre eux.

---

## 2. Calculer le total de travail (ton backlog)

À partir de ton **Sprint Backlog** (estimations en minutes), on obtient :

| User Story | Tâches | Somme (min) | Somme (h) |
|------------|--------|-------------|-----------|
| US 1.1 Inscription | T1.1 → T1.6 | 45+30+90+60+30+60 | 5,25 h |
| US 1.2 Login/Logout | T2.1 → T2.5 | 30+45+45+30+60 | 3,5 h |
| US 1.3 Mon compte | T3.1 → T3.4 | 30+60+60+45 | 3,25 h |
| US 1.4 Liste users (backoffice) | T4.1 → T4.4 | 30+60+60+45 | 3,25 h |
| US 1.5 Edit/Delete user | T5.1 → T5.3 | 30+60+45 | 2,25 h |
| US 2.1 Reset password (mail) | T6.1 → T6.6 | 45+45+60+90+15+60 | 5,25 h |
| US 2.2 Upload avatar | T7.1 → T7.5 | 30+30+60+60+45 | 3,75 h |
| US 2.3 Contrôle saisie | T8.1 → T8.4 | 30+45+30+45 | 2,5 h |
| US 2.4 Validation Médecin KYC | T9.1 → T9.5 | 60+90+60+90+60 | 6 h |
| **TOTAL** | | **2100 min** | **35 h** |

**Valeur à utiliser pour le graphique : travail total = 35 h** (en début de sprint, “reste à faire” = 35 h).

---

## 3. Choisir la durée du sprint

Exemple : **5 jours ouvrés** (J1 à J5).

- **Ligne idéale** :  
  - J1 : 35 h  
  - J2 : 28 h  
  - J3 : 21 h  
  - J4 : 14 h  
  - J5 : 7 h  
  - Fin J5 : 0 h  

(Chaque jour on “brûle” 35 ÷ 5 = 7 h en théorie.)

---

## 4. Calculer la courbe réelle (travail restant)

À la **fin de chaque jour**, tu calcules :

**Travail restant = Total du sprint − (somme des estimations des tâches déjà terminées)**

Exemple fictif (à adapter selon tes vrais “done”) :

| Jour | Tâches terminées ce jour | Heures “brûlées” ce jour | Travail restant (fin de jour) |
|------|-------------------------|---------------------------|-------------------------------|
| J1 | T1.1, T1.2, T2.1 | 45+30+30 = 105 min = 1,75 h | 35 − 1,75 = **33,25 h** |
| J2 | T1.3, T1.4, T2.2, T2.3 | 90+60+45+45 = 240 min = 4 h | 33,25 − 4 = **29,25 h** |
| J3 | T1.5, T1.6, T2.4, T2.5, T3.1, T3.2 | 30+60+30+60+30+60 = 270 min = 4,5 h | 29,25 − 4,5 = **24,75 h** |
| J4 | T3.3, T3.4, T4.1–T4.4, T5.1 | 60+45+30+60+60+45+30 = 330 min = 5,5 h | 24,75 − 5,5 = **19,25 h** |
| J5 | T5.2, T5.3, T6.1–T6.4, … | (reste des tâches) | fin vers **0 h** |

En pratique : chaque soir, tu regardes quelles tâches sont **Done** dans Trello (ou ton tableau), tu additionnes leurs estimations, et tu en déduis le “reste à faire” pour le point du burndown.

---

## 5. Faire le graphique (Excel ou Google Sheets)

### Étape 1 – Tableau de données

Crée un tableau comme ci-dessous (les valeurs “Réel” sont un exemple ; tu les remplaces par tes vrais restants).

| Jour | Idéal (h) | Réel (h) |
|------|-----------|----------|
| 0 (début) | 35 | 35 |
| 1 | 28 | 33,25 |
| 2 | 21 | 29,25 |
| 3 | 14 | 24,75 |
| 4 | 7 | 19,25 |
| 5 | 0 | 0 (ou la valeur réelle) |

- **Jour 0** = début du sprint (reste = 35 h).  
- **Jour 1 à 5** = fin de chaque jour.

### Étape 2 – Créer le graphique

1. Sélectionne les colonnes **Jour**, **Idéal**, **Réel**.
2. **Insertion** → **Graphique** → **Ligne** (ou “Line chart”).
3. **Axe X** : Jour.  
   **Axe Y** : Heures (Idéal et Réel).
4. Optionnel : renommer les séries en “Idéal” et “Réel (Burndown)”.

Résultat type :
- Une **droite** qui descend régulièrement (Idéal).
- Une **courbe** qui suit (ou s’écarte de) l’idéal selon le rythme réel (Réel).

---

## 6. Règles à retenir

- **Un point par jour** : à la fin de chaque jour, un seul “travail restant” pour la courbe réelle.
- **Même unité partout** : soit tout en **heures** (recommandé avec ton backlog), soit tout en **points** si tu convertis tes tâches en story points.
- **Tâche “terminée”** = Done (testée, livrée), pas “en cours”.
- Si tu **ajoutes** des tâches en cours de sprint, tu augmentes le “total” et tu peux l’indiquer (ex. petit marqueur “changement de scope”).

---

## 7. Exemple visuel (résumé)

```
Heures
  35 | *
     |   \
  28 |     *  (idéal)
     |       \
  21 |         *
     |           \
  14 |             *
     |               \
   7 |                 *
     |                   *
   0 +-----+-----+-----+-----+-----+---- Jours
      0     1     2     3     4     5

-- Idéal (droite)
-- Réel (points reliés selon ce que tu as vraiment terminé)
```

---

## 8. Fichier CSV pour Excel/Sheets (template)

Tu peux créer un fichier `burndown_sprint1.csv` avec le contenu suivant, l’ouvrir dans Excel ou Google Sheets, puis insérer un graphique en lignes comme en section 5.

```csv
Jour,Idéal (h),Réel (h)
0,35,35
1,28,33.25
2,21,29.25
3,14,24.75
4,7,19.25
5,0,0
```

Tu remplaces la colonne **Réel (h)** par tes vrais “travail restant” en fin de chaque jour une fois le sprint avancé.

---

En résumé : **total = 35 h**, **un point “reste à faire” par jour**, **ligne idéale** de 35 à 0 sur 5 jours, **ligne réelle** = tes vrais restants. Dès que tu as ces données, le Sprint Burndown chart se fait en un graphique en lignes dans Excel ou Google Sheets.

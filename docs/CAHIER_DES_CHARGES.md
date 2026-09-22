# Cahier des charges — Plateforme de gestion des bénévoles

**Projet :** Salon de la Danse (Angers) — association JayDance Fam
**Contexte :** Starter-pack Master 1 FullStack / Cybersécurité & Architecture Réseau

**Voir aussi :** [dictionnaire de données](DICTIONNAIRE_DONNEES.md) · [suivi des tâches](TACHES.md)

## Contexte événement

- Salon de la Danse : 2,5 jours au Centre de Congrès d'Angers.
- Dates de l'édition : vendredi 14 mai 2027, samedi 15 mai 2027, dimanche 16 mai 2027.
- Envergure : 120 exposants, 7 000+ visiteurs attendus.
- 130 bénévoles à gérer pour cette édition.
- Objectif : remplacer les tableurs par une plateforme web sur-mesure, moderne, sécurisée, responsive et pérenne (pensée multi-éditions).

## 1. Processus global

- **Étape 1 (hors plateforme)** — Sélection : candidatures via Google Forms externe. Les profils retenus reçoivent un e-mail avec un **code d'invitation unique**.
- **Étape 2 (sur la plateforme)** — Inscription & Planning : le bénévole utilise son code d'invitation pour créer son compte, renseigner ses infos, et composer son planning selon ses disponibilités.

## 2. Sécurité, authentification & compte bénévole

### Création de compte
- Accès à l'inscription **strictement conditionné** par un code d'invitation valide.
- Champs requis : Nom, Prénom, E-mail, Téléphone, Mot de passe sécurisé, Photo récente (obligatoire pour le badge).
- **Unicité** : une seule personne = un seul compte.
- **Verrouillage** : une fois validées, les infos personnelles (nom, e-mail, photo...) ne sont modifiables que par un **Administrateur**.
- Réinitialisation / modification du mot de passe en cas d'oubli.

### Accueil & période d'inscription
- Onboarding / Dashboard bénévole : règles d'inscription, dates du Salon, quota de créneaux (min/max), contacts équipe, règles d'engagement.
- **Contrôle temporel** : l'admin définit la fenêtre d'inscription. Hors de cette fenêtre, planning en **lecture seule**. Verrouillage manuel possible à tout moment.

## 3. Module Planning & règles métier

Interface **mobile-first**, visuelle et interactive.

### Structure temporelle
- 3 jours : vendredi 14/05/2027, samedi 15/05/2027, dimanche 16/05/2027.
- 5 créneaux de 2h/jour : 8h30-10h00, 10h00-12h00, 12h00-14h00, 14h00-16h00, 16h00-18h00.

### Missions ouvertes à la réservation
Accueil exposants, Vestiaires, Point Info, Masterclass/Conférences, Loges danseurs, Logistique (Niveau 0 + -2), Scène principale, Stand JayDance, Village Danses du Monde.

### Postes sous restriction
Billetterie, Caisse : hors-planning public, attribution manuelle par l'admin uniquement.

### Règles métier & validation
- Volume horaire : **min 1 créneau (2h)**, **max 3 créneaux (6h total sur le week-end)**.
- Interdiction d'avoir 2 missions sur le même créneau.
- Interdiction d'enchaîner **3 créneaux consécutifs** (pause obligatoire).
- Jauge de capacité paramétrable par mission/créneau ; réservation bloquée à capacité atteinte.
- Code couleur dynamique : Vert (disponible) / Orange (presque complet) / Rouge-Gris (complet/indisponible).
- **Confidentialité stricte** : un bénévole ne voit que le nombre de places restantes, jamais l'identité des autres inscrits sur un créneau.

### Validation & espace personnel
- Mode brouillon modifiable jusqu'au clic "Valider définitivement" (avec pop-up de confirmation). Planning verrouillé après validation.
- Profil bénévole : récapitulatif missions/horaires, consignes, export/impression du planning individuel en **PDF**.

## 4. Back-office Administrateur

### Dashboard & supervision
- Compteurs temps réel : total bénévoles, comptes créés, plannings validés vs en attente, taux de remplissage par jour/mission.
- Recherche multi-critères : nom, prénom, mission, statut de validation, jour.
- Gestion admin : modifier un planning verrouillé, forcer l'attribution de postes sensibles, réinitialiser des identifiants, valider des profils mineurs.

### Badging, exports & traçabilité
- Génération automatique de badges imprimables : Photo, Nom, Prénom, Rôle "BÉNÉVOLE", ID unique, QR Code de vérification.
- Exports Excel / CSV / PDF (plannings généraux, listes par mission, fiches contact).
- Log d'audit horodaté de toutes les actions admin.
- Gestion multi-éditions : création de nouvelles éditions, archivage, consultation des éditions passées.

## 5. Roadmap de développement

### MVP prioritaire (semaine)
1. Authentification & inscription par code d'invitation + upload photo.
2. Moteur de planning interactif (mobile-first) avec jauges et code couleur.
3. Contrôle des règles métier (1 à 3 créneaux, non-chevauchement, jauges max).
4. Verrouillage du planning + fiche récapitulative bénévole.
5. Back-office admin : vue d'ensemble, modifications manuelles, export Excel/CSV.

### Fonctionnalités avancées (post-MVP)
1. Générateur de badges PDF avec QR Code dynamique.
2. Notifications e-mail automatiques (confirmation, rappels).
3. Historique détaillé des modifications admin.
4. Architecture multi-éditions (archivage).

### Livrables attendus en fin de semaine
- Démo prototype web fonctionnel (bénévole & admin).
- Base de données structurée.
- Présentation des choix d'architecture technique.

## Décisions techniques (à compléter au fil du projet)

- Stack : Laravel (PHP 8.5), base de données **MySQL 8.0**.
- **Base de données locale** : MySQL tourne dans un conteneur Docker dédié au projet (`docker-compose.yml`, service `mysql`), pour ne pas dépendre du MySQL système de la machine.
  - Port hôte : `3307` (le 3306 est déjà pris par un MySQL système local).
  - Base : `danse_benevol`, user `danse_benevol` / password `danse_benevol` (dev uniquement).
  - Démarrage : `docker compose up -d mysql`.
  - En production, un environnement MySQL différent sera utilisé (ex. Laravel Cloud) — ces identifiants ne concernent que le développement local.
- **phpMyAdmin (local uniquement)** : service `phpmyadmin` dans `docker-compose.yml`, accessible sur http://localhost:8081 (identifiants : `danse_benevol` / `danse_benevol`). N'est jamais déployé en production.
- *(Cette section sera enrichie au fur et à mesure des décisions prises pendant le projet.)*

## Points ouverts / à trancher

- **Stockage des photos bénévoles** (`users.photo_path`) : disque à choisir (`local` privé + route authentifiée vs `public`) et implémentation de l'upload — à faire avec le reste de l'authentification/inscription. Recommandation : disque privé (`storage/app/private`) + route authentifiée, car ce sont des photos de personnes potentiellement mineures, pas d'URL publique devinable.

# Suivi des tâches — Plateforme de gestion des bénévoles

Liste de tâches dérivée du [cahier des charges](CAHIER_DES_CHARGES.md), organisée dans le même ordre. À mettre à jour au fil de l'avancement (cocher/décocher, ajouter des sous-tâches si besoin).

Légende : ✅ Fait — 🚧 Partiellement fait (le détail précise ce qui manque) — ⬜ À faire

## Infrastructure & outillage (hors périmètre du cahier des charges, mais nécessaire au dev)

- [x] ✅ Environnement MariaDB local via Docker (`docker-compose.yml`)
- [x] ✅ phpMyAdmin en local (Docker, dev uniquement)
- [x] ✅ Bandeau d'URLs locales au lancement de `php artisan serve` / `composer run dev`

## Base de données

- [x] ✅ Dictionnaire de données (`docs/DICTIONNAIRE_DONNEES.md`)
- [x] ✅ Migrations du schéma complet (11 tables)
- [x] ✅ Modèles Eloquent + relations + casts
- [x] ✅ Factories pour toutes les tables
- [x] ✅ Seeder de données de démo réalistes (Faker) — `DemoDataSeeder`
- [x] ✅ Décision + implémentation du stockage des photos (disque privé `local`, upload à l'inscription — route de service authentifiée à faire plus tard, liée à la génération de badge)

## §2 — Sécurité, authentification & compte bénévole

- [x] ✅ Génération/gestion des codes d'invitation côté admin (création unitaire par e-mail + liste + révocation ; génération en masse non couverte)
- [x] ✅ Inscription strictement conditionnée par un code d'invitation valide
- [x] ✅ Formulaire d'inscription (nom, prénom, e-mail, téléphone, mot de passe, photo)
- [x] ✅ Upload de la photo (dépend de la décision de stockage ci-dessus)
- [x] ✅ Contrôle applicatif d'unicité du compte (l'e-mail est déjà `UNIQUE` en base)
- [ ] ⬜ Verrouillage des infos perso après validation, modifiable par un admin seulement (colonne `profile_locked_at` déjà en base, logique applicative à écrire)
- [ ] ⬜ Réinitialisation / modification du mot de passe
- [ ] ⬜ Onboarding / dashboard bénévole (règles, dates, quotas, contacts, engagement)
- [ ] ⬜ Fenêtre d'inscription : planning en lecture seule hors période + verrouillage manuel admin (colonnes `registration_opens_at`/`registration_closes_at`/`is_registration_locked` déjà en base)

## §3 — Module Planning & règles métier

- [x] ✅ Interface de sélection du planning, mobile-first, visuelle et interactive (formulaires serveur, sans framework JS)
- [x] ✅ Affichage des jauges avec code couleur dynamique (vert/orange/rouge-gris) — `MissionSlot::gaugeStatus()`
- [x] ✅ Contrôle des règles métier à la réservation :
  - [x] ✅ Non-chevauchement (2 missions sur le même créneau) — contrainte `UNIQUE(user_id, time_slot_id)` en base
  - [x] ✅ Min 1 / max 3 créneaux par bénévole
  - [x] ✅ Pas plus de `max_consecutive_slots` créneaux consécutifs
  - [x] ✅ Jauge de capacité non dépassée (`MissionSlot::remainingCapacity()` branché à `PlanningController::reserve()`)
- [x] ✅ Confidentialité : n'afficher que le nombre de places restantes, jamais l'identité des autres inscrits
- [x] ✅ Postes sous restriction (Billetterie, Caisse) hors planning public, attribution manuelle admin uniquement — `Admin\RestrictedMissionController`
- [x] ✅ Mode brouillon modifiable + validation définitive avec pop-up de confirmation + verrouillage
- [x] ✅ Profil bénévole : récapitulatif missions/horaires + consignes (règles affichées sur la page planning)
- [ ] ⬜ Export/impression du planning individuel en PDF (nécessite une dépendance PDF, PR dédié)

## §4 — Back-office Administrateur

- [x] ✅ Dashboard admin : compteurs temps réel (bénévoles, comptes créés, plannings validés/en attente, taux de remplissage) — `Admin\DashboardController`, nouvelle page d'accueil admin
- [x] ✅ Recherche multi-critères (nom, prénom, mission, statut, jour) — `Admin\VolunteerController`
- [ ] ⬜ Modification d'un planning verrouillé par un admin
- [x] ✅ Forcer l'attribution de postes sensibles
- [ ] ⬜ Réinitialisation des identifiants par un admin
- [x] ✅ Validation des profils mineurs — filtre + action sur la page `Admin\VolunteerController`
- [ ] ⬜ Génération automatique des badges (Photo, Nom, Prénom, "BÉNÉVOLE", ID unique, QR code) — `badge_uid` déjà en base
- [x] ✅ Exports Excel / CSV (planning général, listes par mission, fiches contact) — `Admin\ExportController`, OpenSpout, téléchargements tracés dans `audit_logs`
- [ ] ⬜ Exports PDF (plannings, listes par mission, fiches contact)
- [x] ✅ Import Excel / CSV de candidats : création et envoi d'un code d'invitation par e-mail valide, rapport des lignes ignorées — `Admin\InvitationCodeController`
- [ ] 🚧 Log d'audit horodaté des actions admin — branché sur la création/révocation de codes d'invitation et l'attribution/retrait de postes restreints ; pas encore d'UI de consultation (cf. fonctionnalités avancées)
- [ ] ⬜ Gestion multi-éditions : création, archivage, consultation des éditions passées (le modèle `Edition` est déjà pensé multi-éditions, UI à faire)

## Fonctionnalités avancées (post-MVP)

- [ ] ⬜ QR code dynamique sur le badge
- [ ] ⬜ Notifications e-mail automatiques (confirmation, rappels)
- [ ] ⬜ Historique détaillé des modifications admin (UI de consultation des `audit_logs`)
- [ ] ⬜ Archivage multi-éditions (UI)

## Livrables attendus en fin de semaine

- [ ] ⬜ Démo prototype fonctionnel (parcours bénévole & admin)
- [x] ✅ Base de données structurée
- [ ] ⬜ Présentation des choix d'architecture technique

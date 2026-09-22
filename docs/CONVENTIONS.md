# Conventions Git — Branches, Commits, Pull Requests

Convention à appliquer à partir de maintenant sur ce dépôt.

## Nommage des branches

```
<type>/<description-courte-en-kebab-case>
```

- `<type>` : voir la [liste des types](#types) ci-dessous (ex. `feat`, `fix`, `docs`).
- `<description>` : en kebab-case, courte et explicite.

Exemples :
- `feat/inscription-code-invitation`
- `feat/planning-reservation`
- `fix/jauge-mission-slot`
- `docs/update-taches`
- `chore/upgrade-laravel`

## Messages de commit

```
type(scope): description
```

- `type` : voir la [liste des types](#types) ci-dessous.
- `(scope)` : optionnel, la zone concernée (ex. `auth`, `planning`, `admin`, `bdd`, `docker`, `docs`). À omettre si le changement est transverse.
- `description` : à l'impératif, sans majuscule ni point final. Le sujet peut être en français, comme le reste des messages du dépôt.

Un corps de message (ligne vide puis paragraphe) peut être ajouté pour expliquer le **pourquoi** d'un changement non évident — pas la simple description de ce qui a été fait.

Exemples :
- `feat(planning): ajouter la réservation de créneau avec contrôle de jauge`
- `fix(auth): corriger la validation du code d'invitation expiré`
- `docs(taches): mettre à jour le suivi après le module planning`
- `chore(docker): monter phpMyAdmin sur le port 8081`
- `test(planning): couvrir la règle des 3 créneaux consécutifs`

## Types

| Type | Usage |
|---|---|
| `feat` | Nouvelle fonctionnalité |
| `fix` | Correction de bug |
| `docs` | Documentation uniquement (rien dans `app/`, `database/`, etc.) |
| `style` | Formatage (Pint, indentation...), aucun changement de logique |
| `refactor` | Changement de code qui n'est ni un fix ni une feature |
| `perf` | Amélioration de performance |
| `test` | Ajout ou correction de tests |
| `build` | Dépendances, configuration de build (composer.json, package.json...) |
| `ci` | Configuration d'intégration continue |
| `chore` | Maintenance, outillage (Docker, scripts...), sans impact fonctionnel |

## Pull Requests

- Titre au même format que les commits : `type(scope): titre`.
- Une PR = un sujet cohérent (éviter de mélanger plusieurs types de changements sans lien).
- Description avec :
  - **Summary** : liste à puces de ce qui change et pourquoi.
  - **Test plan** : checklist de ce qui a été vérifié (tests, Pint, vérifications manuelles).
- Mettre à jour `docs/TACHES.md` dans la même PR si elle fait avancer une tâche du suivi.

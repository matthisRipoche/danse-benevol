#!/usr/bin/env bash
# Déploie une version sur le VPS. Appelé par la CI, ou à la main :
#   ./deploy.sh <tag-de-l-image>     (tag = SHA du commit, ou « latest »)
# À lancer depuis le dossier qui contient compose.prod.yml et le .env de production.
set -euo pipefail

cd "$(dirname "$0")"

export IMAGE_TAG="${1:?Usage : ./deploy.sh <tag>}"
compose() { docker compose -f compose.prod.yml "$@"; }

echo "→ Récupération des images ${IMAGE_TAG}"
compose pull app web

echo "→ Base de données"
compose up -d --wait mariadb

if compose ps --status running --services | grep -qx app; then
    echo "→ Sauvegarde avant migration"
    ./backup.sh
fi

echo "→ Migrations"
compose run --rm app php artisan migrate --force

echo "→ Démarrage de la nouvelle version"
compose up -d --remove-orphans

docker image prune -f > /dev/null
echo "✓ Version ${IMAGE_TAG} déployée"

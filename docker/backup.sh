#!/usr/bin/env bash
# Sauvegarde locale de la base et des photos, à planifier chaque nuit (cron) :
#   15 3 * * * /opt/danse-benevol/backup.sh >> /var/log/danse-benevol-backup.log 2>&1
# Les archives restent sur le VPS : leur chiffrement et leur copie hors du VPS
# (stockage externe) sont à ajouter selon la destination retenue.
set -euo pipefail

cd "$(dirname "$0")"

destination="${BACKUP_DIR:-/var/backups/danse-benevol}"
stamp="$(date +%F-%H%M)"
compose() { docker compose -f compose.prod.yml "$@"; }

umask 077
mkdir -p "$destination"

# Les variables sont développées dans le conteneur MariaDB, pas sur l'hôte.
# shellcheck disable=SC2016
compose exec -T mariadb sh -c 'exec mariadb-dump --single-transaction -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' \
    | gzip > "$destination/base-$stamp.sql.gz"

compose exec -T app tar -C /var/www/html/storage/app -czf - private \
    > "$destination/photos-$stamp.tar.gz"

find "$destination" -type f -mtime +7 -delete

echo "Sauvegarde $stamp terminée dans $destination"

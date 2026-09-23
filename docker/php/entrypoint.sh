#!/bin/sh
set -e

# Met en cache la configuration, les routes et les vues à partir des variables
# d'environnement du conteneur (le .env du serveur, transmis par compose).
php artisan optimize --quiet

exec "$@"

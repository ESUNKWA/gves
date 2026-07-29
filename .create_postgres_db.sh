#!/bin/bash

#==========================================
# Configuration
#==========================================
DB_NAME="gves_db"
DB_USER="mdeki"
DB_PASSWORD="Dyaj2021@"

#==========================================
# Vérification de PostgreSQL
#==========================================
if ! command -v psql >/dev/null 2>&1; then
    echo "Erreur : PostgreSQL n'est pas installé."
    exit 1
fi

echo "Démarrage du service PostgreSQL..."
systemctl start postgresql

echo "Création de l'utilisateur si nécessaire..."
su - postgres -c "psql <<EOF
DO \$\$
BEGIN
    IF NOT EXISTS (
        SELECT FROM pg_catalog.pg_roles
        WHERE rolname = '${DB_USER}'
    ) THEN
        CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASSWORD}';
    END IF;
END
\$\$;
EOF"

echo "Création de la base si nécessaire..."
su - postgres -c "psql -tAc \"SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'\"" | grep -q 1

if [ $? -ne 0 ]; then
    su - postgres -c "createdb -O ${DB_USER} ${DB_NAME}"
    echo "Base créée."
else
    echo "La base existe déjà."
fi

echo "Attribution des privilèges..."
su - postgres -c "psql -c \"GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};\""

echo ""
echo "=================================="
echo "Configuration terminée"
echo "=================================="
echo "Base        : ${DB_NAME}"
echo "Utilisateur : ${DB_USER}"
echo "Mot de passe: ${DB_PASSWORD}"
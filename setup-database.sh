#!/bin/bash

# Bâti Yakaar - Script d'installation de la base de données
# Ce script crée la base de données MySQL et exécute les migrations

echo "🚀 Installation de la base de données Bâti Yakaar"
echo "=================================================="
echo ""

# Couleurs pour les messages
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Vérifier si MySQL est installé
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}❌ MySQL n'est pas installé. Veuillez installer MySQL d'abord.${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} MySQL détecté"

# Demander les credentials MySQL
echo ""
echo "Entrez vos credentials MySQL:"
read -p "Utilisateur MySQL (défaut: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Mot de passe MySQL: " DB_PASSWORD
echo ""

# Nom de la base de données
DB_NAME="noor_immo"

# Créer la base de données
echo ""
echo -e "${YELLOW}📦 Création de la base de données '${DB_NAME}'...${NC}"

mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Base de données '${DB_NAME}' créée avec succès"
else
    echo -e "${RED}❌ Erreur lors de la création de la base de données${NC}"
    echo "Vérifiez vos credentials MySQL"
    exit 1
fi

# Mettre à jour le fichier .env
echo ""
echo -e "${YELLOW}⚙️  Configuration du fichier .env...${NC}"

if [ -f .env ]; then
    # Mettre à jour les variables de base de données
    sed -i.bak "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
    sed -i.bak "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
    sed -i.bak "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
    rm .env.bak
    echo -e "${GREEN}✓${NC} Fichier .env mis à jour"
else
    echo -e "${RED}❌ Fichier .env introuvable${NC}"
    exit 1
fi

# Exécuter les migrations
echo ""
echo -e "${YELLOW}🔄 Exécution des migrations (32 tables)...${NC}"
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓${NC} Migrations exécutées avec succès"
    echo ""
    echo "=================================================="
    echo -e "${GREEN}🎉 Installation terminée avec succès !${NC}"
    echo "=================================================="
    echo ""
    echo "Base de données: ${DB_NAME}"
    echo "Tables créées: 32"
    echo ""
    echo "Prochaines étapes:"
    echo "  1. Créer les seeders: php artisan make:seeder PlansSeeder"
    echo "  2. Créer les modèles: php artisan make:model NomDuModele"
    echo "  3. Lancer le serveur: php artisan serve"
    echo ""
else
    echo -e "${RED}❌ Erreur lors de l'exécution des migrations${NC}"
    exit 1
fi

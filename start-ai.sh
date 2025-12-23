#!/bin/bash

# Script de démarrage pour l'Assistant IA Noor Immo

echo "🤖 Démarrage de l'Assistant IA Noor Immo..."

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Vérifier si on est dans le bon répertoire
if [ ! -d "python-ai" ]; then
    echo -e "${RED}❌ Erreur: Exécutez ce script depuis batiyakaar-api/${NC}"
    exit 1
fi

cd python-ai

# Vérifier Python
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}❌ Python 3 n'est pas installé${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Python trouvé: $(python3 --version)"

# Vérifier Ollama
if ! command -v ollama &> /dev/null; then
    echo -e "${YELLOW}⚠️  Ollama n'est pas installé${NC}"
    echo "Installation: curl -fsSL https://ollama.com/install.sh | sh"
    exit 1
fi

echo -e "${GREEN}✓${NC} Ollama trouvé"

# Vérifier le modèle Phi-3
if ! ollama list | grep -q "phi3:mini"; then
    echo -e "${YELLOW}⚠️  Modèle Phi-3 Mini non trouvé${NC}"
    echo "Téléchargement du modèle (2.3 GB)..."
    ollama pull phi3:mini
fi

echo -e "${GREEN}✓${NC} Modèle Phi-3 Mini disponible"

# Vérifier Redis
if ! command -v redis-cli &> /dev/null; then
    echo -e "${YELLOW}⚠️  Redis n'est pas installé (optionnel)${NC}"
else
    if redis-cli ping &> /dev/null; then
        echo -e "${GREEN}✓${NC} Redis actif"
    else
        echo -e "${YELLOW}⚠️  Redis n'est pas démarré${NC}"
    fi
fi

# Créer venv si nécessaire
if [ ! -d "venv" ]; then
    echo "📦 Création de l'environnement virtuel..."
    python3 -m venv venv
fi

# Activer venv
source venv/bin/activate

# Installer/mettre à jour les dépendances
echo "📦 Installation des dépendances..."
pip install -q --upgrade pip
pip install -q -r requirements.txt

echo -e "${GREEN}✓${NC} Dépendances installées"

# Démarrer le service
echo ""
echo "🚀 Démarrage du service sur http://localhost:8001"
echo "   Appuyez sur Ctrl+C pour arrêter"
echo ""

python main.py

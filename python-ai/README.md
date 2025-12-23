# 🤖 Assistant IA Noor Immo

Assistant intelligent basé sur **OpenAI** et **LangChain** pour aider à la gestion immobilière.

## 🚀 Installation

### Prérequis

1. **Python 3.11+**
```bash
python3 --version
```

2. **Clé API OpenAI**
   - Créez un compte sur https://platform.openai.com
   - Générez une clé API
   - Ajoutez-la dans votre `.env`

3. **Redis** (optionnel mais recommandé pour le cache)
```bash
# macOS
brew install redis
brew services start redis

# Linux
sudo apt install redis-server
sudo systemctl start redis
```

### Installation des dépendances Python

```bash
cd batiyakaar-api/python-ai

# Créer environnement virtuel
python3 -m venv venv

# Activer
source venv/bin/activate  # macOS/Linux
# ou
venv\Scripts\activate  # Windows

# Installer dépendances
pip install -r requirements.txt
```

## ⚙️ Configuration

Ajoutez votre clé API OpenAI dans le fichier `.env` de Laravel :

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-your-api-key-here
```

**C'est tout !** Le service Python lit automatiquement ce fichier.

## 🎯 Démarrage

### Option 1 : Script automatique

```bash
cd batiyakaar-api
./start-ai.sh
```

### Option 2 : Manuel

```bash
cd batiyakaar-api/python-ai
source venv/bin/activate
python main.py
```

Le service démarre sur `http://localhost:8001`

## 📡 API Endpoints

### Health Check
```bash
GET http://localhost:8001/health
```

### Chat
```bash
POST http://localhost:8001/chat
Content-Type: application/json
Authorization: Bearer <token>

{
  "message": "Combien de biens ai-je ?",
  "user_id": 1,
  "agence_id": 1
}
```

### Suggestions
```bash
GET http://localhost:8001/suggestions?user_id=1&agence_id=1
Authorization: Bearer <token>
```

## 🧠 Fonctionnement

### Query Router

Le système route intelligemment les questions :

**SQL (Rapide - <500ms)** :
- "Combien de biens ai-je ?"
- "Liste des locataires en retard"
- "Revenus du mois"

**OpenAI (Complexe - 1-2s)** :
- "Résume la situation de mes biens"
- "Conseils pour réduire les impayés"
- "Analyse mes revenus"

### Cache

Les réponses sont mises en cache pendant 1 heure pour :
- Réduire les coûts API
- Améliorer les performances

### Modèles disponibles

Par défaut : **GPT-3.5-turbo** (rapide et économique)

Pour changer de modèle, modifiez dans `config/settings.py` :
```python
openai_model: str = "gpt-4"  # Plus intelligent mais plus cher
```

## 💰 Coûts

### GPT-3.5-turbo
- **Input** : $0.0005 / 1K tokens (~750 mots)
- **Output** : $0.0015 / 1K tokens
- **Coût moyen par question** : ~$0.002 (0.002 USD)

### GPT-4
- **Input** : $0.03 / 1K tokens
- **Output** : $0.06 / 1K tokens
- **Coût moyen par question** : ~$0.05 (0.05 USD)

**Estimation mensuelle** (GPT-3.5-turbo) :
- 1000 questions/mois : ~$2 USD
- 5000 questions/mois : ~$10 USD
- 10000 questions/mois : ~$20 USD

## 🔧 Développement

### Structure

```
python-ai/
├── main.py                  # Application FastAPI
├── config/
│   └── settings.py          # Configuration
├── services/
│   ├── openai_service.py    # Service OpenAI + LangChain
│   ├── query_router.py      # Routage intelligent
│   ├── database_service.py  # Requêtes SQL
│   └── cache_service.py     # Cache Redis
├── requirements.txt
└── README.md
```

### Tests

```bash
# Test health
curl http://localhost:8001/health

# Test chat (nécessite token)
curl -X POST http://localhost:8001/chat \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"message": "Combien de biens ?", "user_id": 1, "agence_id": 1}'
```

## 🐛 Dépannage

### Erreur "OpenAI API key not configured"

Vérifiez que `OPENAI_API_KEY` est bien dans le `.env` de Laravel :
```bash
cd batiyakaar-api
cat .env | grep OPENAI
```

### Redis non disponible

```bash
# Vérifier Redis
redis-cli ping
# Devrait répondre: PONG

# Redémarrer Redis
brew services restart redis  # macOS
sudo systemctl restart redis # Linux
```

### Erreur de connexion MySQL

Vérifier le fichier `.env` de Laravel :
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## 🚀 Production

### Avec Gunicorn

```bash
pip install gunicorn
gunicorn main:app -w 2 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8001
```

### Avec systemd

Créer `/etc/systemd/system/noor-ai.service` :

```ini
[Unit]
Description=Noor Immo AI Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/batiyakaar-api/python-ai
Environment="PATH=/path/to/venv/bin"
Environment="OPENAI_API_KEY=your-key-here"
ExecStart=/path/to/venv/bin/uvicorn main:app --host 0.0.0.0 --port 8001
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable noor-ai
sudo systemctl start noor-ai
```

## 📝 Notes

- **Coûts** : ~$0.002 par question avec GPT-3.5-turbo
- **Performance** : ~1-2 secondes par réponse IA
- **Cache** : Réduit les coûts de ~40%
- **Sécurité** : Clé API jamais exposée au frontend

## 🔗 Liens Utiles

- [OpenAI Platform](https://platform.openai.com)
- [LangChain Documentation](https://python.langchain.com)
- [FastAPI Documentation](https://fastapi.tiangolo.com/)

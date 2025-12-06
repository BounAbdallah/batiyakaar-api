# Bâti Yakaar API Documentation

## 📖 Table des Matières

- [Vue d'ensemble](#vue-densemble)
- [Installation](#installation)
- [Configuration](#configuration)
- [Authentication](#authentication)
- [Endpoints](#endpoints)
- [Exemples](#exemples)
- [Codes d'erreur](#codes-derreur)
- [Postman](#postman)

---

## 🎯 Vue d'ensemble

**Bâti Yakaar API** est une API RESTful complète pour la gestion de projets de construction et de location immobilière au Sénégal.

### Caractéristiques

- ✅ **58 endpoints** organisés en 13 modules
- ✅ **Authentication** avec Laravel Sanctum (token-based)
- ✅ **Validation** automatique des données
- ✅ **Pagination** sur toutes les listes
- ✅ **Filtres** avancés
- ✅ **Relations** Eloquent chargées automatiquement
- ✅ **Soft deletes** sur les ressources critiques
- ✅ **Business logic** (ventilation, commissions, etc.)

### Technologies

- **Framework**: Laravel 11.x
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum
- **PHP**: 8.3+

---

## 🚀 Installation

### Prérequis

```bash
- PHP 8.3+
- Composer
- MySQL 8.0+
- Node.js (optionnel)
```

### Étapes

```bash
# 1. Cloner le projet
git clone <repository-url>
cd batiyakaar-api

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 4. Configurer la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=batiyakaar
DB_USERNAME=root
DB_PASSWORD=

# 5. Exécuter les migrations
php artisan migrate

# 6. Exécuter les seeders
php artisan db:seed

# 7. Démarrer le serveur
php artisan serve
```

L'API sera disponible sur `http://localhost:8000/api/v1`

---

## ⚙️ Configuration

### Variables d'environnement

```env
APP_NAME="Bâti Yakaar API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=batiyakaar
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

---

## 🔐 Authentication

### Inscription

**Endpoint**: `POST /api/v1/auth/register`

**Body**:
```json
{
  "nom": "Diop",
  "prenom": "Amadou",
  "email": "amadou@example.com",
  "telephone": "+221771234567",
  "password": "password123",
  "password_confirmation": "password123",
  "user_type": "bailleur",
  "pays": "France",
  "adresse_diaspora": "Paris, France"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Utilisateur créé avec succès",
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

### Connexion

**Endpoint**: `POST /api/v1/auth/login`

**Body**:
```json
{
  "email": "amadou.diop@diaspora.sn",
  "password": "password123"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": { ... },
    "token": "2|xyz789..."
  }
}
```

### Utiliser le token

Toutes les requêtes authentifiées doivent inclure le header:

```
Authorization: Bearer {votre_token}
Accept: application/json
```

### Déconnexion

**Endpoint**: `POST /api/v1/auth/logout`

---

## 📋 Endpoints

### Authentication (4 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/auth/register` | Inscription |
| POST | `/auth/login` | Connexion |
| GET | `/auth/user` | Utilisateur connecté |
| POST | `/auth/logout` | Déconnexion |

### Projets Construction (5 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/projets` | Liste des projets |
| POST | `/projets` | Créer un projet |
| GET | `/projets/{id}` | Détails d'un projet |
| PUT | `/projets/{id}` | Modifier un projet |
| DELETE | `/projets/{id}` | Supprimer un projet |

**Filtres disponibles**: `statut`, `bailleur_id`, `search`

### Biens (5 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/biens` | Liste des biens |
| POST | `/biens` | Créer un bien |
| GET | `/biens/{id}` | Détails d'un bien |
| PUT | `/biens/{id}` | Modifier un bien |
| DELETE | `/biens/{id}` | Supprimer un bien |

**Filtres disponibles**: `statut`, `type`, `agence_id`, `bailleur_id`, `loyer_min`, `loyer_max`, `search`

### Baux (5 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/baux` | Liste des baux |
| POST | `/baux` | Créer un bail |
| GET | `/baux/{id}` | Détails d'un bail |
| PUT | `/baux/{id}` | Modifier un bail |
| DELETE | `/baux/{id}` | Supprimer un bail |

**Filtres disponibles**: `statut`, `bien_id`, `locataire_id`, `agence_id`

### Paiements Loyer (5 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/paiements-loyer` | Liste des paiements |
| POST | `/paiements-loyer` | Créer un paiement (auto-ventilation) |
| GET | `/paiements-loyer/{id}` | Détails d'un paiement |
| PUT | `/paiements-loyer/{id}` | Modifier un paiement |
| DELETE | `/paiements-loyer/{id}` | Supprimer un paiement |

**Features**: Ventilation automatique (5% plateforme, 10% agence, reste bailleur), génération quittance

### Incidents (7 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/incidents` | Liste des incidents |
| POST | `/incidents` | Créer un incident |
| GET | `/incidents/{id}` | Détails d'un incident |
| PUT | `/incidents/{id}` | Modifier un incident |
| DELETE | `/incidents/{id}` | Supprimer un incident |
| POST | `/incidents/{id}/assign` | Assigner à un technicien |
| POST | `/incidents/{id}/resolve` | Marquer comme résolu |

**Filtres disponibles**: `statut`, `priorite`, `bail_id`, `locataire_id`

### Produits (6 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/produits` | Liste des produits |
| POST | `/produits` | Créer un produit |
| GET | `/produits/{id}` | Détails d'un produit |
| PUT | `/produits/{id}` | Modifier un produit |
| DELETE | `/produits/{id}` | Supprimer un produit |
| PUT | `/produits/{id}/stock` | Mettre à jour le stock |

**Filtres disponibles**: `categorie`, `fournisseur_id`, `prix_min`, `prix_max`, `en_stock`, `search`

### Commandes (5 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/commandes` | Liste des commandes |
| POST | `/commandes` | Créer une commande |
| GET | `/commandes/{id}` | Détails d'une commande |
| PUT | `/commandes/{id}` | Modifier une commande |
| DELETE | `/commandes/{id}` | Supprimer une commande |

**Features**: Calcul automatique du montant total

### Livraisons (5 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/livraisons` | Liste des livraisons |
| POST | `/livraisons` | Créer une livraison |
| GET | `/livraisons/{id}` | Détails d'une livraison |
| PUT | `/livraisons/{id}` | Modifier une livraison |
| DELETE | `/livraisons/{id}` | Supprimer une livraison |

### Transactions (2 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/transactions` | Liste des transactions |
| GET | `/transactions/{id}` | Détails d'une transaction |

### User (5 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/user/profile` | Profil utilisateur |
| PUT | `/user/profile` | Modifier le profil |
| GET | `/user/portefeuille` | Solde du wallet |
| GET | `/user/notifications` | Liste des notifications |
| PUT | `/user/notifications/{id}/read` | Marquer notification lue |

### Portefeuille (2 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/portefeuille` | Balance du wallet |
| GET | `/portefeuille/history` | Historique des transactions |

### Agence (2 endpoints)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/agence/dashboard` | Statistiques agence |
| GET | `/agence` | Détails agence |

---

## 💡 Exemples

### Créer un projet

```bash
curl -X POST http://localhost:8000/api/v1/projets \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "bailleur_id": 1,
    "titre": "Construction Villa Almadies",
    "description": "Villa moderne 4 chambres",
    "adresse": "Almadies, Dakar",
    "budget_total": 50000000,
    "date_debut": "2024-01-15",
    "date_fin_prevue": "2024-12-31"
  }'
```

### Créer un paiement avec ventilation automatique

```bash
curl -X POST http://localhost:8000/api/v1/paiements-loyer \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "bail_id": 1,
    "montant": 350000,
    "date_paiement": "2024-01-05",
    "date_prevue": "2024-01-01",
    "mode_paiement": "mobile_money",
    "reference_transaction": "OM123456789"
  }'
```

### Filtrer les biens disponibles

```bash
curl -X GET "http://localhost:8000/api/v1/biens?statut=disponible&type=appartement&loyer_max=500000" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## ❌ Codes d'erreur

| Code | Message | Description |
|------|---------|-------------|
| 200 | OK | Succès |
| 201 | Created | Ressource créée |
| 400 | Bad Request | Données invalides |
| 401 | Unauthorized | Non authentifié |
| 403 | Forbidden | Non autorisé |
| 404 | Not Found | Ressource introuvable |
| 422 | Unprocessable Entity | Validation échouée |
| 500 | Internal Server Error | Erreur serveur |

### Format des erreurs

```json
{
  "success": false,
  "message": "Les identifiants fournis sont incorrects.",
  "errors": {
    "email": ["Les identifiants fournis sont incorrects."]
  }
}
```

---

## 📦 Postman

### Importer la collection

1. Ouvrir Postman
2. Cliquer sur **Import**
3. Sélectionner `Batiyakaar-API.postman_collection.json`
4. La collection sera importée avec tous les endpoints

### Variables

- `base_url`: `http://localhost:8000/api/v1`
- `access_token`: Rempli automatiquement après login

### Credentials de test

| Email | Type | Password |
|-------|------|----------|
| `amadou.diop@diaspora.sn` | Bailleur | `password123` |
| `contact@immoplus.sn` | Agence | `password123` |
| `mamadou.gueye@construction.sn` | Entrepreneur | `password123` |
| `contact@ciment-plus.sn` | Fournisseur | `password123` |
| `awa.diouf@email.sn` | Locataire | `password123` |

---

## 🔧 Développement

### Générer la documentation Swagger

```bash
php artisan l5-swagger:generate
```

Accéder à: `http://localhost:8000/api/documentation`

### Exécuter les tests

```bash
php artisan test
```

### Vider le cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 📊 Statistiques

- **Controllers**: 14
- **Endpoints**: 58
- **Modèles**: 32
- **Tables**: 32
- **Seeders**: 3
- **Users test**: 31

---

## 🤝 Support

Pour toute question ou problème:
- Email: support@batiyakaar.com
- Documentation: http://localhost:8000/api/documentation

---

**Bâti Yakaar API** - Documentation complète v1.0

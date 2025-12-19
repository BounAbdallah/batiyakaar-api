# Noor-Immo API

API Backend Laravel pour la plateforme PropTech Noor-Immo - Gestion des investissements immobiliers de la diaspora sénégalaise.

## 🚀 Technologies

- **Framework**: Laravel 11.x
- **Base de données**: MySQL 8.0+
- **PHP**: 8.3+
- **Authentification**: Laravel Sanctum (à venir)

## 📦 Installation

### Prérequis

- PHP 8.3 ou supérieur
- Composer
- MySQL 8.0 ou supérieur
- Node.js & NPM (optionnel, pour assets)

### Étapes d'installation

1. **Cloner le repository**
   ```bash
   cd batiyakaar-api
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurer la base de données**
   
   Éditez le fichier `.env` :
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=noor_immo
   DB_USERNAME=root
   DB_PASSWORD=votre_mot_de_passe
   ```

5. **Créer la base de données**
   ```bash
   mysql -u root -p -e "CREATE DATABASE noor_immo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

6. **Exécuter les migrations**
   ```bash
   php artisan migrate
   ```

7. **Lancer le serveur de développement**
   ```bash
   php artisan serve
   ```

   L'API sera accessible sur `http://localhost:8000`

## 📊 Structure de la Base de Données

Le schéma de base de données comprend **32 tables** organisées en **7 modules** :

### Module 1: Gestion des Utilisateurs (7 tables)
- `users` - Table polymorphique de base
- `bailleurs` - Propriétaires/Diaspora
- `agences` - Agences immobilières
- `entrepreneurs` - Entrepreneurs/Maçons
- `fournisseurs` - Fournisseurs de matériaux
- `locataires` - Locataires
- `techniciens` - Techniciens de maintenance

### Module 2: Construction (7 tables)
- `projets_construction` - Projets de construction
- `chantiers` - Sites avec géolocalisation
- `etapes` - Étapes des projets
- `preuves_visuelles` - Photos/vidéos certifiées (hash SHA256)
- `paiements_escrow` - Paiements en séquestre
- `parties_prenantes` - Parties prenantes
- `rapports_chantier` - Rapports PDF

### Module 3: Gestion Locative (6 tables)
- `biens` - Biens immobiliers
- `baux` - Contrats de location
- `paiements_loyer` - Paiements de loyers
- `quittances` - Quittances de loyer
- `incidents` - Tickets de maintenance
- `etats_des_lieux` - États des lieux

### Module 4: Marketplace (5 tables)
- `catalogues` - Catalogues fournisseurs
- `produits` - Produits/Matériaux
- `commandes` - Commandes
- `lignes_commande` - Lignes de commande
- `livraisons` - Livraisons

### Module 5: Financier (4 tables)
- `portefeuilles_virtuels` - Portefeuilles virtuels
- `transactions` - Toutes les transactions
- `commissions` - Commissions plateforme/agence
- `ventilations` - Distribution des paiements

### Module 6: Notifications & Abonnements (3 tables)
- `notifications` - Notifications multi-canal
- `plans` - Plans d'abonnement (Starter, Pro, Enterprise)
- `abonnements` - Abonnements des agences

📖 Voir [DATABASE_GUIDE.md](../DATABASE_GUIDE.md) pour la documentation complète.

## 🔧 Commandes Utiles

### Migrations
```bash
# Exécuter les migrations
php artisan migrate

# Rollback de la dernière migration
php artisan migrate:rollback

# Reset toutes les migrations
php artisan migrate:reset

# Refresh (reset + migrate)
php artisan migrate:refresh

# Vérifier le statut
php artisan migrate:status
```

### Seeders (à venir)
```bash
# Exécuter les seeders
php artisan db:seed

# Refresh avec seeders
php artisan migrate:refresh --seed
```

### Cache
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize
php artisan optimize
```

## 📁 Structure du Projet

```
batiyakaar-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # Contrôleurs API
│   │   └── Middleware/     # Middlewares
│   ├── Models/             # Modèles Eloquent
│   └── Services/           # Logique métier
├── database/
│   ├── migrations/         # 32 migrations
│   ├── seeders/            # Seeders (à créer)
│   └── factories/          # Factories (à créer)
├── routes/
│   ├── api.php             # Routes API
│   └── web.php             # Routes web
├── config/                 # Configuration
└── tests/                  # Tests
```

## 🎯 Prochaines Étapes

### Phase 1: Modèles & Relations
- [ ] Créer les modèles Eloquent pour toutes les tables
- [ ] Définir les relations (hasMany, belongsTo, etc.)
- [ ] Ajouter les accessors et mutators

### Phase 2: Seeders & Factories
- [ ] Créer les seeders pour les données de test
- [ ] Créer les factories pour générer des données
- [ ] Seeder pour les plans d'abonnement

### Phase 3: API Endpoints
- [ ] Authentification (Laravel Sanctum)
- [ ] CRUD pour chaque module
- [ ] Validation des requêtes
- [ ] Policies & Gates pour l'autorisation

### Phase 4: Intégrations
- [ ] Wave API
- [ ] Orange Money API
- [ ] Free Money API
- [ ] WhatsApp Business API
- [ ] Service Email (SMTP)
- [ ] Service SMS

## 🔐 Sécurité

- Authentification via Laravel Sanctum
- Validation des données entrantes
- Protection CSRF
- Rate limiting sur les API
- Hashing des mots de passe (bcrypt)
- Certification des preuves visuelles (SHA256)

## 📝 Documentation API

La documentation API sera générée avec Swagger/OpenAPI (à venir).

## 🧪 Tests

```bash
# Exécuter les tests
php artisan test

# Avec coverage
php artisan test --coverage
```

## 📄 Licence

Propriétaire - Noor-Immo © 2024

## 👥 Équipe

Développé pour la diaspora sénégalaise 🇸🇳

---

**Noor-Immo** - Construire la Confiance, Gérer l'Avenir

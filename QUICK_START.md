# Bâti Yakaar API - Guide de Démarrage Rapide

## ⚡ Démarrage en 5 minutes

### 1. Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configuration Base de Données

Éditer `.env`:
```env
DB_DATABASE=batiyakaar
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Migration & Seeders

```bash
php artisan migrate
php artisan db:seed
```

### 4. Démarrer le serveur

```bash
php artisan serve
```

API disponible sur: `http://localhost:8000/api/v1`

---

## 🔥 Premier Test

### 1. Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"amadou.diop@diaspora.sn","password":"password123"}'
```

**Copier le token** de la réponse.

### 2. Tester un endpoint

```bash
curl -X GET http://localhost:8000/api/v1/projets \
  -H "Authorization: Bearer {VOTRE_TOKEN}" \
  -H "Accept: application/json"
```

---

## 📦 Postman (Recommandé)

1. Importer `Batiyakaar-API.postman_collection.json`
2. Exécuter **Authentication > Login**
3. Le token est sauvegardé automatiquement
4. Tester tous les autres endpoints !

---

## 🎯 Endpoints Principaux

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/auth/login` | POST | Connexion |
| `/projets` | GET | Liste projets |
| `/biens` | GET | Liste biens |
| `/baux` | GET | Liste baux |
| `/paiements-loyer` | POST | Créer paiement (auto-ventilation) |
| `/incidents` | POST | Créer incident |
| `/user/profile` | GET | Profil utilisateur |
| `/agence/dashboard` | GET | Stats agence |

---

## 🔐 Credentials Test

**Email**: `amadou.diop@diaspora.sn`  
**Password**: `password123`

Autres utilisateurs: voir `API_DOCUMENTATION.md`

---

## 📚 Documentation Complète

- **API Docs**: `API_DOCUMENTATION.md`
- **Postman**: `POSTMAN_README.md`
- **Swagger**: `http://localhost:8000/api/documentation` (après génération)

---

## 🚀 Générer Swagger

```bash
php artisan l5-swagger:generate
```

Accéder à: `http://localhost:8000/api/documentation`

---

**Prêt à développer !** 🎉

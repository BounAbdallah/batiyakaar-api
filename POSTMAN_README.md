# Collection Postman - Bâti Yakaar API

## 📦 Import dans Postman

1. Ouvrir Postman
2. Cliquer sur **Import**
3. Sélectionner le fichier `Batiyakaar-API.postman_collection.json`
4. La collection sera importée avec tous les endpoints

## 🔧 Configuration

### Variables de collection
- `base_url` : `http://localhost:8000/api/v1` (modifiable)
- `access_token` : Sera automatiquement rempli après le login

### Démarrer le serveur
```bash
cd batiyakaar-api
php artisan serve
```

## 🚀 Utilisation

### 1. Authentication
1. Aller dans **Authentication > Login**
2. Exécuter la requête
3. Le token sera automatiquement sauvegardé dans `{{access_token}}`
4. Toutes les autres requêtes utiliseront ce token automatiquement

### 2. Tester les endpoints
Tous les endpoints sont organisés par module :
- **Authentication** (4 endpoints)
- **Projets Construction** (5 endpoints)
- **Biens** (2 endpoints avec exemples)
- **Baux** (2 endpoints avec exemples)
- **Paiements Loyer** (2 endpoints)
- **Incidents** (4 endpoints)
- **Produits** (2 endpoints)
- **Commandes** (1 endpoint)
- **User** (3 endpoints)
- **Agence Dashboard** (1 endpoint)

## 🔐 Credentials de test

**Email:** `amadou.diop@diaspora.sn`  
**Password:** `password123`

Autres utilisateurs disponibles :
- `contact@immoplus.sn` (Agence)
- `mamadou.gueye@construction.sn` (Entrepreneur)
- `contact@ciment-plus.sn` (Fournisseur)
- `awa.diouf@email.sn` (Locataire)

## 📝 Exemples de requêtes

### Créer un projet
```json
{
  "bailleur_id": 1,
  "titre": "Construction Villa Almadies",
  "description": "Villa moderne 4 chambres",
  "adresse": "Almadies, Dakar",
  "budget_total": 50000000,
  "date_debut": "2024-01-15",
  "date_fin_prevue": "2024-12-31"
}
```

### Créer un paiement (avec auto-ventilation)
```json
{
  "bail_id": 1,
  "montant": 350000,
  "date_paiement": "2024-01-05",
  "date_prevue": "2024-01-01",
  "mode_paiement": "mobile_money",
  "reference_transaction": "OM123456789"
}
```

### Créer une commande
```json
{
  "bailleur_id": 1,
  "projet_construction_id": 1,
  "lignes": [
    {
      "produit_id": 1,
      "quantite": 100
    },
    {
      "produit_id": 2,
      "quantite": 50
    }
  ]
}
```

## 🎯 Features testables

- ✅ **Auto-ventilation** : Créer un paiement loyer et vérifier la ventilation automatique
- ✅ **Génération quittance** : Automatique lors d'un paiement
- ✅ **Assignment incidents** : Assigner un technicien à un incident
- ✅ **Gestion stock** : Mettre à jour le stock d'un produit
- ✅ **Dashboard agence** : Voir les statistiques en temps réel
- ✅ **Calcul commandes** : Total calculé automatiquement

## 📊 Endpoints disponibles (58 total)

| Module | Endpoints |
|--------|-----------|
| Authentication | 4 |
| Projets | 5 |
| Biens | 5 |
| Baux | 5 |
| Paiements Loyer | 5 |
| Incidents | 7 |
| Produits | 6 |
| Commandes | 5 |
| Livraisons | 5 |
| Transactions | 2 |
| User | 5 |
| Portefeuille | 2 |
| Agence | 2 |

## 🔍 Tests recommandés

1. **Login** → Vérifier que le token est sauvegardé
2. **Get User** → Vérifier les données utilisateur
3. **List Projets** → Tester les filtres
4. **Create Projet** → Créer un nouveau projet
5. **Create Paiement** → Vérifier la ventilation automatique
6. **Create Incident** → Créer et assigner
7. **Dashboard Agence** → Voir les statistiques

---

**Bâti Yakaar API** - Collection Postman complète ! 🚀

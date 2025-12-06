# Bâti Yakaar - Guide des Modèles Eloquent

## 📊 Vue d'ensemble

**32 modèles Eloquent** créés pour l'API Bâti Yakaar, organisés en 7 modules.

## ✅ Modèles Complétés (avec relations)

### Module 1: User Management

#### 1. User ✅ COMPLET
- **Fillable**: nom, prenom, email, telephone, password, user_type, actif
- **Casts**: email_verified_at (datetime), actif (boolean), password (hashed)
- **Soft Deletes**: Oui
- **Relations**:
  - `bailleur()` - hasOne
  - `agence()` - hasOne
  - `entrepreneur()` - hasOne
  - `fournisseur()` - hasOne
  - `locataire()` - hasOne
  - `portefeuilleVirtuel()` - hasOne
  - `notifications()` - hasMany
  - `transactionsEmises()` - hasMany
  - `transactionsRecues()` - hasMany
- **Scopes**: `active()`, `byType($type)`
- **Accessors**: `nomComplet`

#### 2. Bailleur ✅ COMPLET
- **Fillable**: user_id, pays, adresse_diaspora
- **Relations**:
  - `user()` - belongsTo
  - `projetsConstruction()` - hasMany
  - `biens()` - hasMany
  - `commandes()` - hasMany

#### 3-7. Agence, Entrepreneur, Fournisseur, Locataire, Technicien
- ⏳ **Modèles créés** - Relations à ajouter

### Module 2: Construction

#### 8. ProjetConstruction ✅ COMPLET
- **Table**: projets_construction
- **Fillable**: bailleur_id, titre, description, adresse, budget_total, budget_consomme, date_debut, date_fin_prevue, statut, pourcentage_avancement
- **Casts**: budget_total, budget_consomme, pourcentage_avancement (decimal:2), date_debut, date_fin_prevue (date)
- **Soft Deletes**: Oui
- **Relations**:
  - `bailleur()` - belongsTo
  - `chantier()` - hasOne
  - `paiementsEscrow()` - hasMany
  - `partiesPrenantes()` - hasMany
  - `rapports()` - hasMany
  - `commandes()` - hasMany
  - `bien()` - hasOne
- **Scopes**: `enCours()`, `termine()`, `parBailleur($id)`
- **Accessors**: `budgetRestant`

#### 9-14. Chantier, Etape, PreuveVisuelle, PaiementEscrow, PartiesPrenantes, RapportChantier
- ⏳ **Modèles créés** - Relations à ajouter

### Module 3: Gestion Locative (6 modèles)
- ⏳ **Modèles créés** - Relations à ajouter

### Module 4: Marketplace (5 modèles)
- ⏳ **Modèles créés** - Relations à ajouter

### Module 5: Financier (4 modèles)
- ⏳ **Modèles créés** - Relations à ajouter

### Module 6: Notifications & Abonnements (3 modèles)
- ⏳ **Modèles créés** - Relations à ajouter

## 📋 Statut Global

| Module | Modèles | Créés | Complétés |
|--------|---------|-------|-----------|
| User Management | 7 | ✅ 7/7 | ✅ 3/7 |
| Construction | 7 | ✅ 7/7 | ✅ 2/7 |
| Gestion Locative | 6 | ✅ 6/6 | ⏳ 0/6 |
| Marketplace | 5 | ✅ 5/5 | ⏳ 0/5 |
| Financier | 4 | ✅ 4/4 | ⏳ 0/4 |
| Notifications | 3 | ✅ 3/3 | ⏳ 0/3 |
| **TOTAL** | **32** | **✅ 32/32** | **⏳ 5/32** |

## 🎯 Prochaines Étapes

### Option 1: Compléter Tous les Modèles
Ajouter relations, fillable, casts, et scopes aux 27 modèles restants.

### Option 2: Créer les Seeders
Générer des données de test pour les modèles existants.

### Option 3: Créer les Controllers API
Commencer le développement des endpoints API.

## 📝 Template de Modèle

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NomDuModele extends Model
{
    use HasFactory;

    protected $fillable = [
        // Liste des champs
    ];

    protected function casts(): array
    {
        return [
            // Casts des champs
        ];
    }

    // Relations
    public function relationName()
    {
        return $this->belongsTo(RelatedModel::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }
}
```

## 🔧 Commandes Utiles

```bash
# Créer un modèle
php artisan make:model NomDuModele

# Créer un modèle avec migration
php artisan make:model NomDuModele -m

# Créer un modèle avec factory et seeder
php artisan make:model NomDuModele -fs

# Lister tous les modèles
ls -la app/Models/
```

## 📚 Documentation des Relations

### Types de Relations Utilisées

1. **hasOne** - Relation 1:1
   ```php
   public function profile() {
       return $this->hasOne(Profile::class);
   }
   ```

2. **hasMany** - Relation 1:N
   ```php
   public function posts() {
       return $this->hasMany(Post::class);
   }
   ```

3. **belongsTo** - Relation inverse
   ```php
   public function user() {
       return $this->belongsTo(User::class);
   }
   ```

## ✅ Modèles Prêts à Utiliser

Les modèles suivants sont complets et prêts :
- ✅ **User** - Authentification et relations polymorphiques
- ✅ **Bailleur** - Gestion des propriétaires
- ✅ **ProjetConstruction** - Gestion des projets avec escrow

---

**Note**: Les 27 modèles restants ont été créés mais nécessitent l'ajout des relations, fillable, casts, et scopes selon le plan d'implémentation.

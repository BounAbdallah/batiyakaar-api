<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom_entreprise',
        'categorie_materiaux',
        'adresse_entrepot',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function catalogue()
    {
        return $this->hasOne(Catalogue::class);
    }

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }

    public function livraisons()
    {
        return $this->hasMany(Livraison::class);
    }
}

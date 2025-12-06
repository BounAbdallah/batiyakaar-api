<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalogue_id',
        'fournisseur_id',
        'reference',
        'nom',
        'description',
        'categorie',
        'prix_unitaire',
        'unite',
        'stock_disponible',
        'url_image',
    ];

    protected function casts(): array
    {
        return [
            'prix_unitaire' => 'decimal:2',
            'stock_disponible' => 'integer',
        ];
    }

    // Relationships
    public function catalogue()
    {
        return $this->belongsTo(Catalogue::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function lignesCommande()
    {
        return $this->hasMany(LigneCommande::class);
    }

    // Scopes
    public function scopeEnStock($query)
    {
        return $query->where('stock_disponible', '>', 0);
    }

    public function scopeParCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }
}

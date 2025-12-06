<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalogue extends Model
{
    use HasFactory;

    protected $fillable = [
        'fournisseur_id',
        'nom',
        'description',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    // Relationships
    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}

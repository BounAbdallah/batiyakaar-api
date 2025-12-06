<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'fournisseur_id',
        'date_livraison_prevue',
        'date_livraison_effective',
        'adresse_livraison',
        'statut',
        'url_preuve',
    ];

    protected function casts(): array
    {
        return [
            'date_livraison_prevue' => 'date',
            'date_livraison_effective' => 'date',
        ];
    }

    // Relationships
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    // Scopes
    public function scopeLivree($query)
    {
        return $query->where('statut', 'livree');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }
}

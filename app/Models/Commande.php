<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'bailleur_id',
        'projet_construction_id',
        'numero_commande',
        'date_commande',
        'montant_total',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_commande' => 'datetime',
            'montant_total' => 'decimal:2',
        ];
    }

    // Relationships
    public function bailleur()
    {
        return $this->belongsTo(Bailleur::class);
    }

    public function projetConstruction()
    {
        return $this->belongsTo(ProjetConstruction::class);
    }

    public function lignesCommande()
    {
        return $this->hasMany(LigneCommande::class);
    }

    public function livraison()
    {
        return $this->hasOne(Livraison::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    // Scopes
    public function scopeParStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }
}

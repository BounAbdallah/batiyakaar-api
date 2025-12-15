<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaiementLoyer extends Model
{
    use HasFactory;

    protected $table = 'paiements_loyer';

    protected $fillable = [
        'bail_id',
        'montant',
        'montant_attendu',
        'date_paiement',
        'date_prevue',
        'mode_paiement',
        'statut',
        'reference_transaction',
        'periode_debut',
        'periode_fin',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'montant_attendu' => 'decimal:2',
            'date_paiement' => 'date',
            'date_prevue' => 'date',
        ];
    }

    // Relationships
    public function bail()
    {
        return $this->belongsTo(Bail::class);
    }

    public function quittance()
    {
        return $this->hasOne(Quittance::class);
    }

    public function ventilation()
    {
        return $this->hasOne(Ventilation::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    // Scopes
    public function scopePaye($query)
    {
        return $query->where('statut', 'paye');
    }

    public function scopePartiel($query)
    {
        return $query->where('statut', 'partiel');
    }

    public function scopeImpaye($query)
    {
        return $query->where('statut', 'impaye');
    }

    public function scopeEnRetard($query)
    {
        return $query->where('statut', 'en_retard');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ventilation extends Model
{
    use HasFactory;

    protected $fillable = [
        'paiement_loyer_id',
        'montant_agence',
        'montant_plateforme',
        'montant_bailleur',
        'date_ventilation',
    ];

    protected function casts(): array
    {
        return [
            'montant_agence' => 'decimal:2',
            'montant_plateforme' => 'decimal:2',
            'montant_bailleur' => 'decimal:2',
            'date_ventilation' => 'datetime',
        ];
    }

    // Relationships
    public function paiementLoyer()
    {
        return $this->belongsTo(PaiementLoyer::class);
    }
}

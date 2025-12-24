<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'paiement_loyer_id',
        'numero_quittance',
        'montant',
        'periode_debut',
        'periode_fin',
        'date_emission',
        'url_pdf',
    ];

    protected function casts(): array
    {
        return [
            'date_emission' => 'datetime',
            'periode_debut' => 'date',
            'periode_fin' => 'date',
            'montant' => 'decimal:2',
        ];
    }

    // Relationships
    public function paiementLoyer()
    {
        return $this->belongsTo(PaiementLoyer::class);
    }
}

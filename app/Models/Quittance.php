<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'paiement_loyer_id',
        'numero',
        'date_emission',
        'url_pdf',
    ];

    protected function casts(): array
    {
        return [
            'date_emission' => 'datetime',
        ];
    }

    // Relationships
    public function paiementLoyer()
    {
        return $this->belongsTo(PaiementLoyer::class);
    }
}

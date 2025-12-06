<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrepreneur extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialite',
        'registre_commerce',
        'tarif_journalier',
    ];

    protected function casts(): array
    {
        return [
            'tarif_journalier' => 'decimal:2',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function preuvesVisuelles()
    {
        return $this->hasMany(PreuveVisuelle::class);
    }

    public function paiementsEscrow()
    {
        return $this->hasMany(PaiementEscrow::class);
    }
}

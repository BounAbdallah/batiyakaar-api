<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteDepense extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'mois',
        'annee',
        'description',
        'total_montant',
        'statut',
        'agence_id',
        'bailleur_id',
        'immeuble_id',
    ];

    protected $casts = [
        'mois' => 'integer',
        'annee' => 'integer',
        'total_montant' => 'decimal:2',
    ];

    // Relationships
    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function bailleur()
    {
        return $this->belongsTo(Bailleur::class);
    }

    public function immeuble()
    {
        return $this->belongsTo(Immeuble::class);
    }

    public function depenses()
    {
        return $this->hasMany(Depense::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bail extends Model
{
    use HasFactory;

    protected $fillable = [
        'bien_id',
        'locataire_id',
        'agence_id',
        'date_debut',
        'date_fin',
        'loyer_mensuel',
        'caution',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'loyer_mensuel' => 'decimal:2',
            'caution' => 'decimal:2',
        ];
    }

    // Relationships
    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function locataire()
    {
        return $this->belongsTo(Locataire::class);
    }

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function paiementsLoyer()
    {
        return $this->hasMany(PaiementLoyer::class);
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    public function etatsDesLieux()
    {
        return $this->hasMany(EtatDesLieux::class);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeExpire($query)
    {
        return $query->where('statut', 'expire');
    }
}

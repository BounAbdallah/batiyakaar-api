<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bien extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bailleur_id',
        'agence_id',
        'projet_construction_id',
        'reference',
        'adresse',
        'type',
        'nombre_pieces',
        'surface',
        'loyer_mensuel',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'surface' => 'decimal:2',
            'loyer_mensuel' => 'decimal:2',
        ];
    }

    // Relationships
    public function bailleur()
    {
        return $this->belongsTo(Bailleur::class);
    }

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function projetConstruction()
    {
        return $this->belongsTo(ProjetConstruction::class);
    }

    public function baux()
    {
        return $this->hasMany(Bail::class);
    }

    // Scopes
    public function scopeDisponible($query)
    {
        return $query->where('statut', 'disponible');
    }

    public function scopeLoue($query)
    {
        return $query->where('statut', 'loue');
    }

    public function scopeParAgence($query, $agenceId)
    {
        return $query->where('agence_id', $agenceId);
    }
}

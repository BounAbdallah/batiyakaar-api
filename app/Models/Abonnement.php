<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abonnement extends Model
{
    use HasFactory;

    protected $fillable = [
        'agence_id',
        'plan_id',
        'date_debut',
        'date_fin',
        'statut',
        'auto_renouvellement',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'auto_renouvellement' => 'boolean',
        ];
    }

    // Relationships
    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'prix_mensuel',
        'prix_annuel',
        'limite_utilisateurs',
        'limite_biens',
        'fonctionnalites',
        'actif',
        'est_personnalise',
        'est_public',
        'access_token',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'prix_mensuel' => 'decimal:2',
            'prix_annuel' => 'decimal:2',
            'limite_utilisateurs' => 'integer',
            'limite_biens' => 'integer',
            'fonctionnalites' => 'array',
            'actif' => 'boolean',
            'est_personnalise' => 'boolean',
            'est_public' => 'boolean',
            'token_expires_at' => 'datetime',
        ];
    }

    // Relationships
    public function abonnements()
    {
        return $this->hasMany(Abonnement::class);
    }

    public function agences()
    {
        return $this->hasManyThrough(
            \App\Models\Agence::class,
            Abonnement::class,
            'plan_id',      // Foreign key on abonnements table
            'id',           // Foreign key on agences table
            'id',           // Local key on plans table
            'agence_id'     // Local key on abonnements table
        );
    }

    public function fonctionnalites()
    {
        return $this->belongsToMany(Fonctionnalite::class, 'plan_fonctionnalite');
    }

    public function fonctionnalitesActives()
    {
        return $this->belongsToMany(Fonctionnalite::class, 'plan_fonctionnalite')
            ->where('actif', true)
            ->orderBy('ordre');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}

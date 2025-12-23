<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'raison_sociale',
        'ninea',
        'rccm',
        'adresse',
        'logo',
        'taux_commission_agence',
        'taux_commission_plateforme',
    ];

    // Accessors
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            // Use full URL with correct port for dev environment
            return config('app.url') . ':8000/storage/logos/' . $this->logo;
        }
        return null;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function techniciens()
    {
        return $this->hasMany(Technicien::class);
    }

    public function baux()
    {
        return $this->hasMany(Bail::class);
    }

    public function biens()
    {
        return $this->hasMany(Bien::class);
    }

    public function abonnement()
    {
        return $this->hasOne(Abonnement::class);
    }
    public function immeubles()
    {
        return $this->hasMany(Immeuble::class);
    }

    public function equipe()
    {
        return $this->hasMany(User::class, 'agence_id');
    }

    // Helper methods for features
    public function getFonctionnalitesDisponibles()
    {
        $abonnement = $this->abonnement()
            ->where('statut', 'actif')
            ->with('plan.fonctionnalitesActives')
            ->first();

        return $abonnement?->plan?->fonctionnalitesActives ?? collect();
    }

    public function hasFonctionnalite($code)
    {
        return $this->getFonctionnalitesDisponibles()
            ->contains('code', $code);
    }
}

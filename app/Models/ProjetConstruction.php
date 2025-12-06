<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjetConstruction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projets_construction';

    protected $fillable = [
        'bailleur_id',
        'titre',
        'description',
        'adresse',
        'budget_total',
        'budget_consomme',
        'date_debut',
        'date_fin_prevue',
        'statut',
        'pourcentage_avancement',
    ];

    protected function casts(): array
    {
        return [
            'budget_total' => 'decimal:2',
            'budget_consomme' => 'decimal:2',
            'pourcentage_avancement' => 'decimal:2',
            'date_debut' => 'date',
            'date_fin_prevue' => 'date',
        ];
    }

    // Relationships
    public function bailleur()
    {
        return $this->belongsTo(Bailleur::class);
    }

    public function chantier()
    {
        return $this->hasOne(Chantier::class);
    }

    public function paiementsEscrow()
    {
        return $this->hasMany(PaiementEscrow::class);
    }

    public function partiesPrenantes()
    {
        return $this->hasMany(PartiesPrenantes::class);
    }

    public function rapports()
    {
        return $this->hasMany(RapportChantier::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function bien()
    {
        return $this->hasOne(Bien::class);
    }

    // Scopes
    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTermine($query)
    {
        return $query->where('statut', 'termine');
    }

    public function scopeParBailleur($query, $bailleurId)
    {
        return $query->where('bailleur_id', $bailleurId);
    }

    // Accessors
    public function getBudgetRestantAttribute()
    {
        return $this->budget_total - $this->budget_consomme;
    }
}

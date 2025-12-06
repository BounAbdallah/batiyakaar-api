<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etape extends Model
{
    use HasFactory;

    protected $fillable = [
        'chantier_id',
        'nom',
        'description',
        'ordre',
        'date_debut',
        'date_fin',
        'statut',
        'pourcentage',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'pourcentage' => 'decimal:2',
        ];
    }

    // Relationships
    public function chantier()
    {
        return $this->belongsTo(Chantier::class);
    }

    public function preuvesVisuelles()
    {
        return $this->hasMany(PreuveVisuelle::class);
    }

    // Scopes
    public function scopeParOrdre($query)
    {
        return $query->orderBy('ordre');
    }

    public function scopeTermine($query)
    {
        return $query->where('statut', 'termine');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }
}

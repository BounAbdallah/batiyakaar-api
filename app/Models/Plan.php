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
        'limite_utilisateurs',
        'limite_biens',
        'fonctionnalites',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'prix_mensuel' => 'decimal:2',
            'limite_utilisateurs' => 'integer',
            'limite_biens' => 'integer',
            'fonctionnalites' => 'array',
            'actif' => 'boolean',
        ];
    }

    // Relationships
    public function abonnements()
    {
        return $this->hasMany(Abonnement::class);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}

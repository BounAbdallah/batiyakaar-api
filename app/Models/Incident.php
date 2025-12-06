<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'bail_id',
        'locataire_id',
        'technicien_id',
        'titre',
        'description',
        'categorie',
        'priorite',
        'statut',
        'date_declaration',
        'date_resolution',
    ];

    protected function casts(): array
    {
        return [
            'date_declaration' => 'datetime',
            'date_resolution' => 'datetime',
        ];
    }

    // Relationships
    public function bail()
    {
        return $this->belongsTo(Bail::class);
    }

    public function locataire()
    {
        return $this->belongsTo(Locataire::class);
    }

    public function technicien()
    {
        return $this->belongsTo(Technicien::class);
    }

    // Scopes
    public function scopeOuvert($query)
    {
        return $query->where('statut', 'ouvert');
    }

    public function scopeResolu($query)
    {
        return $query->where('statut', 'resolu');
    }

    public function scopeParPriorite($query, $priorite)
    {
        return $query->where('priorite', $priorite);
    }
}

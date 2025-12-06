<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaiementEscrow extends Model
{
    use HasFactory;

    protected $table = 'paiements_escrow';

    protected $fillable = [
        'projet_construction_id',
        'entrepreneur_id',
        'montant',
        'date_depot',
        'date_deblocage',
        'statut',
        'condition_deblocage',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_depot' => 'datetime',
            'date_deblocage' => 'datetime',
        ];
    }

    // Relationships
    public function projetConstruction()
    {
        return $this->belongsTo(ProjetConstruction::class);
    }

    public function entrepreneur()
    {
        return $this->belongsTo(Entrepreneur::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    // Scopes
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeDebloque($query)
    {
        return $query->where('statut', 'debloque');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'emetteur_id',
        'beneficiaire_id',
        'montant',
        'type',
        'statut',
        'mode_paiement',
        'date_transaction',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_transaction' => 'datetime',
        ];
    }

    // Relationships
    public function emetteur()
    {
        return $this->belongsTo(User::class, 'emetteur_id');
    }

    public function beneficiaire()
    {
        return $this->belongsTo(User::class, 'beneficiaire_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    // Scopes
    public function scopeReussie($query)
    {
        return $query->where('statut', 'reussie');
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'taux',
        'montant',
        'type',
        'beneficiaire',
    ];

    protected function casts(): array
    {
        return [
            'taux' => 'decimal:2',
            'montant' => 'decimal:2',
        ];
    }

    // Relationships
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}

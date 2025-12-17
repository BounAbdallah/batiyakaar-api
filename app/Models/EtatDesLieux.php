<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtatDesLieux extends Model
{
    use HasFactory;

    protected $table = 'etat_des_lieuxes';

    protected $fillable = [
        'bail_id',
        'type',
        'date_etat_des_lieux',
        'observations',
        'content',
        'documents',
    ];

    protected function casts(): array
    {
        return [
            'date_etat_des_lieux' => 'date',
            'content' => 'array',
            'documents' => 'array',
        ];
    }

    // Relationships
    public function bail()
    {
        return $this->belongsTo(Bail::class);
    }
}

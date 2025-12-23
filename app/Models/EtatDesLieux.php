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
        'date_etat',
        'remarques',
        'effectue_par',
        'content',
        'documents',
    ];

    protected function casts(): array
    {
        return [
            'date_etat' => 'date',
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

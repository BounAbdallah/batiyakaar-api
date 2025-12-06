<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtatDesLieux extends Model
{
    use HasFactory;

    protected $table = 'etats_des_lieux';

    protected $fillable = [
        'bail_id',
        'type',
        'date',
        'observations',
        'url_photos',
        'signature',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'url_photos' => 'array',
        ];
    }

    // Relationships
    public function bail()
    {
        return $this->belongsTo(Bail::class);
    }
}

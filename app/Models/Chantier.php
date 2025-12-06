<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chantier extends Model
{
    use HasFactory;

    protected $fillable = [
        'projet_construction_id',
        'localisation',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    // Relationships
    public function projetConstruction()
    {
        return $this->belongsTo(ProjetConstruction::class);
    }

    public function etapes()
    {
        return $this->hasMany(Etape::class);
    }
}

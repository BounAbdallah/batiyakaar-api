<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RapportChantier extends Model
{
    use HasFactory;

    protected $table = 'rapports_chantier';

    protected $fillable = [
        'projet_construction_id',
        'date_generation',
        'type_fichier',
        'url_pdf',
        'contenu',
    ];

    protected function casts(): array
    {
        return [
            'date_generation' => 'datetime',
            'contenu' => 'array',
        ];
    }

    // Relationships
    public function projetConstruction()
    {
        return $this->belongsTo(ProjetConstruction::class);
    }
}

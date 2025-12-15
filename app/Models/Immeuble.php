<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Immeuble extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'adresse',
        'description',
        'image',
        'nombre_etages',
        'bailleur_id',
        'agence_id',
    ];

    public function bailleur()
    {
        return $this->belongsTo(Bailleur::class);
    }

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function etages()
    {
        return $this->hasMany(Etage::class);
    }

    public function biens()
    {
        return $this->hasMany(Bien::class);
    }
}

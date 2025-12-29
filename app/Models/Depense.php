<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_depense_id',
        'titre',
        'description',
        'montant',
        'date_depense',
        'categorie',
        'statut',
        'preuve',
        'agence_id',
        'bailleur_id',
        'immeuble_id',
        'bien_id',
    ];

    protected $casts = [
        'date_depense' => 'date',
        'montant' => 'decimal:2',
    ];

    // Relationships
    public function noteDepense()
    {
        return $this->belongsTo(NoteDepense::class);
    }

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function bailleur()
    {
        return $this->belongsTo(Bailleur::class);
    }

    public function immeuble()
    {
        return $this->belongsTo(Immeuble::class);
    }

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }
}

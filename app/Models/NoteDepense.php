<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteDepense extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function depenses()
    {
        return $this->hasMany(Depense::class);
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

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }
}

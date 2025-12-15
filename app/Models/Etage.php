<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etage extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'numero',
        'immeuble_id',
    ];

    public function immeuble()
    {
        return $this->belongsTo(Immeuble::class);
    }

    public function biens()
    {
        return $this->hasMany(Bien::class);
    }
}

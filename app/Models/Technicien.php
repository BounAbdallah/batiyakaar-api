<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Technicien extends Model
{
    use HasFactory;

    protected $fillable = [
        'agence_id',
        'nom',
        'telephone',
        'specialite',
    ];

    // Relationships
    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }
}

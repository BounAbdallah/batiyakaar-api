<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreuveVisuelle extends Model
{
    use HasFactory;

    protected $table = 'preuves_visuelles';

    protected $fillable = [
        'etape_id',
        'entrepreneur_id',
        'type',
        'url_fichier',
        'horodatage',
        'latitude',
        'longitude',
        'hash_certification',
        'validee',
    ];

    protected function casts(): array
    {
        return [
            'horodatage' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'validee' => 'boolean',
        ];
    }

    // Relationships
    public function etape()
    {
        return $this->belongsTo(Etape::class);
    }

    public function entrepreneur()
    {
        return $this->belongsTo(Entrepreneur::class);
    }

    // Scopes
    public function scopeValidees($query)
    {
        return $query->where('validee', true);
    }

    public function scopeEnAttente($query)
    {
        return $query->where('validee', false);
    }

    // Mutators - Generate hash on save
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($preuveVisuelle) {
            if (!$preuveVisuelle->hash_certification) {
                $preuveVisuelle->hash_certification = hash(
                    'sha256',
                    $preuveVisuelle->url_fichier .
                    $preuveVisuelle->horodatage .
                    $preuveVisuelle->latitude .
                    $preuveVisuelle->longitude
                );
            }
        });
    }
}

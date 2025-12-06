<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'titre',
        'message',
        'type',
        'date_envoi',
        'lue',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'date_envoi' => 'datetime',
            'lue' => 'boolean',
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeNonLues($query)
    {
        return $query->where('lue', false);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    // Usiamo $fillable per tutti i campi che possiamo valorizzare in massa.
    protected $fillable = [
        'name',
        'country',
        'latitude',
        'longitude',
    ];

    // Convertiamo automaticamente quando leggiamo dal DB
    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
    ];
}

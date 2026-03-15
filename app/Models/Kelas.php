<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = [
        'nama',
        'keterangan',
    ];

    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class);
    }
}
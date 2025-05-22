<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pekerjaan extends Model
{
    use HasFactory;
    
    protected $fillable = ['nama', 'aktif'];
    
    protected $casts = [
        'aktif' => 'boolean',
    ];
    
    public function donaturs(): HasMany
    {
        return $this->hasMany(Donatur::class);
    }
}
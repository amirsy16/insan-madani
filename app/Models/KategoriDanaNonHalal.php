<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KategoriDanaNonHalal extends Model
{
    use HasFactory;

    protected $table = 'kategori_dana_non_halal';

    protected $fillable = [
        'nama',
        'deskripsi',
        'aktif',
        'urutan',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeUrutan($query)
    {
        return $query->orderBy('urutan');
    }
}

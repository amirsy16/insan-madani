<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisDonasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'apakah_barang',
        'membutuhkan_keterangan_tambahan',
        'sumber_dana_penyaluran_id',
        'aktif',
    ];

    protected $casts = [
        'apakah_barang' => 'boolean',
        'membutuhkan_keterangan_tambahan' => 'boolean',
        'aktif' => 'boolean',
    ];

    public function sumberDanaPenyaluran()
    {
        return $this->belongsTo(SumberDanaPenyaluran::class, 'sumber_dana_penyaluran_id');
    }

    public function donasis()
    {
        return $this->hasMany(Donasi::class, 'jenis_donasi_id');
    }
}

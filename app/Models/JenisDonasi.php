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
        'mengandung_dana_non_halal',
        'keterangan_dana_non_halal',
    ];

    protected $casts = [
        'apakah_barang' => 'boolean',
        'membutuhkan_keterangan_tambahan' => 'boolean',
        'aktif' => 'boolean',
        'mengandung_dana_non_halal' => 'boolean',
    ];

    public function sumberDanaPenyaluran()
    {
        return $this->belongsTo(SumberDanaPenyaluran::class, 'sumber_dana_penyaluran_id');
    }

    public function donasis()
    {
        return $this->hasMany(Donasi::class, 'jenis_donasi_id');
    }

    /**
     * Scope untuk jenis donasi yang mengandung dana non halal
     */
    public function scopeMengandungDanaNonHalal($query)
    {
        return $query->where('mengandung_dana_non_halal', true);
    }

    /**
     * Scope untuk jenis donasi yang halal
     */
    public function scopeHalal($query)
    {
        return $query->where('mengandung_dana_non_halal', false);
    }

    /**
     * Check apakah jenis donasi ini mengandung dana non halal
     */
    public function isMengandungDanaNonHalal(): bool
    {
        return $this->mengandung_dana_non_halal === true;
    }

    /**
     * Get status dana halal dalam bentuk text
     */
    public function getStatusDanaHalalAttribute(): string
    {
        return $this->mengandung_dana_non_halal ? 'Dana Non Halal' : 'Dana Halal';
    }

    /**
     * Get keterangan lengkap untuk dana non halal
     */
    public function getKeteranganDanaNonHalalLengkapAttribute(): string
    {
        if (!$this->mengandung_dana_non_halal) {
            return 'Dana ini halal dan dapat disalurkan secara umum.';
        }

        $keterangan = $this->keterangan_dana_non_halal ?: 'Dana mengandung unsur non halal';
        return "⚠️ PERHATIAN: {$keterangan}. Dana ini harus disalurkan sesuai ketentuan syariah.";
    }

    /**
     * Get warna badge untuk status dana
     */
    public function getStatusDanaColor(): string
    {
        return $this->mengandung_dana_non_halal ? 'danger' : 'success';
    }
}

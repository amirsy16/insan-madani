<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPenggunaanHakAmil extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'persentase_hak_amil',
        'jenis_donasi_id',
        'sumber_dana_penyaluran_id',
        'aktif',
        'tanggal_berlaku_mulai',
        'tanggal_berlaku_berakhir',
    ];

    protected $casts = [
        'persentase_hak_amil' => 'decimal:2',
        'aktif' => 'boolean',
        'tanggal_berlaku_mulai' => 'date',
        'tanggal_berlaku_berakhir' => 'date',
    ];

    // Relasi ke JenisDonasi untuk pengaturan per jenis donasi
    public function jenisDonasi(): BelongsTo
    {
        return $this->belongsTo(JenisDonasi::class);
    }

    // Relasi ke SumberDanaPenyaluran untuk pengaturan per sumber dana
    public function sumberDanaPenyaluran(): BelongsTo
    {
        return $this->belongsTo(SumberDanaPenyaluran::class);
    }

    // Relasi ke PenggunaanHakAmil
    public function penggunaanHakAmils(): HasMany
    {
        return $this->hasMany(PenggunaanHakAmil::class);
    }

    // Scope untuk pengaturan aktif
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    // Scope untuk pengaturan yang berlaku saat ini
    public function scopeBerlaku($query, $tanggal = null)
    {
        $tanggal = $tanggal ?? now()->toDateString();
        
        return $query->where(function ($q) use ($tanggal) {
            $q->where(function ($subQ) use ($tanggal) {
                $subQ->whereNull('tanggal_berlaku_mulai')
                     ->orWhere('tanggal_berlaku_mulai', '<=', $tanggal);
            })
            ->where(function ($subQ) use ($tanggal) {
                $subQ->whereNull('tanggal_berlaku_berakhir')
                     ->orWhere('tanggal_berlaku_berakhir', '>=', $tanggal);
            });
        });
    }

    // Method untuk mendapat persentase hak amil dari sumber dana penyaluran
    // DEPRECATED: Gunakan langsung SumberDanaPenyaluran->persentase_hak_amil
    public static function getPersentaseHakAmil($jenisDonasi = null, $sumberDana = null, $tanggal = null)
    {
        // Sistem baru: ambil persentase dari sumber dana penyaluran
        if ($sumberDana) {
            $sumberDanaModel = \App\Models\SumberDanaPenyaluran::find($sumberDana);
            return $sumberDanaModel ? ($sumberDanaModel->persentase_hak_amil ?? 0) : 0;
        }
        
        // Fallback: cari dari jenis donasi
        if ($jenisDonasi) {
            $jenisDonasi = \App\Models\JenisDonasi::find($jenisDonasi);
            if ($jenisDonasi && $jenisDonasi->sumber_dana_penyaluran_id) {
                $sumberDanaModel = \App\Models\SumberDanaPenyaluran::find($jenisDonasi->sumber_dana_penyaluran_id);
                return $sumberDanaModel ? ($sumberDanaModel->persentase_hak_amil ?? 0) : 0;
            }
        }
        
        // Default
        return 0;
    }
}
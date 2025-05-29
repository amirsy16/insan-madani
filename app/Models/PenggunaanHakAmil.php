<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Tambahkan ini

class PenggunaanHakAmil extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'jenis_penggunaan_hak_amil_id',
        'keterangan', // Tambahkan ini jika ada
        'jumlah',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jenisPenggunaanHakAmil(): BelongsTo // Relasi ke master data
    {
        return $this->belongsTo(JenisPenggunaanHakAmil::class);
    }
}
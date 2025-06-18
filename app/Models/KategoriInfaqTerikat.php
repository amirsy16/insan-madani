<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriInfaqTerikat extends Model
{
    use HasFactory;

    protected $table = 'kategori_infaq_terikats';

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'aktif',
        'urutan',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    /**
     * Scope untuk kategori aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope untuk pengurutan
     */
    public function scopeUrutan($query)
    {
        return $query->orderBy('urutan', 'asc')->orderBy('nama_kategori', 'asc');
    }
}

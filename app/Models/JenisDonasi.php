<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisDonasi extends Model {
    use HasFactory;
    protected $fillable = [
        'nama', 'kode', 'deskripsi',
        'membutuhkan_keterangan_tambahan', 'apakah_barang', 'aktif'
    ];
    protected $casts = [
        'membutuhkan_keterangan_tambahan' => 'boolean',
        'apakah_barang' => 'boolean',
        'aktif' => 'boolean',
    ];
    public function donasis(): HasMany {
        return $this->hasMany(Donasi::class, 'jenis_donasi_id');
    }
}
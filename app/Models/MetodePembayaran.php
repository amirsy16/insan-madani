<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodePembayaran extends Model {
    use HasFactory;
    protected $fillable = [
        'nama', 'kode', 'tipe', 'nomor_rekening',
        'atas_nama_rekening', 'bank_name', 'instruksi_pembayaran', 'aktif'
    ];
    protected $casts = ['aktif' => 'boolean'];
    public function donasis(): HasMany {
        return $this->hasMany(Donasi::class, 'metode_pembayaran_id');
    }
}
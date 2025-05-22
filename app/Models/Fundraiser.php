<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fundraiser extends Model {
    use HasFactory;
    protected $fillable = [
        'nama_fundraiser', 'nomor_identitas', 'nomor_hp',
        'alamat', 'user_id', 'aktif'
    ];
    protected $casts = ['aktif' => 'boolean'];
    public function user(): BelongsTo { // Relasi ke User (jika fundraiser adalah user)
        return $this->belongsTo(User::class, 'user_id');
    }
    public function donasis(): HasMany {
        return $this->hasMany(Donasi::class, 'fundraiser_id');
    }
}
<?php

// app/Models/ProgramPenyaluran.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Jika menggunakan soft delete

class ProgramPenyaluran extends Model
{
    use HasFactory, SoftDeletes; // Jika menggunakan soft delete

    protected $fillable = [
        'kode_program_penyaluran',
        'nama_program',
        'tanggal_penyaluran',
        'jumlah_dana',
        'sumber_dana_penyaluran_id',
        'jenis_donasi_id', // Sumber spesifik jika ada
        'asnaf_id',
        'bidang_program_id',
        'penerima_manfaat_individu',
        'penerima_manfaat_lembaga',
        'jumlah_penerima_manfaat',
        'lokasi_penyaluran',
        'keterangan',
        'bukti_penyaluran',
        'dicatat_oleh_id',
    ];

    protected $casts = [
        'tanggal_penyaluran' => 'date',
        'jumlah_dana' => 'decimal:2',
    ];

    public function sumberDanaPenyaluran()
    {
        return $this->belongsTo(SumberDanaPenyaluran::class);
    }

    public function jenisDonasi() // Sumber spesifik jika ada
    {
        return $this->belongsTo(JenisDonasi::class); //
    }

    public function asnaf()
    {
        return $this->belongsTo(Asnaf::class);
    }

    public function bidangProgram()
    {
        return $this->belongsTo(BidangProgram::class);
    }

    public function dicatatOleh()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh_id'); //
    }

    // Accessor untuk memudahkan menampilkan nama penerima manfaat
    public function getNamaPenerimaManfaatAttribute(): string
    {
        return $this->penerima_manfaat_individu ?: $this->penerima_manfaat_lembaga ?: 'N/A';
    }


}
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donasi extends Model {
    use HasFactory;
    protected $fillable = [
        'donatur_id', 'jenis_donasi_id', 'metode_pembayaran_id', 'fundraiser_id',
        'jumlah', 'keterangan_infak_khusus', 'kategori_dana_non_halal_id', 'deskripsi_barang', 'perkiraan_nilai_barang',
        'bukti_pembayaran', 'catatan_donatur', 'tanggal_donasi', 'atas_nama_hamba_allah', 'nomor_transaksi_unik',
        'status_konfirmasi', 'dikofirmasi_oleh_user_id', 'dikonfirmasi_pada', 'catatan_konfirmasi',
        'dicatat_oleh_user_id',
    ];
    protected $casts = [
        'jumlah' => 'decimal:2',
        'perkiraan_nilai_barang' => 'decimal:2',
        'tanggal_donasi' => 'date',
        'atas_nama_hamba_allah' => 'boolean',
        'dikonfirmasi_pada' => 'datetime',
    ];
    public function donatur(): BelongsTo { return $this->belongsTo(Donatur::class, 'donatur_id'); }
    public function jenisDonasi(): BelongsTo { return $this->belongsTo(JenisDonasi::class, 'jenis_donasi_id'); }
    public function metodePembayaran(): BelongsTo { return $this->belongsTo(MetodePembayaran::class, 'metode_pembayaran_id'); }
    public function fundraiser(): BelongsTo { return $this->belongsTo(Fundraiser::class, 'fundraiser_id'); }
    public function kategoriDanaNonHalal(): BelongsTo { return $this->belongsTo(KategoriDanaNonHalal::class, 'kategori_dana_non_halal_id'); }
    public function dikonfirmasiOleh(): BelongsTo { return $this->belongsTo(User::class, 'dikonfirmasi_oleh_user_id'); }
    public function dicatatOleh(): BelongsTo { return $this->belongsTo(User::class, 'dicatat_oleh_user_id'); }
    public function invoices(): HasMany { return $this->hasMany(InvoiceDonasi::class); }
    /**
     * Get the latest invoice for this donation
     */
    public function latestInvoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceDonasi::class, 'id', 'donasi_id')
                    ->latest();
    }
    /**
     * Check if this donation has any invoices
     */
    public function hasInvoices(): bool
    {
        return $this->invoices()->exists();
    }
    /**
     * Get successful invoice deliveries
     */
    public function successfulInvoices(): HasMany
    {
        return $this->invoices()->whereIn('delivery_status', ['sent', 'delivered']);
    }

    /**
     * Get total nilai donasi (cash + barang)
     */
    public function getTotalNilaiAttribute(): float
    {
        return ($this->jumlah ?? 0) + ($this->perkiraan_nilai_barang ?? 0);
    }

    /**
     * Get perhitungan hak amil berdasarkan pengaturan
     */
    public function getHakAmilAttribute(): float
    {
        $totalNilai = $this->total_nilai;
        
        if ($totalNilai <= 0) {
            return 0;
        }

        // Cari sumber dana penyaluran dari jenis donasi
        $sumberDanaId = $this->jenisDonasi ? $this->jenisDonasi->sumber_dana_penyaluran_id : null;
        $sumberDana = SumberDanaPenyaluran::find($sumberDanaId);

        $persentase = $sumberDana ? ($sumberDana->persentase_hak_amil ?? 0) : 0;

        return ($totalNilai * $persentase) / 100;
    }

    /**
     * Get sisa donasi setelah dikurangi hak amil
     */
    public function getSisaDonasiAttribute(): float
    {
        return $this->total_nilai - $this->hak_amil;
    }
}

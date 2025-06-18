<?php

namespace App\Filament\Imports;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\Fundraiser;
use App\Models\JenisDonasi;
use App\Models\KategoriInfaqTerikat;
use App\Models\MetodePembayaran;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Carbon\Carbon;

/**
 * DONASI IMPORTER - RESOLVE EXISTING DATA
 * =======================================
 * 
 * Donasi Importer untuk mencocokkan data import dengan data yang sudah ada di database:
 *
 * 1. RESOLVE DONATUR:
 *    - Cari berdasarkan nama persis
 *    - Jika tidak ada, cari berdasarkan nomor HP
 *    - Jika tidak ada, cari dengan LIKE pattern
 *    - Jika masih tidak ditemukan, skip record dan log warning
 *
 * 2. AUTO-CREATE JENIS DONASI:
 *    - Cari berdasarkan nama persis, lalu nama mirip
 *    - Jika tidak ada, buat jenis donasi baru dengan:
 *      * Auto-detect apakah donasi barang (keywords: barang, sembako, makanan, dll)
 *      * Auto-detect butuh keterangan tambahan (keywords: infaq terikat, dskl, program, dll)
 *      * Setting default: aktif=true, non-halal=false
 *
 * 3. AUTO-CREATE METODE PEMBAYARAN:
 *    - Cari berdasarkan nama persis, lalu nama mirip
 *    - Normalisasi nama (tf bsi → Transfer BSI, tunai → Tunai, dll)
 *    - Jika tidak ada, buat metode pembayaran baru dengan nama yang sudah dinormalisasi
 *
 * 4. AUTO-CREATE FUNDRAISER:
 *    - Cari berdasarkan nama fundraiser, nomor identitas, atau nomor HP
 *    - Jika tidak ada, buat fundraiser baru dengan:
 *      * Nama yang sudah dikapitalisasi
 *      * Status aktif=true
 *      * Alamat default="Auto-created from import"
 *
 * MAPPING KOLOM IMPORT:
 * - No Transaksi → nomor_transaksi_unik (optional, auto-generate jika kosong)
 * - Tanggal → tanggal_donasi (parsing fleksibel: 1/3/2025, 01-03-2025, dll)
 * - Nama Donatur → resolve donatur berdasarkan nama persis yang sudah ada
 * - No HP → digunakan untuk membantu resolve donatur
 * - Alamat → digunakan untuk membantu resolve donatur
 * - Jenis Donasi → resolve/create jenis donasi
 * - Keterangan → catatan_donatur (atau keterangan_infak_khusus untuk infaq terikat)
 * - Catatan → catatan_donatur (digabung dengan keterangan)
 * - Via → resolve/create metode pembayaran
 * - Jumlah → jumlah (parsing: Rp100,000 → 100000)
 * - Deskripsi Barang → deskripsi_barang
 * - Perkiraan Nilai Barang → perkiraan_nilai_barang
 * - Fundraiser → resolve/create fundraiser
 *
 * ANTI-DUPLIKAT:
 */

class DonasiImporter extends Importer
{
    protected static ?string $model = Donasi::class;

    // Kolom yang digunakan untuk processing tapi tidak disimpan ke database
    protected array $processingOnlyColumns = [
        'donatur',  // Akan otomatis resolve ke donatur_id
        'nomor_hp', 
        'alamat',
        'jenis_donasi',  // Akan otomatis resolve ke jenis_donasi_id
        'keterangan',
        'catatan',
        'via',
        'fundraiser'
    ];

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nomor_transaksi_unik')
                ->label('No Transaksi')
                ->rules(['nullable'])
                ->example('03700'),

            ImportColumn::make('tanggal_donasi')
                ->requiredMapping()
                ->rules(['required'])
                ->label('Tanggal')
                ->example('1/3/2025'),

            ImportColumn::make('donatur_id')
                ->requiredMapping()
                ->relationship('donatur', 'nama')
                ->label('Nama Donatur')
                ->rules(['required'])
                ->example('Suhadi'),
            
            ImportColumn::make('nomor_hp')
                ->label('No HP')
                ->rules(['nullable'])
                ->example('81274755000'),
            
            ImportColumn::make('alamat')
                ->label('Alamat')
                ->rules(['nullable'])
                ->example('RT. 13 Tangkit'),

            ImportColumn::make('jenis_donasi_id')
                ->requiredMapping()
                ->relationship('jenisDonasi', 'nama')
                ->label('Jenis Donasi')
                ->rules(['required'])
                ->example('Infaq Terikat'),
            
            ImportColumn::make('keterangan')
                ->label('Keterangan')
                ->rules(['nullable'])
                ->example('IK ASRAMA YATIM'),

            ImportColumn::make('catatan')
                ->label('Catatan')
                ->rules(['nullable'])
                ->example('anak yatim'),

            ImportColumn::make('via')
                ->label('Via (Metode Pembayaran)')
                ->example('Tunai'),

            ImportColumn::make('jumlah')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->example('100000'),
            
            ImportColumn::make('deskripsi_barang')
                ->label('Deskripsi Barang')
                ->rules(['nullable'])
                ->example('Beras 5kg premium'),
            
            ImportColumn::make('perkiraan_nilai_barang')
                ->label('Perkiraan Nilai Barang')
                ->numeric()
                ->rules(['nullable', 'numeric'])
                ->example('70000'),

            ImportColumn::make('fundraiser')
                ->label('Fundraiser')
                ->example('ZULI'),
        ];
    }

    public function resolveRecord(): ?Donasi
    {
        try {
            // Dengan relationship, Filament akan otomatis handle validasi required
            // Tapi kita tetap bisa tambahkan validasi manual jika diperlukan
            
            // Generate nomor transaksi unik dari data atau generate baru
            $nomorTransaksi = $this->data['nomor_transaksi_unik'] ?? 'TRX' . strtoupper(uniqid());
            
            // DETEKSI DONASI HAMBA ALLAH
            $isHambaAllah = false;
            if (isset($this->data['donatur_id']) && 
                (strtolower(trim($this->data['donatur_id'])) === 'hamba allah' || 
                 stristr($this->data['donatur_id'], 'hamba allah'))) {
                $isHambaAllah = true;
            }
            
            // Cari donasi berdasarkan nomor transaksi atau buat baru
            $donasi = Donasi::firstOrNew([
                'nomor_transaksi_unik' => $nomorTransaksi,
            ], [
                // Default values untuk record baru
                'atas_nama_hamba_allah' => $isHambaAllah,
                'dicatat_oleh_user_id' => Auth::id(),
            ]);

            // Dengan relationship(), donatur_id dan jenis_donasi_id akan otomatis ter-set
            // dari nama yang di-resolve oleh Filament
            // TAPI untuk Hamba Allah, kita perlu handle secara khusus
            if ($isHambaAllah) {
                // Pastikan donatur "Hamba Allah" ada dan set donatur_id
                $hambaAllahDonatur = Donatur::where('nama', 'Hamba Allah')->first();
                if (!$hambaAllahDonatur) {
                    // Auto-create donatur "Hamba Allah" jika belum ada
                    $hambaAllahDonatur = Donatur::create([
                        'nama' => 'Hamba Allah',
                        'nomor_hp' => null,
                        'alamat' => 'Donatur Anonymous',
                        'aktif' => true,
                        'catatan' => 'Donatur khusus untuk donasi atas nama Hamba Allah'
                    ]);
                }
                $donasi->donatur_id = $hambaAllahDonatur->id;
                $donasi->atas_nama_hamba_allah = true;
            }
            
            // Resolve atau create metode pembayaran
            if (isset($this->data['via']) && $this->data['via']) {
                $metodePembayaranId = $this->resolveMetodePembayaran($this->data['via']);
                if ($metodePembayaranId) {
                    $donasi->metode_pembayaran_id = $metodePembayaranId;
                }
            }

            // Resolve atau create fundraiser
            if (isset($this->data['fundraiser']) && $this->data['fundraiser']) {
                $fundraiserId = $this->resolveFundraiser($this->data['fundraiser']);
                if ($fundraiserId) {
                    $donasi->fundraiser_id = $fundraiserId;
                }
            }

            // Set keterangan berdasarkan jenis donasi
            if (isset($this->data['keterangan']) && $this->data['keterangan']) {
                
                // Ambil jenis donasi dari relationship untuk menentukan handling keterangan
                $jenisDonasi = null;
                if ($donasi->jenis_donasi_id) {
                    $jenisDonasi = JenisDonasi::find($donasi->jenis_donasi_id);
                }
                
                // Cek apakah jenis donasi membutuhkan keterangan tambahan
                if ($jenisDonasi && 
                    $jenisDonasi->membutuhkan_keterangan_tambahan && 
                    !$jenisDonasi->mengandung_dana_non_halal && 
                    !$jenisDonasi->apakah_barang) {
                    
                    // Jenis donasi seperti Infaq Terikat, DSKL → gunakan keterangan_infak_khusus
                    $infaqTerikatKategori = $this->resolveInfaqTerikat($this->data['keterangan']);
                    if ($infaqTerikatKategori) {
                        $donasi->keterangan_infak_khusus = $infaqTerikatKategori;
                    } else {
                        // Fallback: simpan keterangan asli jika tidak ditemukan mapping
                        $donasi->keterangan_infak_khusus = $this->data['keterangan'];
                    }
                } else {
                    // Jenis donasi biasa → gunakan catatan_donatur
                    $donasi->catatan_donatur = $this->data['keterangan'];
                }
            }

            // Set catatan donatur (terpisah dari keterangan)
            if (isset($this->data['catatan']) && $this->data['catatan']) {
                // Jika catatan_donatur sudah terisi dari keterangan, gabungkan
                if ($donasi->catatan_donatur) {
                    $donasi->catatan_donatur .= "\n\nCatatan: " . $this->data['catatan'];
                } else {
                    $donasi->catatan_donatur = $this->data['catatan'];
                }
            }

            // Set field untuk donasi barang
            if (isset($this->data['deskripsi_barang']) && $this->data['deskripsi_barang']) {
                $donasi->deskripsi_barang = $this->data['deskripsi_barang'];
            }

            if (isset($this->data['perkiraan_nilai_barang']) && $this->data['perkiraan_nilai_barang']) {
                $donasi->perkiraan_nilai_barang = $this->data['perkiraan_nilai_barang'];
            }

            // Set field untuk donasi uang
            if (isset($this->data['jumlah']) && $this->data['jumlah']) {
                $jumlahParsed = $this->parseJumlahUang($this->data['jumlah']);
                if ($jumlahParsed > 0) {
                    $donasi->jumlah = $jumlahParsed;
                }
            }

            // Set tanggal donasi
            if (isset($this->data['tanggal_donasi']) && $this->data['tanggal_donasi']) {
                $tanggalParsed = $this->parseTanggal($this->data['tanggal_donasi']);
                if ($tanggalParsed) {
                    $donasi->tanggal_donasi = $tanggalParsed;
                }
            }

            // Bersihkan data yang tidak boleh disimpan langsung ke database
            $this->filterProcessingOnlyColumns();

            return $donasi;
            
        } catch (\Exception $e) {
            Log::error('Error processing donasi import record', [
                'data' => $this->data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'userId' => Auth::id()
            ]);
            
            // Re-throw exception untuk validation errors agar user mendapat feedback yang jelas
            if (str_contains($e->getMessage(), 'field is required') || 
                str_contains($e->getMessage(), 'Failed to resolve')) {
                throw $e;
            }
            
            // Return null untuk error lainnya untuk skip record
            return null;
        }
    }

    /**
     * Filter kolom yang hanya untuk processing, tidak disimpan ke database
     */
    private function filterProcessingOnlyColumns(): void
    {
        foreach ($this->processingOnlyColumns as $column) {
            unset($this->data[$column]);
        }
    }

    /**
     * Resolve donatur berdasarkan nama persis (tanpa create baru)
     */
    private function resolveDonatur(string $nama, ?string $nomorHp = null, ?string $alamat = null): ?int
    {
        try {
            // Cari berdasarkan nama persis terlebih dahulu
            $donatur = Donatur::where('nama', $nama)->first();
            
            if (!$donatur && $nomorHp) {
                // Jika tidak ditemukan, coba berdasarkan nomor HP
                $donatur = Donatur::where('nomor_hp', $nomorHp)->first();
            }
            
            if (!$donatur) {
                // Jika tidak ditemukan, coba cari dengan LIKE (case-insensitive)
                // Hanya jika nama import adalah substring dari nama database
                $donatur = Donatur::whereRaw('LOWER(nama) LIKE ?', ['%' . strtolower($nama) . '%'])->first();
            }
            
            if (!$donatur) {
                // Jika masih tidak ditemukan, log error dan return null
                Log::warning("Donatur tidak ditemukan: {$nama}", [
                    'nama' => $nama,
                    'nomor_hp' => $nomorHp,
                    'alamat' => $alamat
                ]);
                return null;
            }
            
            return $donatur->id;
        } catch (\Exception $e) {
            Log::error("Error resolving donatur: {$nama}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Resolve atau create jenis donasi berdasarkan nama
     */
    private function resolveJenisDonasi(string $namaJenis): ?JenisDonasi
    {
        try {
            // Coba cari berdasarkan nama yang sama persis
            $jenisDonasi = JenisDonasi::where('nama', $namaJenis)->first();
            
            if (!$jenisDonasi) {
                // Coba cari berdasarkan nama yang mirip
                $jenisDonasi = JenisDonasi::where('nama', 'like', '%' . $namaJenis . '%')->first();
            }
            
            if (!$jenisDonasi) {
                // Jika tidak ditemukan, buat jenis donasi baru dengan setting default
                $jenisDonasi = JenisDonasi::create([
                    'nama' => ucwords(strtolower($namaJenis)),
                    'aktif' => true,
                    'apakah_barang' => $this->isJenisDonasiBarang($namaJenis),
                    'membutuhkan_keterangan_tambahan' => $this->isJenisDonasiMembutuhkanKeterangan($namaJenis),
                    'mengandung_dana_non_halal' => false,
                    'keterangan_dana_non_halal' => null,
                    'sumber_dana_penyaluran_id' => null,
                ]);
            }
            
            return $jenisDonasi;
        } catch (\Exception $e) {
            Log::error("Error resolving jenis donasi: {$namaJenis}", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Deteksi apakah jenis donasi adalah donasi barang
     */
    private function isJenisDonasiBarang(string $namaJenis): bool
    {
        $namaLower = strtolower($namaJenis);
        $keywordBarang = [
            'barang', 'sembako', 'makanan', 'minuman', 'pakaian', 
            'alat', 'perlengkapan', 'furniture', 'elektronik',
            'buku', 'tas', 'sepatu', 'mainan', 'obat-obatan'
        ];
        
        foreach ($keywordBarang as $keyword) {
            if (str_contains($namaLower, $keyword)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Deteksi apakah jenis donasi membutuhkan keterangan tambahan
     */
    private function isJenisDonasiMembutuhkanKeterangan(string $namaJenis): bool
    {
        $namaLower = strtolower($namaJenis);
        $keywordKeterangan = [
            'infaq terikat', 'infak terikat', 'dskl', 
            'beasiswa', 'bantuan khusus', 'program',
            'kegiatan', 'acara', 'event', 'terikat'
        ];
        
        foreach ($keywordKeterangan as $keyword) {
            if (str_contains($namaLower, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Resolve atau create fundraiser
     */
    private function resolveFundraiser(string $identifier): ?int
    {
        try {
            // Coba cari berdasarkan nama fundraiser
            $fundraiser = Fundraiser::where('nama_fundraiser', 'like', '%' . $identifier . '%')
                                    ->where('aktif', true)
                                    ->first();
            
            if (!$fundraiser) {
                // Jika tidak ditemukan, coba berdasarkan nomor identitas
                $fundraiser = Fundraiser::where('nomor_identitas', $identifier)
                                       ->where('aktif', true)
                                       ->first();
            }
            
            if (!$fundraiser) {
                // Jika tidak ditemukan, coba berdasarkan nomor HP
                $fundraiser = Fundraiser::where('nomor_hp', $identifier)
                                       ->where('aktif', true)
                                       ->first();
            }
            
            if (!$fundraiser) {
                // Jika masih tidak ditemukan, buat fundraiser baru
                $fundraiser = Fundraiser::create([
                    'nama_fundraiser' => ucwords(strtolower($identifier)),
                    'aktif' => true,
                    'nomor_identitas' => null, // Bisa diisi manual nanti
                    'nomor_hp' => null, // Bisa diisi manual nanti
                    'alamat' => 'Auto-created from import',
                    'user_id' => null, // Tidak terhubung dengan user tertentu
                ]);
            }
            
            return $fundraiser?->id;
        } catch (\Exception $e) {
            Log::error("Error resolving fundraiser: {$identifier}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Resolve atau create metode pembayaran berdasarkan nama
     */
    private function resolveMetodePembayaran(string $namaMetode): ?int
    {
        try {
            // Normalisasi nama metode pembayaran
            $namaMetodeClean = $this->normalizeMetodePembayaran($namaMetode);
            
            // Coba cari berdasarkan nama yang sama persis
            $metodePembayaran = MetodePembayaran::where('nama', $namaMetodeClean)->first();
            
            if (!$metodePembayaran) {
                // Coba cari berdasarkan nama yang mirip
                $metodePembayaran = MetodePembayaran::where('nama', 'like', '%' . $namaMetodeClean . '%')->first();
            }
            
            if (!$metodePembayaran) {
                // Jika tidak ditemukan, buat metode pembayaran baru
                $metodePembayaran = MetodePembayaran::create([
                    'nama' => $namaMetodeClean,
                    'aktif' => true,
                    'instruksi_pembayaran' => 'Auto-created from import: ' . $namaMetode,
                ]);
            }
            
            return $metodePembayaran?->id;
        } catch (\Exception $e) {
            Log::error("Error resolving metode pembayaran: {$namaMetode}", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Normalisasi nama metode pembayaran untuk konsistensi
     */
    private function normalizeMetodePembayaran(string $namaMetode): string
    {
        $nama = trim($namaMetode);
        
        // Mapping untuk standardisasi nama metode pembayaran
        $mappingMetode = [
            // Transfer Bank
            'tf bsi' => 'Transfer BSI',
            'tf bca' => 'Transfer BCA', 
            'tf mandiri' => 'Transfer Mandiri',
            'tf bni' => 'Transfer BNI',
            'tf bri' => 'Transfer BRI',
            'tf btn' => 'Transfer BTN',
            'tf muamalat' => 'Transfer Muamalat',
            'tf syariah' => 'Transfer Bank Syariah',
            
            // E-wallet
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
            'dana' => 'DANA',
            'shopeepay' => 'ShopeePay',
            'linkaja' => 'LinkAja',
            
            // Cash
            'tunai' => 'Tunai',
            'cash' => 'Tunai',
            'kas' => 'Tunai',
            
            // Lainnya
            'qris' => 'QRIS',
            'virtual account' => 'Virtual Account',
            'va' => 'Virtual Account',
        ];
        
        $namaLower = strtolower($nama);
        
        // Cek mapping yang spesifik
        foreach ($mappingMetode as $pattern => $standardName) {
            if (str_contains($namaLower, $pattern)) {
                // Extract nomor rekening/kode jika ada
                if (preg_match('/(\d{4,})/', $nama, $matches)) {
                    return $standardName . ' ' . $matches[1];
                }
                return $standardName;
            }
        }
        
        // Jika tidak ada mapping, kapitalisasi saja
        return ucwords(strtolower($nama));
    }

    private function resolveInfaqTerikat(string $keterangan): ?string
    {
        // Gunakan database query langsung untuk kategori_infaq_terikats
        $infaqTerikat = DB::table('kategori_infaq_terikats')
                          ->where('nama_kategori', 'like', '%' . $keterangan . '%')
                          ->where('aktif', true)
                          ->first();
        
        if (!$infaqTerikat) {
            // Extract bagian setelah kode untuk pencarian
            if (preg_match('/^[A-Z]{1,3}\s+(.+)/', $keterangan, $matches)) {
                $deskripsi = $matches[1];
                $infaqTerikat = DB::table('kategori_infaq_terikats')
                              ->where('nama_kategori', 'like', '%' . $deskripsi . '%')
                              ->where('aktif', true)
                              ->first();
            }
        }
        
        if (!$infaqTerikat) {
            // Pencarian berdasarkan deskripsi
            $infaqTerikat = DB::table('kategori_infaq_terikats')
                          ->where('deskripsi', 'like', '%' . $keterangan . '%')
                          ->where('aktif', true)
                          ->first();
        }
        
        return $infaqTerikat?->nama_kategori;
    }

    /**
     * Parse tanggal dari berbagai format Indonesia
     */
    private function parseTanggal($tanggalInput): ?string
    {
        if (!$tanggalInput) {
            return null;
        }
        
        // Jika sudah berupa Carbon/DateTime object
        if ($tanggalInput instanceof \DateTime || $tanggalInput instanceof \Carbon\Carbon) {
            return $tanggalInput->format('Y-m-d');
        }
        
        $tanggalStr = trim((string) $tanggalInput);
        
        // Handle empty or invalid input
        if (empty($tanggalStr) || $tanggalStr === '0' || $tanggalStr === '0000-00-00') {
            return null;
        }
        
        try {
            // Preprocessing: normalize separators to slash
            $normalized = str_replace(['-', '.'], '/', $tanggalStr);
            
            // Split tanggal berdasarkan separator
            $parts = explode('/', $normalized);
            
            if (count($parts) >= 3) {
                $part1 = (int) $parts[0];
                $part2 = (int) $parts[1]; 
                $part3 = (int) $parts[2];
                
                // Determine which part is year, month, day
                $day = $month = $year = 0;
                
                // If any part is > 31, it's likely the year
                if ($part1 > 31) {
                    // YYYY/MM/DD format
                    $year = $part1;
                    $month = $part2;
                    $day = $part3;
                } elseif ($part3 > 31 || $part3 > 12) {
                    // DD/MM/YYYY or MM/DD/YYYY format
                    $year = $part3;
                    if ($part1 > 12) {
                        // DD/MM/YYYY
                        $day = $part1;
                        $month = $part2;
                    } elseif ($part2 > 12) {
                        // MM/DD/YYYY
                        $month = $part1;
                        $day = $part2;
                    } else {
                        // Ambiguous - assume DD/MM/YYYY (Indonesian format)
                        $day = $part1;
                        $month = $part2;
                    }
                }
                
                // Handle 2-digit years
                if ($year < 100) {
                    if ($year <= 30) {
                        $year += 2000; // 00-30 -> 2000-2030
                    } else {
                        $year += 1900; // 31-99 -> 1931-1999
                    }
                }
                
                // Validate date components
                if ($day >= 1 && $day <= 31 && 
                    $month >= 1 && $month <= 12 && 
                    $year >= 1900 && $year <= 2030) {
                    
                    // Try to create valid date
                    try {
                        $date = Carbon::create($year, $month, $day);
                        return $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Invalid date combination, try swapping day/month
                        if ($day <= 12 && $month <= 31) {
                            try {
                                $date = Carbon::create($year, $day, $month);
                                return $date->format('Y-m-d');
                            } catch (\Exception $e2) {
                                // Still invalid, continue to next method
                            }
                        }
                    }
                }
            }
            
            // Fallback: try common date formats with Carbon parsing
            $commonFormats = [
                'd/m/Y',     // 31/01/2025
                'd/n/Y',     // 31/1/2025 (single digit month)
                'j/m/Y',     // 1/01/2025 (single digit day)
                'j/n/Y',     // 1/1/2025 (both single digits)
                'm/d/Y',     // 01/31/2025
                'n/d/Y',     // 1/31/2025
                'Y-m-d',     // 2025-01-31
                'd-m-Y',     // 31-01-2025
                'd.m.Y',     // 31.01.2025
                'Y/m/d',     // 2025/01/31
            ];
            
            foreach ($commonFormats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $tanggalStr);
                    // Check if the date was parsed correctly and is valid
                    if ($date && $date->format($format) === $tanggalStr && 
                        $date->year >= 1900 && $date->year <= 2030) {
                        return $date->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Last resort: try to manually parse dd/mm/yyyy format
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $tanggalStr, $matches)) {
                $day = (int)$matches[1];
                $month = (int)$matches[2];
                $year = (int)$matches[3];
                
                // Try dd/mm/yyyy first (Indonesian format)
                if ($day <= 31 && $month <= 12 && $year >= 1900 && $year <= 2030) {
                    try {
                        $date = Carbon::create($year, $month, $day);
                        return $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        // If dd/mm/yyyy fails, try mm/dd/yyyy
                        if ($day <= 12 && $month <= 31) {
                            try {
                                $date = Carbon::create($year, $day, $month);
                                return $date->format('Y-m-d');
                            } catch (\Exception $e2) {
                                // Both failed, continue
                            }
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            // Log error for debugging but don't fail the import
            Log::warning("Failed to parse date", [
                'input' => $tanggalInput,
                'normalized' => $tanggalStr ?? 'N/A',
                'error' => $e->getMessage(),
                'userId' => Auth::id()
            ]);
        }
        
        // Return today's date as fallback if all parsing fails
        return Carbon::now()->format('Y-m-d');
    }
    
    /**
     * Parse jumlah uang dari berbagai format Indonesia
     */
    private function parseJumlahUang($jumlahInput): float
    {
        if (!$jumlahInput) {
            return 0;
        }
        
        // Jika sudah berupa number
        if (is_numeric($jumlahInput)) {
            return (float) $jumlahInput;
        }
        
        $jumlahStr = trim((string) $jumlahInput);
        
        // Remove common currency symbols and text
        $cleaned = $jumlahStr;
        $cleaned = str_ireplace(['rp', 'rupiah', 'idr', 'rp.', 'rp '], '', $cleaned);
        
        // Remove spaces
        $cleaned = str_replace(' ', '', $cleaned);
        
        // Handle different decimal separators
        // Format Indonesia: 100.000,50 (titik ribuan, koma desimal)
        // Format International: 100,000.50 (koma ribuan, titik desimal)
        
        // Detect format based on last separator
        $lastComma = strrpos($cleaned, ',');
        $lastDot = strrpos($cleaned, '.');
        
        if ($lastComma !== false && $lastDot !== false) {
            // Both comma and dot present
            if ($lastComma > $lastDot) {
                // Format Indonesia: 100.000,50
                $cleaned = str_replace('.', '', $cleaned); // Remove thousand separators
                $cleaned = str_replace(',', '.', $cleaned); // Convert decimal separator
            } else {
                // Format International: 100,000.50
                $cleaned = str_replace(',', '', $cleaned); // Remove thousand separators
                // Dot is already decimal separator
            }
        } elseif ($lastComma !== false) {
            // Only comma present
            // Could be either thousand separator (100,000) or decimal (100,50)
            $commaCount = substr_count($cleaned, ',');
            if ($commaCount === 1) {
                // Check if it's decimal separator (has 1-2 digits after comma)
                $afterComma = substr($cleaned, $lastComma + 1);
                if (strlen($afterComma) <= 2 && is_numeric($afterComma)) {
                    // Likely decimal separator
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    // Likely thousand separator
                    $cleaned = str_replace(',', '', $cleaned);
                }
            } else {
                // Multiple commas, treat as thousand separators
                $cleaned = str_replace(',', '', $cleaned);
            }
        } elseif ($lastDot !== false) {
            // Only dot present
            // Could be either thousand separator (100.000) or decimal (100.50)
            $dotCount = substr_count($cleaned, '.');
            if ($dotCount === 1) {
                // Check if it's decimal separator (has 1-2 digits after dot)
                $afterDot = substr($cleaned, $lastDot + 1);
                if (strlen($afterDot) <= 2 && is_numeric($afterDot)) {
                    // Likely decimal separator, keep as is
                } else {
                    // Likely thousand separator
                    $cleaned = str_replace('.', '', $cleaned);
                }
            } else {
                // Multiple dots, treat as thousand separators
                $cleaned = str_replace('.', '', $cleaned);
            }
        }
        
        // Remove any remaining non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $cleaned);
        
        // Convert to float
        return is_numeric($cleaned) ? (float) $cleaned : 0;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your donasi import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    /**
     * Filter data untuk hanya kolom yang valid di database
     */
    public function getValidatedData(): array
    {
        // Hanya return data yang sesuai dengan kolom database
        // Kolom seperti nama_donatur, nomor_hp, alamat, dll adalah untuk processing
        // tapi tidak disimpan langsung ke tabel donasis
        $validData = [];
        
        // Kolom yang valid untuk tabel donasis
        $validColumns = [
            'nomor_transaksi_unik',
            'tanggal_donasi', 
            'jumlah',
            'catatan_donatur',
            'deskripsi_barang',
            'perkiraan_nilai_barang',
            'keterangan_infak_khusus',
            'donatur_id',
            'jenis_donasi_id',
            'metode_pembayaran_id',
            'fundraiser_id',
            'atas_nama_hamba_allah',
            'dicatat_oleh_user_id'
        ];
        
        foreach ($validColumns as $column) {
            if (isset($this->data[$column])) {
                $validData[$column] = $this->data[$column];
            }
        }
        
        return $validData;
    }
}
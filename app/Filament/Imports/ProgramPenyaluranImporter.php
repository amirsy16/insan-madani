<?php

namespace App\Filament\Imports;

use App\Models\ProgramPenyaluran;
use App\Models\BidangProgram;
use App\Models\SumberDanaPenyaluran;
use App\Models\JenisDonasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Carbon\Carbon;

/**
 * PROGRAM PENYALURAN IMPORTER
 * ===========================
 * 
 * Import data penyaluran langsung dengan auto-create master data:
 * 
 * 1. AUTO-CREATE MASTER DATA:
 *    - Sumber Dana: "Dana Umum" (default)
 *    - Bidang Program: "Operasional Lembaga"
 *    - Jenis Donasi: "Penyaluran Langsung"
 * 
 * 2. MAPPING KOLOM CSV:
 *    - Tanggal → tanggal_penyaluran
 *    - Keterangan → keterangan + nama_program
 *    - Tujuan → lokasi_penyaluran
 *    - Kategori → (diabaikan, semua masuk "Operasional Lembaga")
 *    - Jumlah → jumlah_dana
 * 
 * 3. AUTO-GENERATE:
 *    - kode_program_penyaluran
 *    - dicatat_oleh_id (user yang sedang login)
 */

class ProgramPenyaluranImporter extends Importer
{
    protected static ?string $model = ProgramPenyaluran::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('tanggal_penyaluran')
                ->requiredMapping()
                ->rules(['required'])
                ->label('Tanggal Penyaluran')
                ->example('07/01/2025'),

            ImportColumn::make('nama_program')
                ->requiredMapping()
                ->rules(['required'])
                ->label('Nama Program')
                ->example('Penyaluran Langsung'),

            ImportColumn::make('jumlah_dana')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric', 'min:0'])
                ->label('Jumlah Dana')
                ->example('650000'),

            ImportColumn::make('sumber_dana_penyaluran_id')
                ->relationship('sumberDanaPenyaluran', 'nama_sumber_dana')
                ->label('Sumber Dana')
                ->rules(['nullable'])
                ->example('Dana Umum'),

            ImportColumn::make('jenis_donasi_id')
                ->relationship('jenisDonasi', 'nama')
                ->label('Jenis Donasi')
                ->rules(['nullable'])
                ->example('Penyaluran Langsung'),

            ImportColumn::make('bidang_program_id')
                ->relationship('bidangProgram', 'nama_bidang')
                ->label('Bidang Program')
                ->rules(['nullable'])
                ->example('Operasional Lembaga'),

            ImportColumn::make('asnaf_id')
                ->relationship('asnaf', 'nama_asnaf')
                ->label('Asnaf')
                ->rules(['nullable'])
                ->example('Fakir'),

            ImportColumn::make('penerima_manfaat_individu')
                ->label('Penerima Manfaat Individu')
                ->rules(['nullable'])
                ->example('Ahmad Santoso'),

            ImportColumn::make('penerima_manfaat_lembaga')
                ->label('Penerima Manfaat Lembaga')
                ->rules(['nullable'])
                ->example('Yayasan Harapan'),

            ImportColumn::make('jumlah_penerima_manfaat')
                ->numeric()
                ->label('Jumlah Penerima Manfaat')
                ->rules(['nullable', 'integer', 'min:1'])
                ->example('5'),

            ImportColumn::make('lokasi_penyaluran')
                ->label('Lokasi Penyaluran')
                ->rules(['nullable'])
                ->example('Kelurahan Tangkit'),

            ImportColumn::make('keterangan')
                ->label('Keterangan')
                ->rules(['nullable'])
                ->example('Bantuan sembako bulanan'),

            ImportColumn::make('bukti_penyaluran')
                ->label('Bukti Penyaluran')
                ->rules(['nullable'])
                ->example('foto_penyaluran.jpg'),
        ];
    }

    public function resolveRecord(): ?ProgramPenyaluran
    {
        try {
            // Validasi data minimal
            if (empty($this->data['tanggal_penyaluran'])) {
                Log::warning("Missing tanggal_penyaluran", ['data' => $this->data]);
                throw new \Exception("Tanggal penyaluran harus diisi");
            }
            
            if (empty($this->data['nama_program'])) {
                Log::warning("Missing nama_program", ['data' => $this->data]);
                throw new \Exception("Nama program harus diisi");
            }
            
            if (empty($this->data['jumlah_dana'])) {
                Log::warning("Missing jumlah_dana", ['data' => $this->data]);
                throw new \Exception("Jumlah dana harus diisi");
            }
            
            // Parse tanggal dengan format fleksibel
            $tanggal = $this->parseTanggal($this->data['tanggal_penyaluran']);
            
            // Parse jumlah dana
            $jumlahDana = $this->parseJumlah($this->data['jumlah_dana']);
            
            if ($jumlahDana <= 0) {
                Log::warning("Invalid jumlah_dana: " . $this->data['jumlah_dana'], ['data' => $this->data]);
                throw new \Exception("Jumlah dana harus lebih dari 0");
            }
            
            // Generate kode program penyaluran yang unik
            $kodeProgramPenyaluran = $this->generateKodeProgram($tanggal);
            
            // Buat record baru dengan data minimal
            $record = new ProgramPenyaluran();
            $record->kode_program_penyaluran = $kodeProgramPenyaluran;
            $record->nama_program = $this->data['nama_program'];
            $record->tanggal_penyaluran = $tanggal->toDateString(); // Convert to string to avoid format issues
            $record->jumlah_dana = $jumlahDana;
            $record->dicatat_oleh_id = Auth::id() ?? 1;
            
            // Optional fields - hanya jika ada di data dan tidak kosong
            if (isset($this->data['penerima_manfaat_individu']) && !empty($this->data['penerima_manfaat_individu'])) {
                $record->penerima_manfaat_individu = $this->data['penerima_manfaat_individu'];
            }
            
            if (isset($this->data['penerima_manfaat_lembaga']) && !empty($this->data['penerima_manfaat_lembaga'])) {
                $record->penerima_manfaat_lembaga = $this->data['penerima_manfaat_lembaga'];
            }
            
            if (isset($this->data['lokasi_penyaluran']) && !empty($this->data['lokasi_penyaluran'])) {
                $record->lokasi_penyaluran = $this->data['lokasi_penyaluran'];
            }
            
            if (isset($this->data['keterangan']) && !empty($this->data['keterangan'])) {
                $record->keterangan = $this->data['keterangan'];
            }
            
            if (isset($this->data['bukti_penyaluran']) && !empty($this->data['bukti_penyaluran'])) {
                $record->bukti_penyaluran = $this->data['bukti_penyaluran'];
            }
            
            // Handle jumlah_penerima_manfaat
            $record->jumlah_penerima_manfaat = 1; // Default
            if (isset($this->data['jumlah_penerima_manfaat']) && is_numeric($this->data['jumlah_penerima_manfaat'])) {
                $record->jumlah_penerima_manfaat = (int)$this->data['jumlah_penerima_manfaat'];
            }
            
            return $record;
            
        } catch (\Exception $e) {
            Log::error('Error processing program penyaluran import record', [
                'data' => $this->data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'userId' => Auth::id()
            ]);
            
            // Re-throw exception untuk validation errors agar user mendapat feedback yang jelas
            throw $e;
        }
    }

    /**
     * Parse tanggal dengan pendekatan yang sangat konservatif
     */
    private function parseTanggal($tanggal): Carbon
    {
        // Guard clause untuk input kosong
        if (!$tanggal || trim((string)$tanggal) === '') {
            Log::warning("Empty date input, using current date");
            return Carbon::now();
        }
        
        // Jika sudah berupa Carbon/DateTime object
        if ($tanggal instanceof \DateTime || $tanggal instanceof \Carbon\Carbon) {
            return $tanggal instanceof Carbon ? $tanggal : Carbon::instance($tanggal);
        }
        
        $tanggalStr = trim((string) $tanggal);
        
        // Handle obvious invalid inputs
        if (in_array($tanggalStr, ['0', '0000-00-00', '', 'null', 'NULL'])) {
            Log::warning("Invalid date input detected", ['input' => $tanggalStr]);
            return Carbon::now();
        }
        
        try {
            // Try the most reliable formats first
            $reliableFormats = [
                'Y-m-d',     // 2025-01-07 - ISO format (most reliable)
                'Y-m-j',     // 2025-1-7 
                'd/m/Y',     // 07/01/2025 - Indonesian format
                'j/n/Y',     // 7/1/2025 (most flexible)
            ];
            
            foreach ($reliableFormats as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, $tanggalStr);
                    if ($parsed) {
                        // Validate the parsed date makes sense
                        if ($parsed->year >= 2020 && $parsed->year <= 2030) {
                            Log::debug("Successfully parsed date", [
                                'input' => $tanggalStr,
                                'format' => $format,
                                'result' => $parsed->toDateString()
                            ]);
                            return $parsed;
                        }
                    }
                } catch (\Throwable $e) {
                    // Continue to next format
                    continue;
                }
            }
            
            // If all specific formats fail, try Carbon's automatic parsing
            // but wrap it in additional safety
            try {
                $parsed = Carbon::parse($tanggalStr);
                if ($parsed && $parsed->year >= 2020 && $parsed->year <= 2030) {
                    Log::info("Parsed date using Carbon::parse", [
                        'input' => $tanggalStr,
                        'result' => $parsed->toDateString()
                    ]);
                    return $parsed;
                }
            } catch (\Throwable $e) {
                // Continue to fallback
            }
            
        } catch (\Throwable $e) {
            Log::warning("Exception during date parsing", [
                'input' => $tanggalStr,
                'error' => $e->getMessage()
            ]);
        }
        
        // Absolute fallback
        Log::error("All date parsing methods failed, using current date", [
            'input' => $tanggal,
            'normalized' => $tanggalStr,
            'fallback' => Carbon::now()->toDateString()
        ]);
        
        return Carbon::now();
    }

    /**
     * Parse jumlah dana - menggunakan pattern dari DonasiImporter
     */
    private function parseJumlah($jumlahInput): float
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
        $result = is_numeric($cleaned) ? (float) $cleaned : 0;
        return $result >= 0 ? $result : 0.0;
    }

    /**
     * Generate kode program penyaluran yang unik dengan timestamp
     */
    private function generateKodeProgram(Carbon $tanggal): string
    {
        try {
            // Gunakan pendekatan timestamp untuk memastikan keunikan
            $prefix = 'PP'; // Program Penyaluran
            $yearMonth = $tanggal->format('ym'); // 2506 untuk Jun 2025
            
            // Gunakan microsecond timestamp untuk uniqueness
            $microtime = (int)(microtime(true) * 1000000); // Microseconds
            $uniqueId = substr($microtime, -6); // Last 6 digits
            
            $kode = $prefix . $yearMonth . $uniqueId;
            
            // Double check uniqueness (max 10 attempts)
            $attempts = 0;
            $originalKode = $kode;
            
            while (ProgramPenyaluran::where('kode_program_penyaluran', $kode)->exists() && $attempts < 10) {
                $attempts++;
                $randomSuffix = mt_rand(100, 999);
                $kode = $originalKode . $randomSuffix;
                
                Log::warning("Kode program collision, trying new one", [
                    'original' => $originalKode,
                    'new' => $kode,
                    'attempt' => $attempts
                ]);
            }
            
            if ($attempts >= 10) {
                // Emergency fallback
                $kode = $prefix . date('YmdHis') . mt_rand(1000, 9999);
                Log::error("Used emergency fallback for kode generation", ['kode' => $kode]);
            }
            
            Log::debug("Generated kode program", [
                'kode' => $kode,
                'tanggal' => $tanggal->toDateString(),
                'attempts' => $attempts
            ]);
            
            return $kode;
            
        } catch (\Exception $e) {
            Log::error("Error generating kode program", [
                'error' => $e->getMessage(),
                'tanggal' => $tanggal->toDateString() ?? 'N/A'
            ]);
            
            // Emergency fallback
            return 'PP' . date('YmdHis') . mt_rand(1000, 9999);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import program penyaluran Anda telah selesai dan ' . number_format($import->successful_rows) . ' ' . str('baris')->plural($import->successful_rows) . ' berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diimpor. Silakan unduh file CSV kegagalan untuk melihat detail error.';
        }

        return $body;
    }
}

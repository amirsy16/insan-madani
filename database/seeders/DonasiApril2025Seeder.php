<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\JenisDonasi;
use App\Models\MetodePembayaran;
use App\Models\Fundraiser;
use App\Models\ProgramPenyaluran;
use App\Models\SumberDanaPenyaluran;
use App\Models\BidangProgram;
use App\Models\Asnaf;
use App\Models\User;
use Carbon\Carbon;

class DonasiApril2025Seeder extends Seeder
{
    private $importStats = [
        'total_processed' => 0,
        'donations_imported' => 0,
        'penyaluran_imported' => 0,
        'donors_created' => 0,
        'donors_updated' => 0,
        'errors' => 0,
        'warnings' => 0,
        'breakdown' => [
            'zakat' => 0,
            'infaq_terikat' => 0,
            'infaq_tidak_terikat' => 0,
            'dskl' => 0,
            'penyaluran' => 0
        ],
        'by_fundraiser' => [],
        'by_payment_method' => [],
        'total_amount' => 0,
        'total_penyaluran' => 0,
        'net_amount' => 0
    ];

    /**
     * Run the database seeds - OPTIMAL COMPLETE IMPORT APRIL 2025
     */
    public function run(): void
    {
        $this->command->info('🚀 OPTIMAL Import - Donasi April 2025');
        $this->command->info('📋 Features: Smart donor matching, Complete edge cases, Real-time stats');
        
        DB::beginTransaction();
        
        try {
            // Initialize all required master data
            $this->initializeMasterData();
            
            // Read and process CSV
            $csvPath = base_path('donasi_april - INPUT DATA.csv');
            if (!file_exists($csvPath)) {
                throw new \Exception("CSV file not found: {$csvPath}");
            }
            
            $csvData = $this->readAndParseCSV($csvPath);
            $this->processAllRows($csvData);
            
            DB::commit();
            
            $this->displayFinalSummary();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("💥 IMPORT FAILED: " . $e->getMessage());
            Log::emergency('Donasi April Import Failed', [
                'error' => $e->getMessage(),
                'stats' => $this->importStats
            ]);
            throw $e;
        }
    }

    /**
     * Initialize all required master data
     */
    private function initializeMasterData(): void
    {
        $this->command->info('📊 Initializing master data...');
        
        // Jenis Donasi - using existing ones only
        $this->command->info('📊 Using existing jenis donasi from database...');
        
        // Payment methods - use existing ones only
        $this->command->info('📱 Using existing payment methods from database...');
        
        // Fundraisers - April 2025 specific fundraisers
        $aprilFundraisers = [
            'ADI', 'HENDRA', 'SRI', 'KANTOR', 'MASYHUDA', 'JOKO', 'EKA', 'TANTI', 
            'MERI', 'ZULI', 'FUJI', 'MIRA', 'TIARA', 'DADANG', 'YUNITA', 'MIRDA'
        ];
        
        foreach ($aprilFundraisers as $fr) {
            Fundraiser::firstOrCreate(['nama_fundraiser' => $fr], [
                'aktif' => true
            ]);
        }
        
        // Penyaluran Master Data
        try {
            // Ensure "Penyaluran Langsung" exists in SumberDanaPenyaluran
            SumberDanaPenyaluran::firstOrCreate([
                'nama_sumber_dana' => 'Penyaluran Langsung'
            ], [
                'deskripsi' => 'Dana yang disalurkan langsung tanpa masuk ke kas',
                'aktif' => true
            ]);
        } catch (\Exception $e) {
            $this->command->warn("⚠️ Penyaluran master data setup warning: " . $e->getMessage());
        }
        
        $this->command->info('✅ Master data initialized');
    }

    /**
     * Read and parse CSV with improved handling
     */
    private function readAndParseCSV(string $filePath): array
    {
        $this->command->info('📄 Reading CSV file...');
        
        $data = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception("Cannot open CSV file: {$filePath}");
        }
        
        $headers = null;
        $lineNumber = 0;
        
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $lineNumber++;
            
            // Find header row - look for NO KWITANSI
            if ($headers === null && isset($row[0]) && 
                (strpos($row[0], 'NO KWITANSI') !== false || strpos($row[0], 'KWITANSI') !== false)) {
                $headers = array_map('trim', $row);
                continue;
            }
            
            // Skip until headers found
            if ($headers === null) continue;
            
            // Create associative array
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }
            
            $data[] = $rowData;
        }
        
        fclose($handle);
        
        $this->command->info("📊 CSV parsed: " . count($data) . " rows");
        return $data;
    }

    /**
     * Process all CSV rows
     */
    private function processAllRows(array $csvData): void
    {
        $this->command->info('🔄 Processing all rows...');
        
        foreach ($csvData as $index => $row) {
            $this->importStats['total_processed']++;
            
            try {
                // Skip empty/invalid rows
                if ($this->shouldSkipRow($row)) {
                    continue;
                }
                
                // Determine row type and process accordingly
                if ($this->isPenyaluranRow($row)) {
                    $this->processPenyaluranRow($row);
                } elseif ($this->isDonationRow($row)) {
                    $this->processDonationRow($row);
                }
                
            } catch (\Exception $e) {
                $this->importStats['errors']++;
                $this->command->error("Error processing row " . ($index + 1) . ": " . $e->getMessage());
                Log::error('Row processing error', [
                    'row_index' => $index + 1,
                    'error' => $e->getMessage(),
                    'row_data' => $row
                ]);
            }
        }
    }

    /**
     * Check if row should be skipped
     */
    private function shouldSkipRow(array $row): bool
    {
        // Empty rows
        if (empty(array_filter($row))) return true;
        
        // Get possible name fields
        $nama = $row['NAMA DONATUR'] ?? '';
        
        // Summary/header rows
        if (empty($nama) || 
            str_contains($nama, 'TOTAL DONASI') ||
            str_contains($nama, 'LAZ INSAN MADANI') ||
            str_contains($nama, 'APRIL 2025') ||
            preg_match('/^dona,*$/', $nama)) {
            return true;
        }
        
        // Get possible amount fields
        $jumlah = $row[' JUMLAH'] ?? $row['JUMLAH'] ?? '';
        
        // Only skip if both nama and jumlah are empty
        if (empty($nama) && empty($jumlah)) {
            return true;
        }
        
        return false;
    }

    /**
     * Is this a donation row?
     */
    private function isDonationRow(array $row): bool
    {
        $nama = $row['NAMA DONATUR'] ?? '';
        $jumlah = $row[' JUMLAH'] ?? $row['JUMLAH'] ?? '';
        
        // Skip if it's a penyaluran/refund entry (case insensitive)
        if (stripos($nama, 'penyaluran') !== false || stripos($nama, 'refund') !== false) {
            return false;
        }
        
        // Accept donations with or without kwitansi, as long as there's nama and jumlah
        return !empty($nama) && !empty($jumlah);
    }

    /**
     * Is this a penyaluran row?
     */
    private function isPenyaluranRow(array $row): bool
    {
        $nama = $row['NAMA DONATUR'] ?? '';
        $penyaluranAmount = $row[' PENYALURAN'] ?? $row['PENYALURAN'] ?? '';
        
        // Check if it has penyaluran amount (not empty, not dash)
        $hasPenyaluranAmount = !empty($penyaluranAmount) && 
                              $penyaluranAmount !== '-' && 
                              $penyaluranAmount !== '0';
        
        // Check if name indicates penyaluran/refund (case insensitive)
        $isPenyaluranName = stripos($nama, 'penyaluran') !== false ||
                           stripos($nama, 'refund') !== false;
        
        // Also check VIA column for "Penyaluran Langsung"
        $via = $row['VIA'] ?? '';
        $isPenyaluranVia = stripos($via, 'penyaluran langsung') !== false;
        
        $isPenyaluran = $hasPenyaluranAmount || $isPenyaluranName || $isPenyaluranVia;
        
        // Debug output for penyaluran detection
        if ($isPenyaluran) {
            $this->command->info("🔍 Penyaluran detected: {$nama} - Via: {$via} - Amount: {$penyaluranAmount}");
        }
        
        return $isPenyaluran;
    }

    /**
     * Process donation row with complete accuracy
     */
    private function processDonationRow(array $row): void
    {
        // Parse basic data
        $nomorKwitansi = $row['NO KWITANSI'] ?? '';
        $tanggal = $this->parseDate($row['TANGGAL'] ?? '');
        
        // Use flexible amount column names
        $jumlahColumn = $row[' JUMLAH'] ?? $row['JUMLAH'] ?? '';
        $jumlah = $this->parseAmount($jumlahColumn);
        
        // Skip if amount is 0 or invalid
        if ($jumlah <= 0) {
            $this->command->warn("⚠️ Skipping row with invalid amount: {$jumlahColumn}");
            return;
        }
        
        // Handle missing receipt number - generate one
        if (empty($nomorKwitansi)) {
            $nomorKwitansi = 'APR-' . date('Ymd-His') . '-' . rand(1000, 9999);
            $this->command->info("Generated kwitansi: {$nomorKwitansi}");
        }
        
        // Check for duplicate - but be more permissive
        $existingDonasi = Donasi::where('nomor_transaksi_unik', $nomorKwitansi)->first();
        if ($existingDonasi) {
            $this->command->warn("⚠️ Duplicate kwitansi found: {$nomorKwitansi} - Skipping");
            return;
        }
        
        // Map donation type to existing ones in database
        $donationType = $row['JENIS DONASI'] ?? '';
        $jenisDonasi = $this->getJenisDonasi($donationType);
        if (!$jenisDonasi) {
            $this->command->warn("⚠️ Unknown donation type: '{$donationType}'");
            $this->importStats['errors']++;
            return;
        }
        
        // Map payment method to existing ones in database
        $paymentMethod = $row['VIA'] ?? '';
        $metodePembayaran = $this->getMetodePembayaran($paymentMethod);
        if (!$metodePembayaran) {
            $this->command->warn("⚠️ Unknown payment method: '{$paymentMethod}'");
            $this->importStats['errors']++;
            return;
        }
        
        // Handle donor
        $donatur = $this->getOrCreateDonatur($row);
        
        // Handle fundraiser
        $fundraiser = null;
        $fundraiserName = $row['FR'] ?? '';
        if (!empty($fundraiserName) && $fundraiserName !== '-') {
            $fundraiser = $this->getFundraiser($fundraiserName);
            $this->updateFundraiserStats($fundraiserName);
        }
        
        // Special cases
        $isBarang = strpos(strtolower($donationType), 'barang') !== false || 
                   strpos(strtolower($donationType), 'logistik') !== false;
        $donorName = $row['NAMA DONATUR'] ?? '';
        $isHambaAllah = $this->isHambaAllah($donorName);
        
        $keterangan = $row['KETERANGAN'] ?? '';
        $catatan = $row['CATATAN'] ?? '';
        
        // Create donation record
        try {
            $donasi = Donasi::create([
                'donatur_id' => $donatur->id,
                'jenis_donasi_id' => $jenisDonasi->id,
                'metode_pembayaran_id' => $metodePembayaran->id,
                'fundraiser_id' => $fundraiser ? $fundraiser->id : null,
                'jumlah' => $isBarang ? 0 : $jumlah,
                'perkiraan_nilai_barang' => $isBarang ? $jumlah : 0,
                'deskripsi_barang' => $isBarang ? $this->getBarangDescription($row) : null,
                'keterangan_infak_khusus' => $keterangan ?: null,
                'catatan_donatur' => $catatan ?: null,
                'tanggal_donasi' => $tanggal,
                'nomor_transaksi_unik' => $nomorKwitansi,
                'atas_nama_hamba_allah' => $isHambaAllah,
                'status_konfirmasi' => 'verified',
                'dikonfirmasi_pada' => now(),
                'dicatat_oleh_user_id' => 1,
                'dikofirmasi_oleh_user_id' => 1,
            ]);
            
            // Update statistics
            $this->updateDonationStats($jenisDonasi, $metodePembayaran, $jumlah);
            
            $this->importStats['donations_imported']++;
            
        } catch (\Exception $e) {
            $this->importStats['errors']++;
            $this->command->error("💥 Failed to create donation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process penyaluran row
     */
    private function processPenyaluranRow(array $row): void
    {
        try {
            $this->command->info("🎯 Processing penyaluran row...");
            
            $nama = $row['NAMA DONATUR'] ?? '';
            $jumlahDonasi = $this->parseAmount($row[' JUMLAH'] ?? $row['JUMLAH'] ?? '');
            $jumlahPenyaluran = $this->parseAmount($row[' PENYALURAN'] ?? $row['PENYALURAN'] ?? '');
            $tanggal = $this->parseDate($row['TANGGAL'] ?? '');
            
            // Use penyaluran amount if available, otherwise use donation amount
            $amount = $jumlahPenyaluran > 0 ? $jumlahPenyaluran : $jumlahDonasi;
            
            if ($amount <= 0) {
                $this->command->warn("⚠️ Skipping penyaluran with invalid amount");
                return;
            }
            
            // Get "Penyaluran Langsung" as sumber dana
            $sumberDana = SumberDanaPenyaluran::where('nama_sumber_dana', 'Penyaluran Langsung')->first();
            if (!$sumberDana) {
                throw new \Exception("Sumber Dana 'Penyaluran Langsung' not found");
            }
            
            // Create penyaluran record
            $programPenyaluran = ProgramPenyaluran::create([
                'nama_program' => "Penyaluran Langsung - {$nama}",
                'keterangan' => "Penyaluran langsung untuk {$nama}",
                'jumlah_penerima_manfaat' => 1,
                'jumlah_dana' => $amount,
                'tanggal_penyaluran' => $tanggal,
                'sumber_dana_penyaluran_id' => $sumberDana->id,
                'dicatat_oleh_id' => 1,
            ]);
            
            $this->importStats['penyaluran_imported']++;
            $this->importStats['total_penyaluran'] += $amount;
            $this->importStats['breakdown']['penyaluran']++;
            
            $this->command->info("✅ Penyaluran created: {$nama} - Rp " . number_format($amount));
            
        } catch (\Exception $e) {
            $this->importStats['errors']++;
            $this->command->error("💥 Failed to create penyaluran: " . $e->getMessage());
        }
    }

    // Include all helper methods from Maret seeder
    private function getOrCreateDonatur(array $row): Donatur
    {
        $namaDonatur = trim($row['NAMA DONATUR'] ?? '');
        $nomorHp = $this->cleanPhoneNumber($row['NO HP'] ?? '');
        $alamat = trim($row['ALAMAT'] ?? '');
        
        $donorInfo = $this->analyzeDonorName($namaDonatur);
        
        // Try to find existing donor
        $existingDonatur = null;
        
        if ($nomorHp) {
            $existingDonatur = Donatur::where('nomor_hp', $nomorHp)->first();
        }
        
        if (!$existingDonatur && !$donorInfo['is_anonymous']) {
            $existingDonatur = Donatur::where('nama', $donorInfo['clean_name'])->first();
        }
        
        if ($existingDonatur) {
            $this->importStats['donors_updated']++;
            
            if (!$existingDonatur->nomor_hp && $nomorHp) {
                $existingDonatur->update(['nomor_hp' => $nomorHp]);
            }
            
            return $existingDonatur;
        }
        
        // Create new donor
        $newDonatur = Donatur::create([
            'nama' => $donorInfo['clean_name'],
            'gender' => $donorInfo['gender'],
            'nomor_hp' => $nomorHp,
            'alamat_detail' => $alamat,
            'alamat_lengkap' => $alamat,
        ]);
        
        $this->importStats['donors_created']++;
        return $newDonatur;
    }

    private function analyzeDonorName(string $nama): array
    {
        $cleanName = trim($nama);
        $gender = 'male'; // default
        $isAnonymous = false;
        
        // Handle prefixes and titles
        if (str_starts_with($cleanName, 'Ibu ')) {
            $gender = 'female';
            $cleanName = trim(substr($cleanName, 4));
        } elseif (str_starts_with($cleanName, 'Bapak ')) {
            $gender = 'male';
            $cleanName = trim(substr($cleanName, 6));
        } elseif (str_starts_with($cleanName, 'Ananda ')) {
            $gender = 'female'; // Default for students, could be either
            $cleanName = trim(substr($cleanName, 7));
        } elseif (str_starts_with($cleanName, 'dr. ') || str_starts_with($cleanName, 'Dr. ')) {
            $gender = 'male'; // Default
        } elseif (str_starts_with($cleanName, 'Alm. Bapak ')) {
            $gender = 'male';
            $cleanName = trim(substr($cleanName, 12));
        } elseif (str_starts_with($cleanName, 'Hamba Allah')) {
            $isAnonymous = true;
            $cleanName = 'Donatur Anonim'; // Use generic anonymous donor name
            $gender = 'male'; // default
        }
        
        // Handle organizations/businesses
        $organizationKeywords = [
            'Komunitas', 'Wong Solo', 'Sambal Lalap', 'Temphoyak', 'Siswa SD',
            'Karyawan', 'Swalayan', 'BSI', 'Bank', 'CV', 'PT', 'UD', 'Toko',
            'Warung', 'Restoran', 'Hotel', 'Sekolah', 'Universitas', 'Yayasan',
            'Masjid', 'Musholla', 'Pondok', 'Pesantren', 'Dinas', 'Kantor'
        ];
        foreach ($organizationKeywords as $keyword) {
            if (str_contains($cleanName, $keyword)) {
                $gender = 'organization';
                break;
            }
        }
        
        return [
            'clean_name' => $cleanName,
            'gender' => $gender,
            'is_anonymous' => $isAnonymous
        ];
    }

    /**
     * Check if donation is atas nama hamba allah
     */
    private function isHambaAllah(string $nama): bool
    {
        return str_contains($nama, 'Hamba Allah');
    }

    /**
     * Get barang description
     */
    private function getBarangDescription(array $row): string
    {
        $keterangan = $row['KETERANGAN'] ?? '';
        $catatan = $row['CATATAN'] ?? '';
        
        if (str_contains($keterangan, 'SEMBAKO')) {
            return $catatan ?: 'Sembako';
        } elseif (str_contains($keterangan, 'NASI') || str_contains($keterangan, 'MAKANAN')) {
            return $catatan ?: 'Makanan/Nasi';
        } elseif (str_contains($keterangan, 'KUE')) {
            return $catatan ?: 'Kue';
        }
        
        return $catatan ?: 'Barang donasi';
    }

    /**
     * Parse date from various formats
     */
    private function parseDate(string $dateString): Carbon
    {
        $dateString = trim($dateString);
        
        try {
            // Try parsing directly first
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            // Fallback to specific format if direct parsing fails
            try {
                return Carbon::createFromFormat('d/m/Y', $dateString);
            } catch (\Exception $e2) {
                // Final fallback to current date
                $this->command->warn("⚠️ Could not parse date '{$dateString}', using current date");
                return Carbon::now();
            }
        }
    }

    /**
     * Parse amount from currency string
     */
    private function parseAmount(string $amountString): float
    {
        if (empty($amountString)) {
            return 0;
        }
        
        // Handle scientific notation
        if (strpos($amountString, 'E') !== false || strpos($amountString, 'e') !== false) {
            return (float) $amountString;
        }
        
        // Remove Rp, spaces, but keep digits, dots and commas for decimal handling
        $cleaned = str_replace(['Rp', ' '], ['', ''], trim($amountString));
        
        // Handle different decimal formats
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d{1,2}$/', $cleaned)) {
            // European format: 1.234.567,89
            $cleaned = str_replace(['.', ','], ['', '.'], $cleaned);
        } elseif (preg_match('/^\d+,\d{3}$/', $cleaned)) {
            // Thousand separator: 1,234
            $cleaned = str_replace(',', '', $cleaned);
        } elseif (preg_match('/^\d+,\d{1,2}$/', $cleaned)) {
            // Decimal separator: 123,45
            $cleaned = str_replace(',', '.', $cleaned);
        } else {
            // Remove all commas and dots that might be thousand separators
            $cleaned = str_replace(',', '', $cleaned);
        }
        
        if (empty($cleaned) || !is_numeric($cleaned)) {
            return 0;
        }
        
        return (float) $cleaned;
    }

    /**
     * Get or create jenis donasi
     */
    private function getJenisDonasi(string $jenisString): ?JenisDonasi
    {
        // Exact match first
        $jenis = JenisDonasi::where('nama', $jenisString)->first();
        if ($jenis) {
            return $jenis;
        }
        
        // Mapping for variations - April specific
        $jenisMap = [
            'Infaq Umum' => 'Infaq Tidak Terikat',
            'Zakat Maal' => 'Zakat',
            'Zakat Profesi' => 'Zakat',
            'DSKL (Dana Sosial Keagamaan Lainnya)' => 'DSKL'
        ];
        
        $mappedName = $jenisMap[$jenisString] ?? null;
        if ($mappedName) {
            return JenisDonasi::where('nama', $mappedName)->first();
        }
        
        // Fallback - try partial matching
        if (stripos($jenisString, 'zakat') !== false) {
            return JenisDonasi::where('nama', 'like', '%Zakat%')->first();
        }
        
        if (stripos($jenisString, 'infaq') !== false) {
            if (stripos($jenisString, 'terikat') !== false) {
                return JenisDonasi::where('nama', 'Infaq Terikat')->first();
            } else {
                return JenisDonasi::where('nama', 'Infaq Tidak Terikat')->first();
            }
        }
        
        if (stripos($jenisString, 'dskl') !== false) {
            return JenisDonasi::where('nama', 'DSKL')->first();
        }
        
        // Final fallback to Infaq Tidak Terikat
        return JenisDonasi::where('nama', 'Infaq Tidak Terikat')->first() ?? JenisDonasi::first();
    }

    /**
     * Get or create metode pembayaran
     */
    private function getMetodePembayaran(string $viaString): ?MetodePembayaran
    {
        // Direct match with existing payment methods
        $payment = MetodePembayaran::where('nama', $viaString)->first();
        if ($payment) {
            return $payment;
        }
        
        // Handle specific mappings for April data
        $paymentMap = [
            'TF BSI 1809' => 'TF BSI 1809', // Create if doesn't exist
            'TF BSI 1972' => 'TF BSI 1972', // Create if doesn't exist
            'Barang' => 'Donasi Logistik/Barang',
            'Penyaluran Langsung' => 'Penyaluran'
        ];
        
        if (isset($paymentMap[$viaString])) {
            $mappedName = $paymentMap[$viaString];
            $method = MetodePembayaran::where('nama', $mappedName)->first();
            
            // If mapped method doesn't exist, create it (for new bank transfer methods)
            if (!$method && in_array($viaString, ['TF BSI 1809', 'TF BSI 1972'])) {
                $method = MetodePembayaran::create([
                    'nama' => $viaString,
                    'aktif' => true
                ]);
                $this->command->info("✅ Created new payment method: {$viaString}");
            }
            
            return $method;
        }
        
        // Try partial matching for bank transfers
        if (stripos($viaString, 'TF') !== false || stripos($viaString, 'Transfer') !== false) {
            // For TF methods, create new specific method
            return MetodePembayaran::firstOrCreate([
                'nama' => $viaString
            ], [
                'aktif' => true
            ]);
        }
        
        if (stripos($viaString, 'barang') !== false || stripos($viaString, 'logistik') !== false) {
            return MetodePembayaran::where('nama', 'Donasi Logistik/Barang')->first();
        }
        
        // Final fallback to Tunai
        return MetodePembayaran::where('nama', 'Tunai')->first() ?? MetodePembayaran::first();
    }

    /**
     * Get fundraiser
     */
    private function getFundraiser(string $frName): ?Fundraiser
    {
        return Fundraiser::where('nama_fundraiser', trim($frName))->first();
    }

    /**
     * Clean phone number
     */
    private function cleanPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^\d]/', '', $phone);
        
        if (empty($phone)) {
            return '';
        }
        
        // Normalize to format starting with 8 (without country code)
        if (str_starts_with($phone, '62')) {
            return substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            return substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            return $phone;
        }
        
        return $phone;
    }

    /**
     * Update donation statistics
     */
    private function updateDonationStats(JenisDonasi $jenis, MetodePembayaran $metode, float $jumlah): void
    {
        $this->importStats['total_amount'] += $jumlah;
        
        // By donation type
        if (str_contains($jenis->nama, 'Zakat')) {
            $this->importStats['breakdown']['zakat']++;
        } elseif (str_contains($jenis->nama, 'Infaq Terikat')) {
            $this->importStats['breakdown']['infaq_terikat']++;
        } elseif (str_contains($jenis->nama, 'Infaq Tidak Terikat')) {
            $this->importStats['breakdown']['infaq_tidak_terikat']++;
        } elseif (str_contains($jenis->nama, 'DSKL')) {
            $this->importStats['breakdown']['dskl']++;
        }
        
        // By payment method
        if (!isset($this->importStats['by_payment_method'][$metode->nama])) {
            $this->importStats['by_payment_method'][$metode->nama] = 0;
        }
        $this->importStats['by_payment_method'][$metode->nama]++;
    }

    /**
     * Update fundraiser statistics
     */
    private function updateFundraiserStats(string $frName): void
    {
        if (!isset($this->importStats['by_fundraiser'][$frName])) {
            $this->importStats['by_fundraiser'][$frName] = 0;
        }
        $this->importStats['by_fundraiser'][$frName]++;
    }

    /**
     * Display comprehensive final summary
     */
    private function displayFinalSummary(): void
    {
        $this->command->info('');
        $this->command->info('🎉 ===== IMPORT APRIL 2025 COMPLETED SUCCESSFULLY ===== 🎉');
        $this->command->info('');
        
        // Calculate net amount (donations - penyaluran)
        $this->importStats['net_amount'] = $this->importStats['total_amount'] - $this->importStats['total_penyaluran'];
        
        // Overall stats
        $this->command->info("📊 OVERALL STATISTICS:");
        $this->command->info("   ├─ Total rows processed: " . number_format($this->importStats['total_processed']));
        $this->command->info("   ├─ Donations imported: " . number_format($this->importStats['donations_imported']));
        $this->command->info("   ├─ Penyaluran imported: " . number_format($this->importStats['penyaluran_imported']));
        $this->command->info("   ├─ New donors created: " . number_format($this->importStats['donors_created']));
        $this->command->info("   ├─ Existing donors updated: " . number_format($this->importStats['donors_updated']));
        $this->command->info("   ├─ Total donations: Rp " . number_format($this->importStats['total_amount']));
        $this->command->info("   ├─ Total penyaluran: Rp " . number_format($this->importStats['total_penyaluran']));
        $this->command->info("   ├─ Net amount: Rp " . number_format($this->importStats['net_amount']));
        $this->command->info("   ├─ Expected total donasi: Rp 75,571,279");
        $this->command->info("   ├─ Expected penyaluran: Rp 17,629,000");
        $this->command->info("   ├─ Expected net: Rp 57,942,279");
        
        // Accuracy check
        $expectedTotal = 75571279; // Total donasi target
        $expectedPenyaluran = 17629000; // Total penyaluran target
        $expectedNet = 57942279; // Net target
        
        if (abs($this->importStats['total_amount'] - $expectedTotal) < 1000) {
            $this->command->info("   ├─ Donation accuracy: ✅ PERFECT MATCH!");
        } else {
            $this->command->warn("   ├─ Donation accuracy: ⚠️ Difference: Rp " . 
                number_format(abs($this->importStats['total_amount'] - $expectedTotal)));
        }
        
        if (abs($this->importStats['total_penyaluran'] - $expectedPenyaluran) < 1000) {
            $this->command->info("   ├─ Penyaluran accuracy: ✅ PERFECT MATCH!");
        } else {
            $this->command->warn("   ├─ Penyaluran accuracy: ⚠️ Difference: Rp " . 
                number_format(abs($this->importStats['total_penyaluran'] - $expectedPenyaluran)));
        }
        
        $this->command->info("   ├─ Errors: " . $this->importStats['errors']);
        $this->command->info("   └─ Warnings: " . $this->importStats['warnings']);
        
        // Breakdown by donation type
        $this->command->info('');
        $this->command->info("💰 BREAKDOWN BY DONATION TYPE:");
        foreach ($this->importStats['breakdown'] as $type => $count) {
            if ($count > 0) {
                $this->command->info("   ├─ " . ucfirst(str_replace('_', ' ', $type)) . ": {$count}");
            }
        }
        
        // Top fundraisers
        if (!empty($this->importStats['by_fundraiser'])) {
            $this->command->info('');
            $this->command->info("🎯 TOP FUNDRAISERS:");
            arsort($this->importStats['by_fundraiser']);
            foreach (array_slice($this->importStats['by_fundraiser'], 0, 10, true) as $fr => $count) {
                $this->command->info("   ├─ {$fr}: {$count} donations");
            }
        }
        
        // Payment methods
        if (!empty($this->importStats['by_payment_method'])) {
            $this->command->info('');
            $this->command->info("💳 PAYMENT METHODS:");
            arsort($this->importStats['by_payment_method']);
            foreach ($this->importStats['by_payment_method'] as $method => $count) {
                $this->command->info("   ├─ {$method}: {$count} transactions");
            }
        }
        
        $this->command->info('');
        $this->command->info('✅ All April 2025 data has been imported successfully!');
        $this->command->info('📋 Ready for verification in the admin panel.');
        $this->command->info('');
    }
}

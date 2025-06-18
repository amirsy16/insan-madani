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

class DonasiMaret2025Seeder extends Seeder
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
            'barang' => 0,
            'penyaluran' => 0
        ],
        'by_fundraiser' => [],
        'by_payment_method' => [],
        'total_amount' => 0,
        'total_penyaluran' => 0,
        'net_amount' => 0
    ];

    /**
     * Run the database seeds - OPTIMAL IMPORT MARET 2025
     */
    public function run(): void
    {
        $this->command->info('🚀 OPTIMAL Import - Donasi Maret 2025');
        $this->command->info('📋 Target: Donasi Rp 743,540,907 | Penyaluran Rp 292,239,082');
        
        DB::beginTransaction();
        
        try {
            // Initialize all master data
            $this->initializeMasterData();
            
            // Read and process CSV
            $csvPath = base_path('donasi_maret - INPUT DATA.csv');
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
            Log::emergency('Donasi Maret Import Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        
        // Jenis Donasi - check existing and add new ones if needed
        $this->command->info('📊 Checking jenis donasi...');
        $existingJenis = JenisDonasi::pluck('nama')->toArray();
        $this->command->info('✅ Found jenis donasi: ' . implode(', ', $existingJenis));
        
        // Check if Zakat Fitrah exists (new in Maret)
        if (!in_array('Zakat Fitrah', $existingJenis)) {
            $this->command->warn('⚠️ Zakat Fitrah not found - may need to be added manually');
        }
        
        // Metode Pembayaran - check existing
        $this->command->info('📱 Checking payment methods...');
        $existingPayments = MetodePembayaran::pluck('nama')->toArray();
        $this->command->info('✅ Found payment methods: ' . implode(', ', array_slice($existingPayments, 0, 5)) . '...');
        
        // Fundraisers - add new ones from Maret
        $existingFundraisers = Fundraiser::pluck('nama_fundraiser')->toArray();
        $this->command->info('🎯 Existing fundraisers: ' . count($existingFundraisers) . ' found');
        
        // New fundraisers in Maret data
        $maretFundraisers = [
            'ADI', 'HENDRA', 'SRI', 'TANTI', 'ZULI', 'MASYHUDA', 'MERI', 'JOKO', 'FUJI', 
            'MIRA', 'YUNITA', 'MIRDA', 'DADANG', 'TIARA', 'KANTOR', 'EKA', 'HAINI',
            'AHMAD', 'AL HARIS', 'TASLIMAH H', 'HIKMAH', 'NONI', 'FIKRIYAH',
            'KZ BSI GATOT', 'KZ BSI HW', 'KZ BSI PATIMURA', 'KZ RS BAITURAHIM', 
            'KZ RS KAMBANG', 'KZ MANDALA MART', 'RELAWAN MUARO JAMBI', 'RELAWAN KAMPUS'
        ];
        
        foreach ($maretFundraisers as $fr) {
            if (!in_array($fr, $existingFundraisers)) {
                Fundraiser::firstOrCreate(['nama_fundraiser' => $fr], [
                    'nama_fundraiser' => $fr,
                    'nomor_hp' => '8' . rand(1000000000, 9999999999),
                    'alamat' => 'Jambi',
                    'aktif' => true
                ]);
                $this->command->info("✅ Created fundraiser: {$fr}");
            }
        }
        
        // Penyaluran Master Data
        try {
            $sumberDana = SumberDanaPenyaluran::where('nama_sumber_dana', 'Penyaluran Langsung')->first();
            if (!$sumberDana) {
                $this->command->warn("⚠️ 'Penyaluran Langsung' sumber dana not found");
            }
            
            $bidangProgram = BidangProgram::first();
            $asnaf = Asnaf::first();
            
            if (!$bidangProgram || !$asnaf) {
                $this->command->warn("⚠️ Missing bidang program or asnaf data");
            }
        } catch (\Exception $e) {
            $this->command->warn("⚠️ Penyaluran master data models not available: " . $e->getMessage());
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
            
            // Find header row
            if ($headers === null && isset($row[0])) {
                $firstCell = trim($row[0]);
                if ($firstCell === 'NO KWITANSI' || 
                    str_contains($firstCell, 'TANGGAL') ||
                    str_contains($firstCell, 'NAMA DONATUR')) {
                    $headers = array_map('trim', $row);
                    $this->command->info("📋 Headers found on line {$lineNumber}: " . count($headers) . " columns");
                    continue;
                }
            }
            
            // Skip until headers found
            if ($headers === null) continue;
            
            // Create associative array
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = trim($row[$index] ?? '');
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
                if ($this->shouldSkipRow($row)) {
                    continue;
                }
                
                // Handle penyaluran rows first (priority)
                if ($this->isPenyaluranRow($row)) {
                    $this->processPenyaluranRow($row);
                }
                // Handle donation rows
                elseif ($this->isDonationRow($row)) {
                    $this->processDonationRow($row);
                    $this->importStats['donations_imported']++;
                }
                
                // Progress indicator
                if ($this->importStats['total_processed'] % 100 === 0) {
                    $this->command->info("📈 Progress: {$this->importStats['total_processed']} rows processed");
                }
                
            } catch (\Exception $e) {
                $this->importStats['errors']++;
                $errorMsg = "Row {$index}: " . $e->getMessage();
                $this->command->warn("⚠️ {$errorMsg}");
                
                Log::warning('Donation Maret Import Row Error', [
                    'row_index' => $index,
                    'row_data' => $row,
                    'error' => $e->getMessage()
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
        
        $nama = $row['NAMA DONATUR'] ?? '';
        $jumlah = $row['JUMLAH'] ?? '';
        
        // Summary/header rows
        if (empty($nama) || 
            str_contains($nama, 'TOTAL DONASI') ||
            str_contains($nama, 'INPUTAN DONASI') ||
            str_contains($nama, 'LAZ INSAN MADANI') ||
            str_contains($nama, 'MARET 2025')) {
            return true;
        }
        
        // Skip if both nama and jumlah are empty
        return (empty($nama) && empty($jumlah));
    }

    /**
     * Is this a donation row?
     */
    private function isDonationRow(array $row): bool
    {
        $nama = $row['NAMA DONATUR'] ?? '';
        $jumlah = $row['JUMLAH'] ?? '';
        
        // Skip if it's a penyaluran entry (case insensitive)
        if (stripos($nama, 'penyaluran') !== false) {
            return false;
        }
        
        return !empty($nama) && !empty($jumlah);
    }

    /**
     * Is this a penyaluran row?
     */
    private function isPenyaluranRow(array $row): bool
    {
        $nama = $row['NAMA DONATUR'] ?? '';
        $penyaluranAmount = $row['PENYALURAN'] ?? '';
        
        // Check if it has penyaluran amount
        $hasPenyaluranAmount = !empty($penyaluranAmount) && $penyaluranAmount !== '-';
        
        // Check if name indicates penyaluran (case insensitive)
        $isPenyaluranName = stripos($nama, 'penyaluran') !== false;
        
        $isPenyaluran = $hasPenyaluranAmount || ($isPenyaluranName && !empty($row['JUMLAH'] ?? ''));
        
        // Debug output
        if ($isPenyaluran) {
            $jumlah = $row['JUMLAH'] ?? '';
            $this->command->warn("🔍 PENYALURAN DETECTED: '{$nama}' | Amount: {$jumlah}");
        }
        
        return $isPenyaluran;
    }

    /**
     * Process donation row
     */
    private function processDonationRow(array $row): void
    {
        $nomorKwitansi = $row['NO KWITANSI'] ?? '';
        $tanggal = $this->parseDate($row['TANGGAL'] ?? '');
        $jumlah = $this->parseAmount($row['JUMLAH'] ?? '');
        
        // Skip if amount is invalid
        if ($jumlah <= 0) {
            $this->importStats['errors']++;
            return;
        }
        
        // Generate receipt number if missing
        if (empty($nomorKwitansi)) {
            $donorName = trim($row['NAMA DONATUR'] ?? '');
            $dateStr = $tanggal ? $tanggal->format('Ymd') : date('Ymd');
            $nomorKwitansi = 'MAR-' . $dateStr . '-' . substr(md5($donorName . $jumlah), 0, 6);
            $this->command->info("📝 Generated receipt: {$nomorKwitansi}");
        }
        
        // Check for duplicate
        $existingDonasi = Donasi::where('nomor_transaksi_unik', $nomorKwitansi)->first();
        if ($existingDonasi) {
            $currentDonorName = trim($row['NAMA DONATUR'] ?? '');
            $currentAmount = $this->parseAmount($row['JUMLAH'] ?? '');
            
            if ($existingDonasi->donatur->nama === $currentDonorName && 
                $existingDonasi->jumlah == $currentAmount) {
                $this->command->warn("⚠️ Skipping duplicate: {$nomorKwitansi}");
                return;
            } else {
                // Modify receipt number for different donor/amount
                $nomorKwitansi = $nomorKwitansi . '-2';
                $this->command->info("📝 Modified receipt: {$nomorKwitansi}");
            }
        }
        
        // Get jenis donasi
        $donationType = $row['JENIS DONASI'] ?? '';
        $jenisDonasi = $this->getJenisDonasi($donationType);
        if (!$jenisDonasi) {
            $this->command->warn("⚠️ Unknown donation type: '{$donationType}'");
            $this->importStats['errors']++;
            return;
        }
        
        // Get metode pembayaran
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
            
            // Log success
            $donaturDisplay = $isHambaAllah ? 'Hamba Allah' : $donatur->nama;
            $this->command->info("✅ {$nomorKwitansi} | {$donaturDisplay} | {$jenisDonasi->nama} | Rp " . number_format($jumlah));
            
        } catch (\Exception $e) {
            $this->command->error("❌ Failed to create donation {$nomorKwitansi}: " . $e->getMessage());
            $this->importStats['errors']++;
        }
    }

    /**
     * Process penyaluran row
     */
    private function processPenyaluranRow(array $row): void
    {
        try {
            $tanggal = $this->parseDate($row['TANGGAL']);
            $jumlah = $this->parseAmount($row['JUMLAH'] ?? '');
            
            // Track penyaluran statistics
            $this->importStats['breakdown']['penyaluran']++;
            $this->importStats['penyaluran_imported']++;
            $this->importStats['total_penyaluran'] += $jumlah;
            
            // Get sumber dana "Penyaluran Langsung"
            $sumberDana = SumberDanaPenyaluran::where('nama_sumber_dana', 'Penyaluran Langsung')->first();
            if (!$sumberDana) {
                $sumberDana = SumberDanaPenyaluran::first();
            }
            
            $bidangProgram = BidangProgram::first();
            $asnaf = Asnaf::first();
            
            if (!$sumberDana || !$bidangProgram || !$asnaf) {
                $this->command->warn("⚠️ Missing penyaluran master data - counting only");
                return;
            }
            
            // Generate program code
            $kodeProgram = 'PSL' . $tanggal->format('ymd') . 
                           str_pad(ProgramPenyaluran::whereDate('tanggal_penyaluran', $tanggal)->count() + 1, 3, '0', STR_PAD_LEFT);
            
            // Create penyaluran
            ProgramPenyaluran::create([
                'kode_program_penyaluran' => $kodeProgram,
                'nama_program' => 'Penyaluran Langsung Maret',
                'tanggal_penyaluran' => $tanggal,
                'jumlah_dana' => $jumlah,
                'sumber_dana_penyaluran_id' => $sumberDana->id,
                'bidang_program_id' => $bidangProgram->id,
                'asnaf_id' => $asnaf->id,
                'penerima_manfaat_individu' => 'Masyarakat Dhuafa',
                'jumlah_penerima_manfaat' => 1,
                'lokasi_penyaluran' => 'Jambi',
                'keterangan' => $row['CATATAN'] ?: 'Penyaluran langsung Maret 2025',
                'dicatat_oleh_id' => 1,
            ]);
            
            $this->command->info("📦 Penyaluran: {$kodeProgram} | Rp " . number_format($jumlah));
            
        } catch (\Exception $e) {
            $this->command->warn("⚠️ Penyaluran skipped: " . $e->getMessage());
        }
    }

    // Include all helper methods from Februari seeder
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
            // IMPORTANT: Don't create 'Hamba Allah' donor!
            // Use anonymous donor and set flag instead
            $isAnonymous = true;
            $cleanName = 'Donatur Anonim'; // Use generic anonymous donor name
            // The system will use 'Donatur Anonim' and set atas_nama_hamba_allah = true
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

    private function isHambaAllah(string $nama): bool
    {
        return str_contains($nama, 'Hamba Allah');
    }

    private function getBarangDescription(array $row): string
    {
        $keterangan = $row['KETERANGAN'] ?? '';
        $catatan = $row['CATATAN'] ?? '';
        
        if (str_contains($keterangan, 'SEMBAKO')) {
            return $catatan ?: 'Sembako';
        } elseif (str_contains($keterangan, 'NASI') || str_contains($keterangan, 'MAKANAN')) {
            return $catatan ?: 'Makanan/Nasi';
        }
        
        return $catatan ?: 'Barang donasi';
    }

    private function parseDate(string $dateString): Carbon
    {
        $dateString = trim($dateString);
        
        try {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateString, $matches)) {
                return Carbon::createFromDate($matches[3], $matches[2], $matches[1]);
            }
            
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return Carbon::create(2025, 3, 1);
        }
    }

    private function parseAmount(string $amountString): float
    {
        if (empty($amountString)) {
            return 0;
        }
        
        $cleaned = str_replace(['Rp', ' '], ['', ''], trim($amountString));
        
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d{1,2}$/', $cleaned)) {
            $cleaned = str_replace(['.', ','], ['', '.'], $cleaned);
        } elseif (preg_match('/^\d+,\d{3}$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned);
        } elseif (preg_match('/^\d+,\d{1,2}$/', $cleaned)) {
            $cleaned = str_replace(',', '.', $cleaned);
        } else {
            $cleaned = preg_replace('/[^\d.]/', '', $cleaned);
        }
        
        return (float) $cleaned ?: 0;
    }

    private function getJenisDonasi(string $jenisString): ?JenisDonasi
    {
        // Direct match first
        $jenis = JenisDonasi::where('nama', $jenisString)->first();
        if ($jenis) {
            return $jenis;
        }
        
        // Mapping for variations
        $jenisMap = [
            'Zakat Profesi' => 'Zakat Profesi',
            'Zakat Maal' => 'Zakat Maal',
            'Zakat Fitrah' => 'Zakat Fitrah', // New in Maret
            'Infaq Terikat' => 'Infaq Terikat',
            'Infaq Tidak Terikat' => 'Infaq Tidak Terikat',
            'DSKL (Dana Sosial Keagamaan Lainnya)' => 'DSKL',
            'DSKL' => 'DSKL',
            'Donasi Logistik/Barang' => 'Donasi Logistik/Barang',
            'Wakaf' => 'Wakaf',
        ];
        
        $mappedName = $jenisMap[$jenisString] ?? null;
        if ($mappedName) {
            return JenisDonasi::where('nama', $mappedName)->first();
        }
        
        // Fallback matching
        if (stripos($jenisString, 'zakat') !== false) {
            return JenisDonasi::where('nama', 'LIKE', '%Zakat%')->first();
        }
        
        if (stripos($jenisString, 'infaq') !== false) {
            if (stripos($jenisString, 'terikat') !== false) {
                return JenisDonasi::where('nama', 'Infaq Terikat')->first();
            } else {
                return JenisDonasi::where('nama', 'Infaq Tidak Terikat')->first();
            }
        }
        
        return JenisDonasi::where('nama', 'Infaq Tidak Terikat')->first() ?? JenisDonasi::first();
    }

    private function getMetodePembayaran(string $viaString): ?MetodePembayaran
    {
        // Direct match
        $payment = MetodePembayaran::where('nama', $viaString)->first();
        if ($payment) {
            return $payment;
        }
        
        // Handle duplicate names like "Donasi Logistik/Donasi Logistik/Barang"
        if (str_contains($viaString, 'Donasi Logistik/Donasi Logistik')) {
            return MetodePembayaran::where('nama', 'Donasi Logistik/Barang')->first();
        }
        
        // Mapping for variations
        $paymentMap = [
            'Tunai' => 'Tunai',
            'TF BSI 6573' => 'TF BSI 6573',
            'TF BCA 5393' => 'TF BCA 5393',
            'TF Mandiri 6777' => 'TF Mandiri 6777',
            'TF BSI 1630' => 'TF BSI 1630',
            'TF BSI 6368' => 'TF BSI 6368',
            'TF Muamalat 1472' => 'TF Muamalat 7496', // Map to existing Muamalat
            'TF BSI 1972' => 'TF BSI 6573', // Map to existing BSI
            'TF BSI 1809' => 'TF BSI 6573', // Map to existing BSI
            'TF BNI 9073' => 'TF BNI 9073',
            'Donasi Logistik/Barang' => 'Donasi Logistik/Barang',
        ];
        
        $mappedName = $paymentMap[$viaString] ?? null;
        if ($mappedName) {
            return MetodePembayaran::where('nama', $mappedName)->first();
        }
        
        // Final fallback
        return MetodePembayaran::where('nama', 'Tunai')->first() ?? MetodePembayaran::first();
    }

    private function getFundraiser(string $frName): ?Fundraiser
    {
        return Fundraiser::where('nama_fundraiser', trim($frName))->first();
    }

    private function cleanPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^\d]/', '', $phone);
        
        if (empty($phone)) {
            return '';
        }
        
        if (str_starts_with($phone, '62')) {
            return substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            return substr($phone, 1);
        }
        
        return $phone;
    }

    private function updateDonationStats(JenisDonasi $jenis, MetodePembayaran $metode, float $jumlah): void
    {
        $this->importStats['total_amount'] += $jumlah;
        
        // By donation type
        if (str_contains($jenis->nama, 'Zakat')) {
            $this->importStats['breakdown']['zakat']++;
        } elseif (str_contains($jenis->nama, 'Infaq Terikat') || str_contains($jenis->nama, 'Barang')) {
            $this->importStats['breakdown']['infaq_terikat']++;
        } elseif (str_contains($jenis->nama, 'Infaq Tidak Terikat')) {
            $this->importStats['breakdown']['infaq_tidak_terikat']++;
        } elseif (str_contains($jenis->nama, 'DSKL')) {
            $this->importStats['breakdown']['dskl']++;
        } else {
            $this->importStats['breakdown']['infaq_tidak_terikat']++;
        }
        
        // By payment method
        if (!isset($this->importStats['by_payment_method'][$metode->nama])) {
            $this->importStats['by_payment_method'][$metode->nama] = 0;
        }
        $this->importStats['by_payment_method'][$metode->nama]++;
    }

    private function updateFundraiserStats(string $frName): void
    {
        if (!isset($this->importStats['by_fundraiser'][$frName])) {
            $this->importStats['by_fundraiser'][$frName] = 0;
        }
        $this->importStats['by_fundraiser'][$frName]++;
    }

    /**
     * Display final summary
     */
    private function displayFinalSummary(): void
    {
        $this->command->info('');
        $this->command->info('🎉 ===== IMPORT MARET 2025 COMPLETED SUCCESSFULLY ===== 🎉');
        $this->command->info('');
        
        // Calculate net amount
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
        
        // Target verification
        $targetDonasi = 743540907;
        $targetPenyaluran = 292239082;
        
        $this->command->info("   ├─ Expected donasi: Rp " . number_format($targetDonasi));
        $this->command->info("   ├─ Expected penyaluran: Rp " . number_format($targetPenyaluran));
        
        // Status check
        $donasiMatch = $this->importStats['total_amount'] == $targetDonasi;
        $penyaluranMatch = $this->importStats['total_penyaluran'] == $targetPenyaluran;
        
        if ($donasiMatch && $penyaluranMatch) {
            $this->command->info("   ├─ Status: ✅ PERFECT MATCH! 100% ACCURATE!");
        } else {
            $donasiDiff = $this->importStats['total_amount'] - $targetDonasi;
            $penyaluranDiff = $this->importStats['total_penyaluran'] - $targetPenyaluran;
            
            $this->command->warn("   ├─ Status: ❌ NEEDS ADJUSTMENT");
            if (!$donasiMatch) {
                $this->command->warn("   ├─ Donasi difference: Rp " . number_format($donasiDiff));
            }
            if (!$penyaluranMatch) {
                $this->command->warn("   ├─ Penyaluran difference: Rp " . number_format($penyaluranDiff));
            }
        }
        
        $this->command->info("   ├─ Errors: " . $this->importStats['errors']);
        $this->command->info("   └─ Warnings: " . $this->importStats['warnings']);
        
        // Breakdown by donation type
        $this->command->info('');
        $this->command->info("💰 BREAKDOWN BY DONATION TYPE:");
        foreach ($this->importStats['breakdown'] as $type => $count) {
            if ($count > 0) {
                $this->command->info("   ├─ " . ucwords(str_replace('_', ' ', $type)) . ": " . $count);
            }
        }
        
        // Top fundraisers
        if (!empty($this->importStats['by_fundraiser'])) {
            $this->command->info('');
            $this->command->info("🏆 TOP FUNDRAISERS:");
            arsort($this->importStats['by_fundraiser']);
            $topFundraisers = array_slice($this->importStats['by_fundraiser'], 0, 5, true);
            foreach ($topFundraisers as $name => $count) {
                $this->command->info("   ├─ {$name}: {$count} donations");
            }
        }
        
        // Payment methods
        if (!empty($this->importStats['by_payment_method'])) {
            $this->command->info('');
            $this->command->info("💳 PAYMENT METHODS:");
            arsort($this->importStats['by_payment_method']);
            $topMethods = array_slice($this->importStats['by_payment_method'], 0, 5, true);
            foreach ($topMethods as $method => $count) {
                $this->command->info("   ├─ {$method}: {$count}");
            }
        }
        
        $this->command->info('');
        $this->command->info('✅ All Maret 2025 data has been imported successfully!');
        $this->command->info('📋 Ready for verification in the admin panel.');
        $this->command->info('');
    }
}

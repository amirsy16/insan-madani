<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\JenisDonasi;
use App\Models\MetodePembayaran;
use App\Models\Fundraiser;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DonasiSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('💰 Creating comprehensive Donasi seeder with 15,000+ realistic records...');
        $startTime = microtime(true);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Donasi::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $faker = Faker::create('id_ID');
        
        // Get master data
        $donaturs = Donatur::pluck('id')->all();
        $jenisDonasis = JenisDonasi::where('aktif', true)->get();
        $metodePembayarans = MetodePembayaran::pluck('id')->all();
        $fundraisers = Fundraiser::where('aktif', true)->pluck('id')->all();
        $users = User::pluck('id')->all();
        
        if (empty($donaturs) || $jenisDonasis->isEmpty() || empty($metodePembayarans) || empty($users)) {
            $this->command->error('❌ Missing required master data. Please run other seeders first.');
            return;
        }
        
        // Realistic donation amounts (Indonesian context)
        $donationAmountGenerators = [
            // Micro donations (40%)
            function() use ($faker) { return $faker->numberBetween(5000, 50000); },
            function() use ($faker) { return $faker->numberBetween(5000, 50000); },
            function() use ($faker) { return $faker->numberBetween(5000, 50000); },
            function() use ($faker) { return $faker->numberBetween(5000, 50000); },
            // Small donations (30%)
            function() use ($faker) { return $faker->numberBetween(50000, 200000); },
            function() use ($faker) { return $faker->numberBetween(50000, 200000); },
            function() use ($faker) { return $faker->numberBetween(50000, 200000); },
            // Medium donations (20%)  
            function() use ($faker) { return $faker->numberBetween(200000, 1000000); },
            function() use ($faker) { return $faker->numberBetween(200000, 1000000); },
            // Large donations (8%)
            function() use ($faker) { return $faker->numberBetween(1000000, 5000000); },
            // Major donations (2%)
            function() use ($faker) { return $faker->numberBetween(5000000, 25000000); },
        ];
        
        // Donation comments for variety
        $donationComments = [
            'Semoga bermanfaat', 'Barakallahu fiikum', 'Mudah-mudahan berkah',
            'Untuk kebaikan umat', 'Lillahi ta\'ala', 'Semoga Allah meridhoi',
            'Infaq fi sabilillah', 'Mohon didoakan', 'Amal jariyah',
            'Semoga menjadi pahala', 'Bismillah', 'Allahu a\'lam',
            '', '', '', // Some empty comments
        ];
        
        $totalRecords = 15000;
        $batchSize = 1000;
        
        $this->command->info("📊 Target: {$totalRecords} records in batches of {$batchSize}");
        
        for ($batch = 0; $batch < ceil($totalRecords / $batchSize); $batch++) {
            $donasiData = [];
            $batchStartTime = microtime(true);
            
            $recordsInThisBatch = min($batchSize, $totalRecords - ($batch * $batchSize));
            
            for ($i = 0; $i < $recordsInThisBatch; $i++) {
                $recordIndex = ($batch * $batchSize) + $i + 1;
                
                // Select jenis donasi with weighted distribution
                $jenisDonasiWeights = [
                    'Infaq' => 40,      // Most common
                    'Sedekah' => 25,    // Second most common
                    'Zakat' => 20,      // Religious obligation
                    'Wakaf' => 10,      // Property donation
                    'DSKL' => 3,        // Other religious funds
                    'Barang' => 2,      // Goods donation
                ];
                
                $jenisDonasi = $this->selectWeightedJenisDonasi($jenisDonasis, $jenisDonasiWeights, $faker);
                
                // Generate donation amount based on type
                $jumlah = 0;
                $perkiraan_nilai_barang = null;
                $deskripsi_barang = null;
                
                if ($jenisDonasi && $jenisDonasi->apakah_barang) {
                    // For goods donation
                    $barangTypes = [
                        'Beras 5kg kualitas premium',
                        'Pakaian layak pakai 10 pcs',
                        'Minyak goreng 2 liter',
                        'Gula pasir 1 kg',
                        'Sarung dan mukena',
                        'Buku-buku islami',
                        'Al-Quran dan sajadah',
                        'Peralatan ibadah'
                    ];
                    $deskripsi_barang = $faker->randomElement($barangTypes);
                    $perkiraan_nilai_barang = $faker->numberBetween(50000, 500000);
                } else {
                    // For cash donation
                    $amountGenerator = $faker->randomElement($donationAmountGenerators);
                    $jumlah = $amountGenerator();
                    
                    // Zakat tends to be larger amounts
                    if ($jenisDonasi && str_contains(strtolower($jenisDonasi->nama), 'zakat')) {
                        $jumlah = $faker->numberBetween(250000, 10000000);
                    }
                }
                
                // 15% anonymous donations
                $atas_nama_hamba_allah = $faker->boolean(15);
                $donaturId = $atas_nama_hamba_allah ? null : $faker->randomElement($donaturs);
                
                // 40% through fundraisers
                $fundraiserId = !empty($fundraisers) && $faker->boolean(40) 
                    ? $faker->randomElement($fundraisers) 
                    : null;
                
                // Generate realistic transaction date (weighted towards recent)
                $daysAgo = $this->generateWeightedDaysAgo($faker);
                $tanggalDonasi = now()->subDays($daysAgo);
                
                // Status distribution (85% verified for performance testing)
                $statusWeights = ['verified' => 85, 'pending' => 12, 'rejected' => 3];
                $status = $this->selectWeightedStatus($statusWeights, $faker);
                
                // Confirmation details
                $dikofirmasi_oleh_user_id = null;
                $dikonfirmasi_pada = null;
                $catatan_konfirmasi = null;
                
                if ($status === 'verified') {
                    $dikofirmasi_oleh_user_id = $faker->randomElement($users);
                    $dikonfirmasi_pada = $tanggalDonasi->addHours($faker->numberBetween(1, 48));
                    $catatan_konfirmasi = $faker->randomElement([
                        'Donasi telah diterima dengan baik',
                        'Transfer berhasil dikonfirmasi',
                        'Barakallahu fiikum atas donasinya',
                        'Terima kasih atas kepercayaannya',
                        null, null // Some without confirmation notes
                    ]);
                } elseif ($status === 'rejected') {
                    $dikofirmasi_oleh_user_id = $faker->randomElement($users);
                    $dikonfirmasi_pada = $tanggalDonasi->addHours($faker->numberBetween(2, 72));
                    $catatan_konfirmasi = $faker->randomElement([
                        'Bukti pembayaran tidak valid',
                        'Nominal tidak sesuai',
                        'Perlu verifikasi ulang'
                    ]);
                }
                
                $donasiData[] = [
                    'donatur_id' => $donaturId,
                    'jenis_donasi_id' => $jenisDonasi->id,
                    'metode_pembayaran_id' => $faker->randomElement($metodePembayarans),
                    'fundraiser_id' => $fundraiserId,
                    'jumlah' => $jumlah,
                    'keterangan_infak_khusus' => $this->generateKeteranganInfak($jenisDonasi, $faker),
                    'deskripsi_barang' => $deskripsi_barang,
                    'perkiraan_nilai_barang' => $perkiraan_nilai_barang,
                    'catatan_donatur' => $faker->randomElement($donationComments),
                    'tanggal_donasi' => $tanggalDonasi->format('Y-m-d'),
                    'atas_nama_hamba_allah' => $atas_nama_hamba_allah,
                    'nomor_transaksi_unik' => 'TRX' . date('Ymd') . sprintf('%06d', $recordIndex),
                    'status_konfirmasi' => $status,
                    'dikofirmasi_oleh_user_id' => $dikofirmasi_oleh_user_id,
                    'dikonfirmasi_pada' => $dikonfirmasi_pada,
                    'catatan_konfirmasi' => $catatan_konfirmasi,
                    'dicatat_oleh_user_id' => $faker->randomElement($users),
                    'created_at' => $tanggalDonasi,
                    'updated_at' => $dikonfirmasi_pada ?: $tanggalDonasi,
                ];
            }
            
            // Insert batch
            DB::table('donasis')->insert($donasiData);
            $currentBatch = $batch + 1;
            
            $batchTime = round(microtime(true) - $batchStartTime, 2);
            $totalProgress = round(($currentBatch / ceil($totalRecords / $batchSize)) * 100, 1);
            $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 1);
            
            $this->command->info("✅ Batch {$currentBatch}: {$recordsInThisBatch} records inserted in {$batchTime}s (Progress: {$totalProgress}%, Memory: {$memoryUsage}MB)");
        }
        
        $totalTime = round(microtime(true) - $startTime, 2);
        $actualTotal = Donasi::count();
        $totalAmount = Donasi::where('status_konfirmasi', 'verified')->sum('jumlah');
        $totalBarang = Donasi::where('status_konfirmasi', 'verified')->sum('perkiraan_nilai_barang');
        
        $this->command->info("🎉 DonasiSeeder completed!");
        $this->command->info("📊 Total records created: " . number_format($actualTotal));
        $this->command->info("💰 Total cash donations: Rp " . number_format($totalAmount));
        $this->command->info("📦 Total goods value: Rp " . number_format($totalBarang ?? 0));
        $this->command->info("💸 Grand total value: Rp " . number_format($totalAmount + ($totalBarang ?? 0)));
        $this->command->info("⏱️  Total execution time: {$totalTime} seconds");
        $this->command->info("🚀 Average: " . round($actualTotal / $totalTime, 0) . " records/second");
    }
    
    private function selectWeightedJenisDonasi($jenisDonasis, $weights, $faker)
    {
        $totalWeight = array_sum($weights);
        $random = $faker->numberBetween(1, $totalWeight);
        $currentWeight = 0;
        
        foreach ($weights as $name => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $jenisDonasis->first(function ($jenis) use ($name) {
                    return str_contains(strtolower($jenis->nama), strtolower($name));
                }) ?: $jenisDonasis->random();
            }
        }
        
        return $jenisDonasis->random();
    }
    
    private function selectWeightedStatus($weights, $faker)
    {
        $totalWeight = array_sum($weights);
        $random = $faker->numberBetween(1, $totalWeight);
        $currentWeight = 0;
        
        foreach ($weights as $status => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $status;
            }
        }
        
        return 'pending';
    }
    
    private function generateWeightedDaysAgo($faker)
    {
        // Weight recent donations more heavily
        $weightedRanges = [
            ['range' => [0, 7], 'weight' => 30],       // Last week: 30%
            ['range' => [8, 30], 'weight' => 25],      // Last month: 25%
            ['range' => [31, 90], 'weight' => 20],     // Last 3 months: 20%
            ['range' => [91, 180], 'weight' => 15],    // Last 6 months: 15%
            ['range' => [181, 365], 'weight' => 10],   // Last year: 10%
        ];
        
        $totalWeight = array_sum(array_column($weightedRanges, 'weight'));
        $random = $faker->numberBetween(1, $totalWeight);
        $currentWeight = 0;
        
        foreach ($weightedRanges as $item) {
            $currentWeight += $item['weight'];
            if ($random <= $currentWeight) {
                return $faker->numberBetween($item['range'][0], $item['range'][1]);
            }
        }
        
        return $faker->numberBetween(0, 365);
    }
    
    private function generateKeteranganInfak($jenisDonasi, $faker)
    {
        if (!$jenisDonasi || !$jenisDonasi->membutuhkan_keterangan_tambahan) {
            return null;
        }
        
        $keteranganOptions = [
            'Infaq untuk yatim piatu',
            'Infaq untuk anak terlantar',
            'Infaq untuk pendidikan',
            'Infaq untuk kesehatan',
            'Infaq untuk masjid',
            'Infaq untuk kegiatan dakwah',
            'Infaq untuk bencana alam',
            'Infaq untuk fakir miskin',
        ];
        
        return $faker->randomElement($keteranganOptions);
    }
}
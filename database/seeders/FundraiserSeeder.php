<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Fundraiser;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class FundraiserSeeder extends Seeder {
    public function run(): void {
        $this->command->info('🎯 Creating comprehensive Fundraiser seeder with 200+ realistic records...');
        $startTime = microtime(true);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Fundraiser::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $faker = Faker::create('id_ID');
        
        // Get existing user IDs for some fundraisers to have accounts
        $userIds = User::pluck('id')->all();
        
        // Indonesian fundraiser names for variety
        $maleNames = [
            'Ahmad Syahrul', 'Muhammad Ridwan', 'Abdul Rahman', 'Budi Santoso', 'Agus Firmansyah',
            'Andi Kurniawan', 'Bambang Wijaya', 'Dedi Setiawan', 'Eko Prasetyo', 'Fajar Nugroho',
            'Gilang Ramadhan', 'Hendra Gunawan', 'Irwan Susanto', 'Joko Prabowo', 'Kurniawan Putra',
            'Lukman Hakim', 'Made Sutrisno', 'Nurul Huda', 'Oki Hermawan', 'Putra Mahendra',
            'Rizki Aditya', 'Syahrul Munir', 'Taufik Hidayat', 'Umar Farouk', 'Wahyu Saputra'
        ];
        
        $femaleNames = [
            'Siti Nurhaliza', 'Sri Rahayu', 'Nur Aini', 'Dewi Sartika', 'Rina Kusuma',
            'Maya Anggraini', 'Lilis Suryani', 'Fitri Handayani', 'Indira Safitri', 'Kartika Sari',
            'Maharani Putri', 'Nanda Pratiwi', 'Octavia Melati', 'Putri Cahyani', 'Qonita Fitriani',
            'Ratna Dewi', 'Sari Puspita', 'Tuti Wardani', 'Ulfa Pertiwi', 'Vina Kusumawati',
            'Wulan Andini', 'Yanti Lestari', 'Zahra Maharani', 'Ayu Permata', 'Bunga Citra'
        ];
        
        // Indonesian address components
        $streetPrefixes = [
            'Jl. H. Agus Salim', 'Jl. KH. Hasyim Ashari', 'Jl. Prof. Dr. Hamka', 'Jl. Kyai Maja',
            'Jl. Ulama', 'Jl. Masjid Raya', 'Jl. Pondok Pesantren', 'Jl. Islamic Center',
            'Jl. Dakwah', 'Jl. Tabligh', 'Jl. Hidayah', 'Jl. Barakah', 'Jl. Rahmat',
            'Jl. Taqwa', 'Jl. Iman', 'Jl. Sholeh', 'Jl. Amanah', 'Jl. Berkah'
        ];
        
        $kelurahan = [
            'Menteng', 'Gondangdia', 'Cikini', 'Kemayoran', 'Senen', 'Johar Baru',
            'Tanah Abang', 'Bendungan Hilir', 'Kebon Melati', 'Petojo Utara',
            'Gambir', 'Duri Pulo', 'Cideng', 'Petojo Selatan', 'Kebon Kacang'
        ];
        
        $totalRecords = 250;
        $batchSize = 50;
        
        $this->command->info("📊 Target: {$totalRecords} records in batches of {$batchSize}");
        
        for ($batch = 0; $batch < ceil($totalRecords / $batchSize); $batch++) {
            $fundraiserData = [];
            $batchStartTime = microtime(true);
            
            $recordsInThisBatch = min($batchSize, $totalRecords - ($batch * $batchSize));
            
            for ($i = 0; $i < $recordsInThisBatch; $i++) {
                $gender = $faker->randomElement(['male', 'female']);
                $nama = $gender === 'male' 
                    ? $faker->randomElement($maleNames)
                    : $faker->randomElement($femaleNames);
                
                // Generate realistic NIK (16 digits)
                $nik = $faker->numerify('################');
                
                // Generate realistic phone number
                $phonePrefix = $faker->randomElement(['811', '812', '813', '821', '822', '823', '851', '852', '853']);
                $phoneNumber = '08' . $phonePrefix . $faker->numerify('######');
                
                // Generate realistic address
                $streetPrefix = $faker->randomElement($streetPrefixes);
                $streetNumber = $faker->numberBetween(1, 299);
                $rtRw = 'RT ' . sprintf('%02d', $faker->numberBetween(1, 15)) . '/RW ' . sprintf('%02d', $faker->numberBetween(1, 8));
                $kelurahanName = $faker->randomElement($kelurahan);
                $alamat = $streetPrefix . ' No. ' . $streetNumber . ', ' . $rtRw . ', ' . $kelurahanName;
                
                // 30% chance of having user account
                $userId = null;
                if (!empty($userIds) && $faker->boolean(30)) {
                    $userId = $faker->randomElement($userIds);
                }
                
                // 85% active rate for realistic distribution
                $aktif = $faker->boolean(85);
                
                $fundraiserData[] = [
                    'nama_fundraiser' => $nama,
                    'nomor_identitas' => $nik,
                    'nomor_hp' => $phoneNumber,
                    'alamat' => $alamat,
                    'user_id' => $userId,
                    'aktif' => $aktif,
                    'created_at' => now()->subDays($faker->numberBetween(1, 180)),
                    'updated_at' => now(),
                ];
            }
            
            // Insert batch
            DB::table('fundraisers')->insert($fundraiserData);
            $currentBatch = $batch + 1;
            
            $batchTime = round(microtime(true) - $batchStartTime, 2);
            $totalProgress = round(($currentBatch / ceil($totalRecords / $batchSize)) * 100, 1);
            
            $this->command->info("✅ Batch {$currentBatch}: {$recordsInThisBatch} records inserted in {$batchTime}s (Progress: {$totalProgress}%)");
        }
        
        $totalTime = round(microtime(true) - $startTime, 2);
        $actualTotal = Fundraiser::count();
        
        $this->command->info("🎉 FundraiserSeeder completed!");
        $this->command->info("📊 Total records created: {$actualTotal}");
        $this->command->info("⏱️  Total execution time: {$totalTime} seconds");
        $this->command->info("🚀 Average: " . round($actualTotal / $totalTime, 0) . " records/second");
    }
}

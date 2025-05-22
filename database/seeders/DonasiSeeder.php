<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\Fundraiser;
use App\Models\JenisDonasi;
use App\Models\MetodePembayaran;
use App\Models\User;
use Faker\Factory as Faker;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DonasiSeeder extends Seeder {
    public function run(): void {
        // Gunakan statement DB untuk menangani foreign key constraint
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Donasi::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Buat instance Faker dengan locale id_ID dan en_US sebagai fallback
        $fakerID = Faker::create('id_ID');
        $fakerEN = Faker::create('en_US'); // Untuk formatter yang tidak ada di id_ID
        
        $donaturIds = Donatur::pluck('id')->all();
        $jenisDonasiData = JenisDonasi::all(); // Ambil semua data untuk cek flag
        $metodePembayaranIds = MetodePembayaran::pluck('id')->all();
        $fundraiserIds = Fundraiser::pluck('id')->all();
        
        // Perbaikan: Ambil user IDs berdasarkan roles dari Spatie Permission
        $adminRoleIds = Role::whereIn('name', ['admin', 'super_admin'])->pluck('id')->toArray();
        $adminUserIds = User::whereHas('roles', function($query) use ($adminRoleIds) {
            $query->whereIn('id', $adminRoleIds);
        })->pluck('id')->all();
        
        // Jika tidak ada admin, gunakan user ID yang ada sebagai fallback
        if (empty($adminUserIds)) {
            $adminUserIds = User::pluck('id')->all();
        }

        if (empty($donaturIds) || $jenisDonasiData->isEmpty() || empty($metodePembayaranIds) || empty($adminUserIds)) {
            $this->command->warn('Pastikan tabel donaturs, jenis_donasis, metode_pembayarans, dan users (admin) sudah terisi.');
            return;
        }
        $hambaAllahDonaturId = Donatur::where('nama', 'Hamba Allah')->first()?->id;

        // Perbaiki nomor HP donatur yang ada untuk format yang benar (dimulai dengan 8)
        $this->fixDonatursPhoneNumbers();
        
        // Buat distribusi donasi per bulan untuk setahun terakhir
        $this->createDistributedDonations($fakerID, $fakerEN, $donaturIds, $jenisDonasiData, $metodePembayaranIds, $fundraiserIds, $adminUserIds, $hambaAllahDonaturId);
    }
    
    /**
     * Memperbaiki format nomor HP donatur yang ada
     */
    private function fixDonatursPhoneNumbers(): void
    {
        $donaturs = Donatur::all();
        foreach ($donaturs as $donatur) {
            // Lewati donatur Hamba Allah
            if ($donatur->nama === 'Hamba Allah') {
                continue;
            }
            
            // Ubah format nomor HP menjadi dimulai dengan 8 (tanpa 0 atau 62)
            $phoneNumber = preg_replace('/^(\+62|62|0)/', '', $donatur->nomor_hp);
            
            // Jika nomor tidak dimulai dengan 8, ubah digit pertama menjadi 8
            if (!empty($phoneNumber) && substr($phoneNumber, 0, 1) !== '8') {
                $phoneNumber = '8' . substr($phoneNumber, 1);
            }
            
            // Jika nomor masih kosong atau tidak valid, buat nomor baru
            if (empty($phoneNumber) || strlen($phoneNumber) < 9) {
                $phoneNumber = '8' . rand(1, 9) . rand(1, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
            }
            
            $donatur->nomor_hp = $phoneNumber;
            $donatur->save();
        }
    }
    
    /**
     * Membuat donasi dengan distribusi yang menunjukkan pola naik-turun per bulan
     */
    private function createDistributedDonations($fakerID, $fakerEN, $donaturIds, $jenisDonasiData, $metodePembayaranIds, $fundraiserIds, $adminUserIds, $hambaAllahDonaturId): void
    {
        // Buat distribusi donasi untuk 12 bulan terakhir
        $now = Carbon::now();
        $startDate = $now->copy()->subMonths(11)->startOfMonth();
        
        // Jumlah donasi per bulan (pola naik-turun: bulan ganjil lebih tinggi)
        $donationsPerMonth = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $startDate->copy()->addMonths($i);
            $isOddMonth = $month->month % 2 === 1;
            
            // Bulan ganjil: 6-10 donasi, bulan genap: 3-5 donasi
            $donationsPerMonth[$month->format('Y-m')] = $isOddMonth ? 
                $fakerID->numberBetween(6, 10) : 
                $fakerID->numberBetween(3, 5);
        }
        
        // Total donasi yang akan dibuat
        $totalDonations = array_sum($donationsPerMonth);
        
        // Buat donasi untuk setiap bulan
        $donationCount = 0;
        foreach ($donationsPerMonth as $yearMonth => $count) {
            list($year, $month) = explode('-', $yearMonth);
            $monthStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            for ($i = 0; $i < $count; $i++) {
                $jenisDonasi = $jenisDonasiData->random();
                $isBarang = $jenisDonasi->apakah_barang;
                $membutuhkanKeterangan = $jenisDonasi->membutuhkan_keterangan_tambahan;

                $jumlah = $isBarang ? 0 : $fakerID->numberBetween(25000, 2000000);
                $perkiraanNilaiBarang = $isBarang ? $fakerID->numberBetween(50000, 1000000) : null;
                
                $deskripsiBarang = $isBarang ? 'Donasi berupa: ' . $fakerID->words(3, true) : null;
                
                $keteranganInfakKhusus = null;
                if ($membutuhkanKeterangan && !$isBarang && str_contains(strtolower($jenisDonasi->nama), 'khusus')) {
                    $keteranganInfakKhusus = 'Untuk program ' . $fakerID->word . ' ' . $fakerID->word;
                } elseif ($membutuhkanKeterangan && !$isBarang && str_contains(strtolower($jenisDonasi->nama), 'dskl')) {
                    $keteranganInfakKhusus = 'DSKL untuk ' . $fakerID->word;
                }

                $atasNamaHambaAllah = $fakerID->boolean(20);
                $donaturId = $atasNamaHambaAllah && $hambaAllahDonaturId ? $hambaAllahDonaturId : $fakerID->randomElement($donaturIds);

                // Tingkatkan persentase donasi dengan status "verified" menjadi 70-80%
                $statusOptions = ['verified', 'verified', 'verified', 'verified', 'verified', 'verified', 'verified', 'pending', 'pending', 'rejected'];
                $statusKonfirmasi = $fakerID->randomElement($statusOptions); // ~70% verified, 20% pending, 10% rejected
                
                $dikonfirmasiOlehUserId = null;
                $dikonfirmasiPada = null;
                if($statusKonfirmasi !== 'pending'){
                    $dikonfirmasiOlehUserId = $fakerID->randomElement($adminUserIds);
                    $dikonfirmasiPada = $fakerID->dateTimeBetween($monthStart, $monthEnd);
                }

                // Tanggal donasi dalam bulan yang sedang diproses
                $tanggalDonasi = $fakerID->dateTimeBetween($monthStart, $monthEnd);

                Donasi::create([
                    'donatur_id' => $donaturId,
                    'jenis_donasi_id' => $jenisDonasi->id,
                    'metode_pembayaran_id' => $fakerID->randomElement($metodePembayaranIds),
                    'fundraiser_id' => (count($fundraiserIds) > 0 && $fakerID->boolean(30)) ? $fakerID->randomElement($fundraiserIds) : null,
                    'jumlah' => $jumlah,
                    'perkiraan_nilai_barang' => $perkiraanNilaiBarang,
                    'deskripsi_barang' => $deskripsiBarang,
                    'keterangan_infak_khusus' => $keteranganInfakKhusus,
                    'bukti_pembayaran' => $fakerID->boolean(40) ? 'bukti_placeholder.jpg' : null,
                    'catatan_donatur' => $fakerID->boolean(25) ? $fakerID->sentence : null,
                    'tanggal_donasi' => $tanggalDonasi,
                    'atas_nama_hamba_allah' => $atasNamaHambaAllah,
                    'nomor_transaksi_unik' => 'TRX' . time() . $fakerID->unique()->randomNumber(5),
                    'status_konfirmasi' => $statusKonfirmasi,
                    'dikofirmasi_oleh_user_id' => $dikonfirmasiOlehUserId,
                    'dikonfirmasi_pada' => $dikonfirmasiPada,
                    'catatan_konfirmasi' => $statusKonfirmasi === 'rejected' ? $fakerID->sentence : null,
                    'dicatat_oleh_user_id' => $fakerID->randomElement($adminUserIds),
                ]);
                
                $donationCount++;
            }
        }
        
        // Tambahkan donasi acak untuk mencapai total 50 jika belum mencapai
        while ($donationCount < 50) {
            $jenisDonasi = $jenisDonasiData->random();
            $isBarang = $jenisDonasi->apakah_barang;
            $membutuhkanKeterangan = $jenisDonasi->membutuhkan_keterangan_tambahan;

            $jumlah = $isBarang ? 0 : $fakerID->numberBetween(25000, 2000000);
            $perkiraanNilaiBarang = $isBarang ? $fakerID->numberBetween(50000, 1000000) : null;
            
            $deskripsiBarang = $isBarang ? 'Donasi berupa: ' . $fakerID->words(3, true) : null;
            
            $keteranganInfakKhusus = null;
            if ($membutuhkanKeterangan && !$isBarang && str_contains(strtolower($jenisDonasi->nama), 'khusus')) {
                $keteranganInfakKhusus = 'Untuk program ' . $fakerID->word . ' ' . $fakerID->word;
            } elseif ($membutuhkanKeterangan && !$isBarang && str_contains(strtolower($jenisDonasi->nama), 'dskl')) {
                $keteranganInfakKhusus = 'DSKL untuk ' . $fakerID->word;
            }

            $atasNamaHambaAllah = $fakerID->boolean(20);
            $donaturId = $atasNamaHambaAllah && $hambaAllahDonaturId ? $hambaAllahDonaturId : $fakerID->randomElement($donaturIds);

            $statusOptions = ['verified', 'verified', 'verified', 'verified', 'verified', 'verified', 'verified', 'pending', 'pending', 'rejected'];
            $statusKonfirmasi = $fakerID->randomElement($statusOptions);
            
            $dikonfirmasiOlehUserId = null;
            $dikonfirmasiPada = null;
            if($statusKonfirmasi !== 'pending'){
                $dikonfirmasiOlehUserId = $fakerID->randomElement($adminUserIds);
                $dikonfirmasiPada = $fakerID->dateTimeThisYear();
            }

            Donasi::create([
                'donatur_id' => $donaturId,
                'jenis_donasi_id' => $jenisDonasi->id,
                'metode_pembayaran_id' => $fakerID->randomElement($metodePembayaranIds),
                'fundraiser_id' => (count($fundraiserIds) > 0 && $fakerID->boolean(30)) ? $fakerID->randomElement($fundraiserIds) : null,
                'jumlah' => $jumlah,
                'perkiraan_nilai_barang' => $perkiraanNilaiBarang,
                'deskripsi_barang' => $deskripsiBarang,
                'keterangan_infak_khusus' => $keteranganInfakKhusus,
                'bukti_pembayaran' => $fakerID->boolean(40) ? 'bukti_placeholder.jpg' : null,
                'catatan_donatur' => $fakerID->boolean(25) ? $fakerID->sentence : null,
                'tanggal_donasi' => $fakerID->dateTimeBetween('-1 year', 'now'),
                'atas_nama_hamba_allah' => $atasNamaHambaAllah,
                'nomor_transaksi_unik' => 'TRX' . time() . $fakerID->unique()->randomNumber(5),
                'status_konfirmasi' => $statusKonfirmasi,
                'dikofirmasi_oleh_user_id' => $dikonfirmasiOlehUserId,
                'dikonfirmasi_pada' => $dikonfirmasiPada,
                'catatan_konfirmasi' => $statusKonfirmasi === 'rejected' ? $fakerID->sentence : null,
                'dicatat_oleh_user_id' => $fakerID->randomElement($adminUserIds),
            ]);
            
            $donationCount++;
        }
    }
}

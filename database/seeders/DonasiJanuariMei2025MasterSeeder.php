<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class DonasiJanuariMei2025MasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Import semua data donasi dari Januari sampai Mei 2025
     */
    public function run(): void
    {
        $startTime = microtime(true);
        $this->command->info('=' . str_repeat('=', 70) . '=');
        $this->command->info('  IMPORT DONASI JANUARI - MEI 2025 (MASTER SEEDER)');
        $this->command->info('=' . str_repeat('=', 70) . '=');
        $this->command->newLine();

        // Array seeder yang akan dijalankan
        $seeders = [
            'DonasiJanuari2025OptimalSeeder' => 'Januari 2025',
            'DonasiFebruari2025Seeder' => 'Februari 2025',
            'DonasiMaret2025Seeder' => 'Maret 2025',
            'DonasiApril2025Seeder' => 'April 2025',
            'DonasiMei2025Seeder' => 'Mei 2025'
        ];

        $totalBerhasil = 0;
        $totalGagal = 0;
        $detailPerBulan = [];

        foreach ($seeders as $seederClass => $namaBulan) {
            $this->command->info("🔄 Mengimpor data {$namaBulan}...");
            
            $countSebelum = DB::table('donasis')->count();
            
            try {
                // Jalankan seeder
                $this->call($seederClass);
                
                $countSesudah = DB::table('donasis')->count();
                $jumlahBaru = $countSesudah - $countSebelum;
                
                $this->command->info("✅ {$namaBulan}: {$jumlahBaru} donasi berhasil diimpor");
                
                $totalBerhasil += $jumlahBaru;
                $detailPerBulan[$namaBulan] = $jumlahBaru;
                
            } catch (\Exception $e) {
                $this->command->error("❌ {$namaBulan}: Gagal - " . $e->getMessage());
                $totalGagal++;
            }
            
            $this->command->newLine();
        }

        // Hitung total donasi dan nominal
        $this->tampilkanRingkasan($detailPerBulan, $totalBerhasil, $totalGagal);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->command->newLine();
        $this->command->info("⏱️ Waktu eksekusi: {$duration} detik");
        $this->command->info('=' . str_repeat('=', 70) . '=');
    }

    /**
     * Tampilkan ringkasan hasil import
     */
    private function tampilkanRingkasan(array $detailPerBulan, int $totalBerhasil, int $totalGagal): void
    {
        $this->command->newLine();
        $this->command->info('📊 RINGKASAN HASIL IMPORT');
        $this->command->info('-' . str_repeat('-', 50) . '-');
        
        // Detail per bulan
        foreach ($detailPerBulan as $bulan => $jumlah) {
            $this->command->info("   {$bulan}: " . number_format($jumlah, 0, ',', '.') . " donasi");
        }
        
        $this->command->info('-' . str_repeat('-', 50) . '-');
        $this->command->info("   TOTAL DONASI BERHASIL: " . number_format($totalBerhasil, 0, ',', '.'));
        
        if ($totalGagal > 0) {
            $this->command->error("   TOTAL GAGAL: {$totalGagal} bulan");
        }
        
        $this->command->newLine();
        
        // Hitung total nominal donasi
        $this->hitungTotalNominal();
        
        // Statistik tambahan
        $this->tampilkanStatistikTambahan();
    }

    /**
     * Hitung dan tampilkan total nominal donasi
     */
    private function hitungTotalNominal(): void
    {
        try {
            // Total nominal semua donasi
            $totalNominal = DB::table('donasis')->sum('nominal');
            
            // Total donasi per jenis
            $totalPerJenis = DB::table('donasis')
                ->join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
                ->select('jenis_donasis.nama', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(donasis.nominal) as total'))
                ->groupBy('jenis_donasis.nama')
                ->orderBy('total', 'desc')
                ->get();

            $this->command->info('💰 RINGKASAN NOMINAL DONASI');
            $this->command->info('-' . str_repeat('-', 50) . '-');
            $this->command->info("   TOTAL KESELURUHAN: Rp " . number_format($totalNominal, 0, ',', '.'));
            
            $this->command->newLine();
            $this->command->info('📈 BREAKDOWN PER JENIS DONASI:');
            
            foreach ($totalPerJenis as $jenis) {
                $nominal = number_format($jenis->total, 0, ',', '.');
                $jumlah = number_format($jenis->jumlah, 0, ',', '.');
                $this->command->info("   {$jenis->nama}: Rp {$nominal} ({$jumlah} donasi)");
            }
            
        } catch (\Exception $e) {
            $this->command->error("❌ Gagal menghitung total nominal: " . $e->getMessage());
        }
    }

    /**
     * Tampilkan statistik tambahan
     */
    private function tampilkanStatistikTambahan(): void
    {
        try {
            $this->command->newLine();
            $this->command->info('📋 STATISTIK TAMBAHAN');
            $this->command->info('-' . str_repeat('-', 50) . '-');
            
            // Total donatur
            $totalDonatur = DB::table('donaturs')->count();
            $this->command->info("   Total Donatur: " . number_format($totalDonatur, 0, ',', '.'));
            
            // Donatur anonim
            $donaturAnonim = DB::table('donaturs')->where('is_anonim', true)->count();
            $this->command->info("   Donatur Anonim: " . number_format($donaturAnonim, 0, ',', '.'));
            
            // Total penyaluran
            $totalPenyaluran = DB::table('penyalurans')->count();
            $nominalPenyaluran = DB::table('penyalurans')->sum('nominal');
            $this->command->info("   Total Penyaluran: " . number_format($totalPenyaluran, 0, ',', '.') . 
                               " (Rp " . number_format($nominalPenyaluran, 0, ',', '.') . ")");
            
            // Metode pembayaran teratas
            $metodeTeratas = DB::table('donasis')
                ->join('metode_pembayarans', 'donasis.metode_pembayaran_id', '=', 'metode_pembayarans.id')
                ->select('metode_pembayarans.nama', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('metode_pembayarans.nama')
                ->orderBy('jumlah', 'desc')
                ->limit(3)
                ->get();
            
            $this->command->info("   Metode Pembayaran Teratas:");
            foreach ($metodeTeratas as $metode) {
                $this->command->info("     - {$metode->nama}: " . number_format($metode->jumlah, 0, ',', '.') . " donasi");
            }
            
            // Fundraiser teratas
            $fundraiserTeratas = DB::table('donasis')
                ->join('fundraisers', 'donasis.fundraiser_id', '=', 'fundraisers.id')
                ->select('fundraisers.nama', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('fundraisers.nama')
                ->orderBy('jumlah', 'desc')
                ->limit(3)
                ->get();
            
            $this->command->info("   Fundraiser Teratas:");
            foreach ($fundraiserTeratas as $fundraiser) {
                $this->command->info("     - {$fundraiser->nama}: " . number_format($fundraiser->jumlah, 0, ',', '.') . " donasi");
            }
            
        } catch (\Exception $e) {
            $this->command->error("❌ Gagal mengambil statistik tambahan: " . $e->getMessage());
        }
    }
}

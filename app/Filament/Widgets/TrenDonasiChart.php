<?php

namespace App\Filament\Widgets;

use App\Models\Donasi; // Pastikan model Donasi Anda sudah benar
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB; // Untuk query yang lebih kompleks jika perlu

class TrenDonasiChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Donasi (6 Bulan Terakhir)';

    protected static ?string $description = 'Menampilkan total donasi (uang dan nilai barang) terverifikasi per bulan.';

    // Untuk mengatur urutan widget di dashboard (opsional, angka lebih kecil tampil lebih dulu)
    protected static ?int $sort = 2; // Sesuaikan urutannya jika perlu

    

    // Interval refresh (opsional), misalnya '5s', '10s', '30s', '1m'
    // protected static ?string $pollingInterval = '30s';

    // Warna default untuk chart (opsional: 'primary', 'success', 'danger', 'warning', 'info', 'gray')
    // Anda juga bisa mengatur warna per dataset di getData()
    // protected ?string $chartColor = 'primary';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Ambil data untuk 6 bulan terakhir, termasuk bulan ini
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            // Menggunakan Carbon untuk format nama bulan dan tahun dalam Bahasa Indonesia
            // Jika locale Anda belum diatur ke 'id' di config/app.php, translatedFormat mungkin tidak bekerja sesuai harapan.
            // Alternatif: $month->format('M Y'); (misal: May 2025)
            $labels[] = $month->translatedFormat('F Y'); // Format: Mei 2025

            // Menghitung total donasi uang untuk bulan tersebut
            $totalUang = Donasi::where('status_konfirmasi', 'verified') // Hanya donasi yang sudah diverifikasi
                ->whereYear('tanggal_donasi', $month->year)
                ->whereMonth('tanggal_donasi', $month->month)
                ->sum('jumlah'); // Kolom jumlah donasi uang

            // Menghitung total perkiraan nilai barang untuk bulan tersebut
            $totalNilaiBarang = Donasi::where('status_konfirmasi', 'verified')
                ->whereYear('tanggal_donasi', $month->year)
                ->whereMonth('tanggal_donasi', $month->month)
                ->sum('perkiraan_nilai_barang'); // Kolom perkiraan nilai barang

            // Menjumlahkan total uang dan total nilai barang
            $data[] = $totalUang + $totalNilaiBarang;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Donasi Diterima', // Label untuk legenda dataset
                    'data' => $data, // Data numerik untuk grafik
                    'fill' => 'start', // Memberi area fill di bawah garis grafik
                    'borderColor' => 'rgb(59, 130, 246)', // Warna garis (biru Tailwind)
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)', // Warna area fill dengan transparansi
                    'tension' => 0.3, // Membuat garis sedikit melengkung (opsional)
                ],
                // Anda bisa menambahkan dataset lain di sini jika perlu, misalnya:
                // [
                //     'label' => 'Target Donasi',
                //     'data' => [5000000, 6000000, 5500000, 7000000, 6500000, 7500000], // Contoh data target
                //     'borderColor' => 'rgb(239, 68, 68)', // Warna garis merah
                // ],
            ],
            'labels' => $labels, // Label untuk sumbu X (bulan dan tahun)
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Tipe chart: 'line', 'bar', 'pie', 'doughnut', 'radar', 'polarArea'
    }

    // (Opsional) Atur tinggi chart jika defaultnya kurang sesuai
    // protected function getChartHeight(): ?string
    // {
    //     return '300px'; // Contoh tinggi 300 pixel
    // }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use App\Models\JenisDonasi; // Pastikan model ini ada dan relasinya benar
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KomposisiJenisDonasiChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Donasi berdasarkan Jenis';
    
    // Menggunakan filter standar Filament
    public ?string $filter = 'bulan_ini'; 

    protected static ?string $maxHeight = '380px'; // Sesuaikan tinggi chart
    protected static ?int $sort = 1; // Urutan widget jika ada beberapa
    // protected int | string | array $columnSpan = 'full'; // Jika ingin chart mengambil lebar penuh

    private array $chartGeneratedColors = [];

    protected function getFilters(): ?array
    {
        return [
            'bulan_ini' => '📅 Bulan Ini',
            'tahun_ini' => '🗓️ Tahun Ini',
            'keseluruhan' => '📊 Semua Waktu',
        ];
    }

    protected function generateColors(int $count): array
    {
        if ($count <= 0) {
            return [];
        }
        if (!empty($this->chartGeneratedColors) && count($this->chartGeneratedColors) === $count) {
            return $this->chartGeneratedColors;
        }

        $baseColors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', 
            '#06B6D4', '#84CC16', '#F97316', '#6366F1', '#14B8A6', '#F43F5E',
            '#A855F7', '#22C55E', '#FBBF24', '#FB7185', '#38BDF8', '#A3A3A3'
        ];
        
        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = $baseColors[$i % count($baseColors)];
        }
        
        $this->chartGeneratedColors = $colors;
        return $colors;
    }
    
    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $now = Carbon::now();
        $startDate = null;
        $endDate = null;

        switch ($activeFilter) {
            case 'tahun_ini':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;
            case 'keseluruhan':
                // Tidak ada filter tanggal
                break;
            case 'bulan_ini':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
        }

        // **!! VERIFIKASI NAMA KOLOM DI SINI !!**
        $kolomNamaJenisDonasiDiTabelJenis = 'jenis_donasis.nama'; 
        $kolomNominalDiTabelDonasi = 'donasis.jumlah';            
        $kolomIdJenisDonasiDiTabelDonasi = 'donasis.jenis_donasi_id'; 
        $kolomTanggalDiTabelDonasi = 'donasis.tanggal_donasi';      
        $kolomIdJenisDonasiDiTabelJenis = 'jenis_donasis.id';        
        // ******************************************

        $query = Donasi::query()
            ->join('jenis_donasis', $kolomIdJenisDonasiDiTabelDonasi, '=', $kolomIdJenisDonasiDiTabelJenis)
            ->select(
                $kolomNamaJenisDonasiDiTabelJenis . ' as nama_jenis',
                DB::raw('SUM(' . $kolomNominalDiTabelDonasi . ') as total_nominal')
            );

        if ($startDate && $endDate) {
            $query->whereBetween($kolomTanggalDiTabelDonasi, [$startDate, $endDate]);
        }

        $dataDonasi = $query->groupBy($kolomIdJenisDonasiDiTabelJenis, $kolomNamaJenisDonasiDiTabelJenis) 
            ->orderBy('total_nominal', 'desc')
            ->get();

        $labels = $dataDonasi->pluck('nama_jenis')->toArray();
        $dataValues = $dataDonasi->pluck('total_nominal')->map(fn($val) => (float) $val)->toArray();

        if (empty($labels) || empty($dataValues)) {
            return [
                'datasets' => [['label' => 'Jenis Donasi', 'data' => [], 'backgroundColor' => []]],
                'labels' => [], 
            ];
        }
        
        $backgroundColors = $this->generateColors(count($labels));
        $hoverBackgroundColors = array_map(function($color) {
            return $this->adjustHexColorBrightness($color, -20); // Warna hover sedikit lebih gelap
        }, $backgroundColors);

        return [
            'datasets' => [
                [
                    'label' => 'Total Nominal per Jenis Donasi',
                    'data' => $dataValues,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => '#FFFFFF', 
                    'borderWidth' => 2, // Border lebih tebal untuk definisi
                    'hoverOffset' => 8, // Segmen membesar saat hover
                    'hoverBackgroundColor' => $hoverBackgroundColors, // Warna berbeda saat hover
                    'hoverBorderColor' => '#FFFFFF', 
                    'hoverBorderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function adjustHexColorBrightness(string $hex, int $steps): string 
    {
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
        }
        $r = max(0,min(255,hexdec(substr($hex,0,2)) + $steps));
        $g = max(0,min(255,hexdec(substr($hex,2,2)) + $steps));
        $b = max(0,min(255,hexdec(substr($hex,4,2)) + $steps));
        return '#'.str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
                 .str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
                 .str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    protected function getType(): string
    {
        // Anda bisa ganti ke 'doughnut' jika lebih suka
        return 'pie'; 
    }

    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'animation' => [
                'animateScale' => true,
                'animateRotate' => true,
                'duration' => 1000,
                'easing' => 'easeOutQuart',
            ],
            'plugins' => [
                'legend' => [
                    'display' => true, 
                    'position' => 'bottom', 
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20, // Jarak lebih antar item legenda
                        'boxWidth' => 10, 
                        'font' => ['size' => 11], // Font legenda lebih besar
                    ],
                    'onClick' => null, // Nonaktifkan klik pada legenda untuk toggle dataset
                ],
                'tooltip' => [
                    'enabled' => true,
                    'backgroundColor' => 'rgba(31, 41, 55, 0.92)', // Warna tooltip lebih pekat
                    'titleColor' => '#F3F4F6', // Warna judul tooltip
                    'titleFont' => ['size' => 13, 'weight' => 'bold'],
                    'bodyColor' => '#E5E7EB', // Warna body tooltip
                    'bodyFont' => ['size' => 12],
                    'borderColor' => 'rgba(71, 85, 105, 0.5)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8, // Corner lebih bulat
                    'padding' => 12, // Padding lebih besar
                    'boxPadding' => 4, 
                    'displayColors' => true, // Tampilkan kotak warna di tooltip
                    'usePointStyle' => true, // Gunakan titik untuk style di tooltip
                ],
            ],
            // Menghilangkan skala sumbu X dan Y untuk Pie/Doughnut Chart
            'scales' => [], // Gunakan array kosong untuk Pie chart
        ];
    }

    // Tambahkan method ini untuk mengatur callback tooltip jika diperlukan
    protected function getExtraJsCode(): string
    {
        return '
        window.chartOptions = window.chartOptions || {};
        window.chartOptions.plugins = window.chartOptions.plugins || {};
        window.chartOptions.plugins.tooltip = window.chartOptions.plugins.tooltip || {};
        window.chartOptions.plugins.tooltip.callbacks = {
            label: function(context) {
                let label = context.label || "";
                if (label) {
                    label += ": ";
                }
                if (context.parsed !== null) {
                    label += new Intl.NumberFormat("id-ID", {
                        style: "currency",
                        currency: "IDR",
                        minimumFractionDigits: 0
                    }).format(context.parsed);
                }
                return label;
            },
            afterLabel: function(context) {
                let total = 0;
                context.dataset.data.forEach(function(value) {
                    total += value;
                });
                if (total === 0) return "(0%)";
                const percentage = ((context.parsed / total) * 100).toFixed(1);
                return "Persentase: " + percentage + "%";
            }
        };
        ';
    }
}
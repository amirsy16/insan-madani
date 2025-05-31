<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Tetap import Log jika Anda membutuhkannya untuk debugging lain

class TrenPenerimaanDonasiChart extends ChartWidget
{
    protected static ?string $heading = '📈 Tren Penerimaan Donasi';
    public ?string $filter = 'satu_bulan'; // Filter periode default baru
    protected static ?string $maxHeight = '350px';
    protected static ?int $sort = 2;

    protected function getFilters(): ?array
    {
        return [
            'satu_bulan' => '📅 1 Bulan Terakhir (Harian)',
            'enam_bulan' => '🗓️ 6 Bulan Terakhir (Mingguan)',
            'satu_tahun' => '📊 1 Tahun Terakhir (Bulanan)',
        ];
    }

    /**
     * =======================================================================================
     * !! VERIFIKASI NAMA KOLOM !! DI BAGIAN BAWAH INI SESUAI DENGAN DATABASE ANDA!
     * =======================================================================================
     */
    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $now = Carbon::now();
        $data = [];
        $labels = [];

        // **!! VERIFIKASI NAMA KOLOM DI SINI !!**
        $kolomNominalDonasi = 'donasis.jumlah'; // Contoh: 'donasis.nominal' atau 'jumlah' jika tidak ada prefix tabel
        $kolomTanggalDonasi = 'donasis.tanggal_donasi'; // Contoh: 'donasis.created_at' atau 'tanggal_donasi'
        // ******************************************

        // Helper untuk mendapatkan nama kolom tanpa prefix tabel (jika ada)
        $rawKolomNominalDonasi = $this->getRawColumnName($kolomNominalDonasi);
        $rawKolomTanggalDonasi = $this->getRawColumnName($kolomTanggalDonasi);

        switch ($activeFilter) {
            case 'satu_bulan': // Tren Harian selama 30 hari terakhir
                $endDate = $now->copy()->endOfDay();
                $startDate = $now->copy()->subDays(29)->startOfDay();

                $donasiData = Donasi::query()
                    ->select(
                        DB::raw("DATE({$kolomTanggalDonasi}) as periode"), // Group by tanggal
                        DB::raw("SUM({$rawKolomNominalDonasi}) as total_nominal")
                    )
                    ->whereBetween($kolomTanggalDonasi, [$startDate, $endDate])
                    ->groupBy('periode')
                    ->orderBy('periode', 'asc')
                    ->pluck('total_nominal', 'periode'); // Ambil sebagai [periode => total_nominal]

                $period = CarbonPeriod::create($startDate, '1 day', $endDate);
                foreach ($period as $date) {
                    $labels[] = $date->isoFormat('D MMM');
                    $data[] = (float) ($donasiData[$date->format('Y-m-d')] ?? 0);
                }
                break;

            case 'enam_bulan': // Tren Mingguan selama 6 bulan terakhir
                $endDate = $now->copy()->endOfDay();
                // Mengambil data dari awal bulan 6 bulan lalu hingga hari ini untuk cakupan minggu yang lebih baik
                $startDate = $now->copy()->subMonthsNoOverflow(5)->startOfMonth()->startOfDay();


                $donasiDalamPeriode = Donasi::query()
                    ->whereBetween($kolomTanggalDonasi, [$startDate, $endDate])
                    ->select($rawKolomTanggalDonasi, $rawKolomNominalDonasi) // Pilih kolom yang dibutuhkan
                    ->orderBy($rawKolomTanggalDonasi, 'asc')
                    ->get()
                    ->map(function ($item) use ($rawKolomTanggalDonasi, $rawKolomNominalDonasi) {
                        // Pastikan tanggal adalah objek Carbon dan nominal adalah float
                        return [
                            'tanggal' => Carbon::parse($item->{$rawKolomTanggalDonasi}),
                            'nominal' => (float) $item->{$rawKolomNominalDonasi},
                        ];
                    });

                $currentPeriodStart = $startDate->copy();
                while ($currentPeriodStart->lte($endDate)) {
                    $startOfWeek = $currentPeriodStart->copy()->startOfWeek(Carbon::MONDAY);
                    $endOfWeek = $currentPeriodStart->copy()->endOfWeek(Carbon::SUNDAY);

                    // Batasi awal dan akhir minggu agar tidak keluar dari rentang 6 bulan
                    if ($startOfWeek->lt($startDate)) $startOfWeek = $startDate->copy();
                    if ($endOfWeek->gt($endDate)) $endOfWeek = $endDate->copy();
                    
                    // Hanya proses jika startOfWeek masih dalam rentang atau sama dengan endDate
                    if ($startOfWeek->gt($endDate)) break;


                    $totalMingguan = $donasiDalamPeriode->filter(function ($donasi) use ($startOfWeek, $endOfWeek) {
                        return $donasi['tanggal']->betweenIncluded($startOfWeek, $endOfWeek);
                    })->sum('nominal');

                    $labelMinggu = $startOfWeek->isoFormat('D MMM');
                    if (!$startOfWeek->isSameDay($endOfWeek) && $startOfWeek->diffInDays($endOfWeek) > 0) {
                         // Hanya tampilkan rentang jika berbeda hari dan tidak melewati end date utama
                        $labelMinggu .= ' - ' . $endOfWeek->isoFormat('D MMM');
                    }
                    $labels[] = $labelMinggu;
                    $data[] = (float) $totalMingguan;

                    if ($endOfWeek->gte($endDate)) {
                        break;
                    }
                    $currentPeriodStart = $endOfWeek->copy()->addDay();
                     if ($currentPeriodStart->gt($endDate)) {
                        break;
                    }
                }
                break;

            case 'satu_tahun': // Tren Bulanan selama 12 bulan terakhir
                $endDate = $now->copy()->endOfMonth();
                $startDate = $now->copy()->subMonthsNoOverflow(11)->startOfMonth();

                $donasiData = Donasi::query()
                    ->select(
                        DB::raw("DATE_FORMAT({$kolomTanggalDonasi}, '%Y-%m') as periode"),
                        DB::raw("SUM({$rawKolomNominalDonasi}) as total_nominal")
                    )
                    ->whereBetween($kolomTanggalDonasi, [$startDate, $endDate])
                    ->groupBy('periode')
                    ->orderBy('periode', 'asc')
                    ->pluck('total_nominal', 'periode');

                $period = CarbonPeriod::create($startDate, '1 month', $endDate);
                foreach ($period as $date) {
                    $labels[] = $date->isoFormat('MMM YY');
                    $data[] = (float) ($donasiData[$date->format('Y-m')] ?? 0);
                }
                break;
        }

        if (empty($labels) || empty($data)) {
            // Jika tidak ada data sama sekali setelah pemrosesan, tampilkan pesan
            $labels = ['Tidak ada data untuk periode ini'];
            $data = [0]; // Berikan satu titik data agar chart tidak error
             return [
                'datasets' => [['label' => 'Penerimaan Donasi', 'data' => $data, 'borderColor' => '#94A3B8', 'backgroundColor' => 'rgba(148, 163, 184, 0.1)']],
                'labels' => $labels,
            ];
        }


        return [
            'datasets' => [
                [
                    'label' => 'Total Penerimaan Donasi',
                    'data' => $data,
                    'borderColor' => '#2563EB',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.3,
                    'pointRadius' => 2, // Sedikit terlihat agar ada titik interaksi
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#2563EB',
                    'pointBorderColor' => '#FFFFFF',
                    'pointHitRadius' => 20,
                    'pointHoverBorderWidth' => 2,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function getRawColumnName(string $columnExpression): string
    {
        if (strpos($columnExpression, '.') !== false) {
            $parts = explode('.', $columnExpression);
            return end($parts);
        }
        return $columnExpression;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'layout' => [
                'padding' => [
                    'top' => 10,
                    'right' => 20,
                    'left' => 10, // Kurangi padding kiri jika label Y-axis memakan tempat
                    'bottom' => 5
                ]
            ],
            'animation' => [
                'duration' => 800,
                'easing' => 'easeInOutSine',
            ],
            'plugins' => [
                'legend' => [
                    'display' => false, // Label dataset sudah ada di tooltip
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index', // Menampilkan tooltip untuk semua dataset di indeks X yang sama
                    'intersect' => false, // Tooltip akan muncul meskipun tidak tepat di atas titik
                    'backgroundColor' => 'rgba(17, 24, 39, 0.92)', 
                    'titleColor' => '#E5E7EB', 
                    'titleFont' => ['size' => 12, 'weight' => '600', 'family' => 'Inter, system-ui, sans-serif'],
                    'bodyColor' => '#D1D5DB', 
                    'bodyFont' => ['size' => 11, 'family' => 'Inter, system-ui, sans-serif'],
                    'borderColor' => 'rgba(55, 65, 81, 0.5)', 
                    'borderWidth' => 1,
                    'cornerRadius' => 6,
                    'padding' => 10,
                    'displayColors' => false, 
                    'boxPadding' => 3,
                    // Bagian callbacks dihilangkan sementara untuk menggunakan default Chart.js
                    // Jika chart menjadi interaktif, masalah ada di callback kustom Anda.
                    // 'callbacks' => [
                    //     'title' => 'function(tooltipItems) { return tooltipItems[0].label; }',
                    //     'label' => 'function(context) { let label = context.dataset.label || ""; if (label) { label += ": "; } if (context.parsed.y !== null) { label += new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(context.parsed.y); } return label; }',
                    // ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false, 
                    'grid' => [
                        'color' => 'rgba(209, 213, 219, 0.1)', 
                        'borderDash' => [3, 3], 
                        'drawBorder' => false, 
                    ],
                    'ticks' => [
                        'color' => '#6B7280', 
                        'padding' => 10,
                        'font' => ['size' => 10, 'family' => 'Inter, system-ui, sans-serif'],
                        // Callback untuk format ticks Y-axis dihilangkan sementara
                        // 'callback' => 'function(value) { if (value === 0) return "0"; return new Intl.NumberFormat("id-ID", { notation: "compact", compactDisplay: "short", minimumFractionDigits:0, maximumFractionDigits:1 }).format(value); }',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false, 
                    ],
                    'ticks' => [
                        'color' => '#4B5569', 
                        'font' => ['size' => 10, 'family' => 'Inter, system-ui, sans-serif'],
                        'maxRotation' => 0, 
                        'minRotation' => 0,
                        'autoSkip' => true, 
                        'maxTicksLimit' => 7, 
                        'padding' => 10, 
                    ],
                ],
            ],
            'interaction' => [ 
                'mode' => 'index',
                'intersect' => false,
                'axis' => 'x', 
            ],
        ];
    }
}


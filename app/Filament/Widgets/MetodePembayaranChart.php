<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MetodePembayaranChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Metode Pembayaran';

    protected static ?string $description = 'Menampilkan komposisi donasi berdasarkan metode pembayaran.';

    protected static ?int $sort = 3;

        // protected int | string | array $columnSpan = 6;

    public ?string $filter = 'bulan_ini';

     protected static bool $isDiscovered = false;

    protected function getFilters(): ?array
    {
        return [
            'bulan_ini' => 'Bulan Ini',
            'tahun_ini' => 'Tahun Ini',
            'keseluruhan' => 'Semua Waktu',
        ];
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

        $query = Donasi::query()
            ->join('metode_pembayarans', 'donasis.metode_pembayaran_id', '=', 'metode_pembayarans.id')
            ->where('donasis.status_konfirmasi', 'verified')
            ->select(
                'metode_pembayarans.nama as nama_metode',
                DB::raw('SUM(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) as total_nominal')
            );

        if ($startDate && $endDate) {
            $query->whereBetween('donasis.tanggal_donasi', [$startDate, $endDate]);
        }

        $dataMetode = $query->groupBy('metode_pembayarans.id', 'metode_pembayarans.nama')
            ->orderBy('total_nominal', 'desc')
            ->get();

        $labels = $dataMetode->pluck('nama_metode')->toArray();
        $dataValues = $dataMetode->pluck('total_nominal')->map(fn($val) => (float) $val)->toArray();

        if (empty($labels) || empty($dataValues)) {
            return [
                'datasets' => [['label' => 'Metode Pembayaran', 'data' => [], 'backgroundColor' => []]],
                'labels' => [], 
            ];
        }

        $backgroundColors = $this->generateColors(count($labels));
        $hoverBackgroundColors = array_map(function($color) {
            return $this->adjustHexColorBrightness($color, -20);
        }, $backgroundColors);

        return [
            'datasets' => [
                [
                    'label' => 'Total Nominal per Metode Pembayaran',
                    'data' => $dataValues,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => '#FFFFFF',
                    'borderWidth' => 2,
                    'hoverOffset' => 8,
                    'hoverBackgroundColor' => $hoverBackgroundColors,
                    'hoverBorderColor' => '#FFFFFF',
                    'hoverBorderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function generateColors(int $count): array
    {
        if ($count <= 0) {
            return [];
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
        
        return $colors;
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
                        'padding' => 20,
                        'boxWidth' => 10, 
                        'font' => ['size' => 11],
                    ],
                    'onClick' => null,
                ],
                'tooltip' => [
                    'enabled' => true,
                    'backgroundColor' => 'rgba(31, 41, 55, 0.92)',
                    'titleColor' => '#F3F4F6',
                    'titleFont' => ['size' => 13, 'weight' => 'bold'],
                    'bodyColor' => '#E5E7EB',
                    'bodyFont' => ['size' => 12],
                    'borderColor' => 'rgba(71, 85, 105, 0.5)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'padding' => 12,
                    'boxPadding' => 4, 
                    'displayColors' => true,
                    'usePointStyle' => true,
                ],
            ],
            'scales' => [],
        ];
    }
}
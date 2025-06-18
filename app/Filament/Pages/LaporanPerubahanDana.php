<?php

namespace App\Filament\Pages;

use App\Services\DanaService;
use App\Models\Asnaf;
use App\Models\BidangProgram;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\Enums\Format;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileDownloads\FileDownloadConfiguration;
use function Spatie\LaravelPdf\Support\pdf;

class LaporanPerubahanDana extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.laporan-perubahan-dana';
    protected static ?string $navigationGroup = 'Laporan & Keuangan';
    protected static ?string $slug = 'Laporan-perubahan-dana';
    protected static ?int $navigationSort = 2;

    public ?array $reportData = [];
    public ?string $startDate = null;
    public ?string $endDate = null;
    public array $asnafList = [];
    public array $bidangProgramList = [];
    public array $penggunaanHakAmil = [];
    public float $totalPenggunaanHakAmil = 0;
    public array $penerimaanHakAmilDetail = [];
    public float $totalPenerimaanHakAmil = 0;
    public array $penggunaanHakAmilDetail = [];
    public float $surplusDefisitHakAmil = 0;
    public array $summaryData = [];
    public float $totalSisaSaldo = 0;
    public array $financialStatus = [];

    /**
     * Dijalankan saat halaman pertama kali dimuat.
     * Mengisi form dengan tanggal default (awal dan akhir tahun ini)
     * dan langsung menghitung data laporan.
     */
    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfYear()->format('Y-m-d');

        $this->asnafList = Asnaf::where('aktif', true)->pluck('nama_asnaf')->toArray();
        $this->bidangProgramList = BidangProgram::where('aktif', true)->pluck('nama_bidang')->toArray();

        // Ambil data laporan hak amil dari DanaService
        $danaService = new DanaService();
        $laporanHakAmil = $danaService->getLaporanHakAmil($this->startDate, $this->endDate);
        $this->penerimaanHakAmilDetail = $laporanHakAmil['penerimaan_detail'];
        $this->totalPenerimaanHakAmil = $laporanHakAmil['total_penerimaan'];
        $this->penggunaanHakAmilDetail = $laporanHakAmil['penggunaan_detail'];
        $this->totalPenggunaanHakAmil = $laporanHakAmil['total_penggunaan'];
        $this->surplusDefisitHakAmil = $laporanHakAmil['surplus_defisit'];

        $this->generateReport();
    }

    /**
     * Mendefinisikan schema form untuk filter tanggal.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('startDate')
                    ->label('Tanggal Mulai')
                    ->required(),
                DatePicker::make('endDate')
                    ->label('Tanggal Akhir')
                    ->required(),
            ])->columns(2);
    }

    /**
     * Method yang dipanggil saat tombol "Generate Laporan" ditekan.
     * Akan memanggil method calculateReport untuk memuat data baru.
     */
    public function generateReport(): void
    {
        $state = $this->form->getState();
        $this->startDate = $state['startDate'];
        $this->endDate = $state['endDate'];

        if (Carbon::parse($this->startDate)->isAfter(Carbon::parse($this->endDate))) {
            Notification::make()
                ->title('Tanggal mulai tidak boleh setelah tanggal akhir')
                ->danger()
                ->send();
            return;
        }

        $danaService = new DanaService();
        $this->reportData = $danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);
        
        // Ambil data summary jika ada
        if (isset($this->reportData['summary'])) {
            $this->summaryData = $this->reportData['summary'];
            $this->totalSisaSaldo = $this->summaryData['total_saldo_akhir'] ?? 0;
            $this->financialStatus = $this->getFinancialStatus();
        }
        
        // Update data hak amil setiap kali filter diganti
        $laporanHakAmil = $danaService->getLaporanHakAmil($this->startDate, $this->endDate);
        $this->penerimaanHakAmilDetail = $laporanHakAmil['penerimaan_detail'];
        $this->totalPenerimaanHakAmil = $laporanHakAmil['total_penerimaan'];
        $this->penggunaanHakAmilDetail = $laporanHakAmil['penggunaan_detail'];
        $this->totalPenggunaanHakAmil = $laporanHakAmil['total_penggunaan'];
        $this->surplusDefisitHakAmil = $laporanHakAmil['surplus_defisit'];
    }

    /**
     * Get financial status based on total remaining balance
     */
    public function getFinancialStatus(): array
    {
        $balance = $this->totalSisaSaldo;
        
        if ($balance >= 1000000) {
            return [
                'status' => 'SEHAT',
                'message' => 'Saldo mencukupi untuk operasional',
                'icon' => '✅',
                'color_class' => 'bg-green-100 border-green-300 text-green-800 dark:bg-green-900 dark:border-green-600 dark:text-green-200'
            ];
        } elseif ($balance >= 0) {
            return [
                'status' => 'PERLU PERHATIAN',
                'message' => 'Saldo terbatas',
                'icon' => '⚠️',
                'color_class' => 'bg-yellow-100 border-yellow-300 text-yellow-800 dark:bg-yellow-900 dark:border-yellow-600 dark:text-yellow-200'
            ];
        } else {
            return [
                'status' => 'DEFISIT',
                'message' => 'Diperlukan tindakan segera',
                'icon' => '❌',
                'color_class' => 'bg-red-100 border-red-300 text-red-800 dark:bg-red-900 dark:border-red-600 dark:text-red-200'
            ];
        }
    }

    /**
     * Format currency without decimal
     */
    public function formatCurrency($amount): string
    {
        return 'Rp ' . number_format($amount ?? 0, 0, ',', '.');
    }

    /**
     * Get color class for positive/negative amounts
     */
    public function getAmountColorClass($amount): string
    {
        return ($amount >= 0) ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    }

    /**
     * Download PDF report
     */    public function downloadPDF()
    {
        try {
            // Ensure we have current data
            if (empty($this->reportData)) {
                $this->generateReport();
            }
            
            // Check if we still don't have data after generating
            if (empty($this->reportData)) {
                Notification::make()
                    ->title('Tidak ada data')
                    ->body('Silakan generate laporan terlebih dahulu sebelum download PDF.')
                    ->warning()
                    ->send();
                return null;
            }

            $data = [
                'reportData' => $this->reportData,
                'summaryData' => $this->summaryData,
                'totalSisaSaldo' => $this->totalSisaSaldo,
                'penerimaanHakAmilDetail' => $this->penerimaanHakAmilDetail,
                'totalPenerimaanHakAmil' => $this->totalPenerimaanHakAmil,
                'penggunaanHakAmilDetail' => $this->penggunaanHakAmilDetail,
                'totalPenggunaanHakAmil' => $this->totalPenggunaanHakAmil,
                'surplusDefisitHakAmil' => $this->surplusDefisitHakAmil,
                'detailPengeluaranAsnaf' => $this->detailPengeluaranAsnaf ?? [],
                'detailPengeluaranBidangProgram' => $this->detailPengeluaranBidangProgram ?? [],
                'detailJenisPenggunaanAmil' => $this->detailJenisPenggunaanAmil ?? [],
                'detailDanaNonHalal' => $this->detailDanaNonHalal ?? [],
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'generatedAt' => now()->format('d/m/Y H:i:s')
            ];

            $filename = 'Laporan_Perubahan_Dana_' . Carbon::parse($this->startDate)->format('d-m-Y') . '_sd_' . Carbon::parse($this->endDate)->format('d-m-Y') . '.pdf';
            
            Log::info('Attempting to generate PDF with filename: ' . $filename);
            
            // Create temporary file path
            $tempPath = storage_path('app/temp/');
            if (!is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }
            
            $fullTempPath = $tempPath . $filename;
            
            // Generate and save PDF to temporary file
            Pdf::view('laporan.pdf.perubahan-dana-clean', $data)
                ->format('a4')
                ->save($fullTempPath);
                
            Log::info('PDF saved to: ' . $fullTempPath);
            
            // Return file as download and delete after sending
            return response()->download($fullTempPath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage());
            Log::error('PDF Error Stack Trace: ' . $e->getTraceAsString());
            
            Notification::make()
                ->title('Error generating PDF')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
                
            return null;
        }
    }

    /**
     * Header actions for the page
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPDF')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action('downloadPDF')
                ->tooltip('Download laporan dalam format PDF'),
        ];
    }
}
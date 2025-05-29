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
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;

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
        // Update data hak amil setiap kali filter diganti
        $laporanHakAmil = $danaService->getLaporanHakAmil($this->startDate, $this->endDate);
        $this->penerimaanHakAmilDetail = $laporanHakAmil['penerimaan_detail'];
        $this->totalPenerimaanHakAmil = $laporanHakAmil['total_penerimaan'];
        $this->penggunaanHakAmilDetail = $laporanHakAmil['penggunaan_detail'];
        $this->totalPenggunaanHakAmil = $laporanHakAmil['total_penggunaan'];
        $this->surplusDefisitHakAmil = $laporanHakAmil['surplus_defisit'];
    }

    /**
     * Tombol untuk mencetak laporan
     */
    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('print')
    //             ->label('Cetak Laporan')
    //             ->icon('heroicon-o-printer')
    //             ->url(fn () => route('laporan.perubahan-dana.print', [
    //                 'startDate' => $this->startDate,
    //                 'endDate' => $this->endDate,
    //             ]))
    //             ->openUrlInNewTab(),
    //     ];
    // }
}
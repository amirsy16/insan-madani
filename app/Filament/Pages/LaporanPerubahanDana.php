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
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 2;

    public ?array $reportData = [];
    public ?string $startDate = null;
    public ?string $endDate = null;
    public array $asnafList = [];
    public array $bidangProgramList = [];

    /**
     * Dijalankan saat halaman pertama kali dimuat.
     * Mengisi form dengan tanggal default (awal dan akhir tahun ini)
     * dan langsung menghitung data laporan.
     */
    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfYear()->format('Y-m-d');

        $this->form->fill([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);

        // Ambil daftar asnaf dan bidang program untuk referensi di view
        $this->asnafList = Asnaf::where('aktif', true)->pluck('nama_asnaf')->toArray();
        $this->bidangProgramList = BidangProgram::where('aktif', true)->pluck('nama_bidang')->toArray();

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

        // Validasi tanggal
        if (Carbon::parse($this->startDate)->isAfter(Carbon::parse($this->endDate))) {
            Notification::make()
                ->title('Tanggal mulai tidak boleh setelah tanggal akhir')
                ->danger()
                ->send();
                
            return;
        }

        // Panggil service untuk mengambil data laporan
        $this->reportData = (new DanaService())->getLaporanPerubahanDana($this->startDate, $this->endDate);
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



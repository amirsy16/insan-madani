<?php

namespace App\Filament\Imports;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\JenisDonasi;
use App\Models\MetodePembayaran;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class DonasiImporter extends Importer
{
    protected static ?string $model = Donasi::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('donatur')
                ->relationship('donatur', 'nama')
                ->label('Donatur')
                ->rules(['required'])
                ->example('Kasiran Winarsih'),
            ImportColumn::make('jenisDonasi')
                ->requiredMapping()
                ->relationship('jenisDonasi', 'nama')
                ->label('Jenis Donasi')
                ->rules(['required'])
                ->example('Sedekah'),
            ImportColumn::make('metodePembayaran')
                ->relationship('metodePembayaran', 'nama')
                ->label('Metode Pembayaran')
                ->example('DANA'),
            ImportColumn::make('jumlah')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric'])
                ->example('750000'),
            ImportColumn::make('tanggal_donasi')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->label('Tanggal Donasi')
                ->example('2025-06-09'),        ];
    }

    public function resolveRecord(): ?Donasi
    {
        // Generate nomor transaksi unik jika belum ada
        $nomorTransaksi = $this->data['nomor_transaksi_unik'] ?? 'TRX' . strtoupper(uniqid());
        
        // Cari donasi berdasarkan nomor transaksi atau buat baru
        $donasi = Donasi::firstOrNew([
            'nomor_transaksi_unik' => $nomorTransaksi,        ], [
            // Default values untuk record baru
            'atas_nama_hamba_allah' => false,
            'dicatat_oleh_user_id' => Auth::id(),
        ]);

        // Resolve donatur berdasarkan identifier
        if (isset($this->data['donatur']) && $this->data['donatur']) {
            $donaturId = $this->resolveDonatur($this->data['donatur']);
            if ($donaturId) {
                $donasi->donatur_id = $donaturId;
            }
        }

        // Resolve jenis donasi
        if (isset($this->data['jenisDonasi']) && $this->data['jenisDonasi']) {
            $jenisDonasi = JenisDonasi::where('nama', 'like', '%' . $this->data['jenisDonasi'] . '%')->first();
            if ($jenisDonasi) {
                $donasi->jenis_donasi_id = $jenisDonasi->id;
            }
        }

        // Resolve metode pembayaran
        if (isset($this->data['metodePembayaran']) && $this->data['metodePembayaran']) {
            $metodePembayaran = MetodePembayaran::where('nama', 'like', '%' . $this->data['metodePembayaran'] . '%')->first();
            if ($metodePembayaran) {
                $donasi->metode_pembayaran_id = $metodePembayaran->id;
            }
        }

        return $donasi;
    }

    private function resolveDonatur(string $identifier): ?int
    {
        // Coba cari berdasarkan kode donatur terlebih dahulu
        $donatur = Donatur::where('kode_donatur', $identifier)->first();
        
        if (!$donatur) {
            // Jika tidak ditemukan, coba berdasarkan email
            $donatur = Donatur::where('email', $identifier)->first();
        }
        
        if (!$donatur) {
            // Jika masih tidak ditemukan, coba berdasarkan nomor HP
            $donatur = Donatur::where('nomor_hp', $identifier)->first();
        }
        
        return $donatur?->id;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your donasi import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}

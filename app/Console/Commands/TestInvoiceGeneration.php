<?php

namespace App\Console\Commands;

use App\Models\Donasi;
use App\Services\InvoiceDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestInvoiceGeneration extends Command
{
    protected $signature = 'invoice:test {donasi_id?}';
    
    protected $description = 'Test invoice generation for a donation';

    public function handle(InvoiceDeliveryService $invoiceService)
    {
        $donasiId = $this->argument('donasi_id');
        
        if (!$donasiId) {
            // Ambil donasi verified pertama
            $donasi = Donasi::where('status_konfirmasi', 'verified')
                           ->with(['donatur', 'jenisDonasi', 'metodePembayaran'])
                           ->first();
        } else {
            $donasi = Donasi::with(['donatur', 'jenisDonasi', 'metodePembayaran'])
                           ->find($donasiId);
        }
        
        if (!$donasi) {
            $this->error('Donasi tidak ditemukan atau belum terverifikasi');
            return 1;
        }
        
        $this->info("Testing invoice generation untuk:");
        $this->line("- Donasi ID: {$donasi->id}");
        $this->line("- No. Transaksi: {$donasi->nomor_transaksi_unik}");
        $this->line("- Donatur: " . ($donasi->atas_nama_hamba_allah ? 'Hamba Allah' : $donasi->donatur->nama));
        $this->line("- Jumlah: Rp " . number_format($donasi->jumlah, 0, ',', '.'));
        
        // Tentukan metode delivery berdasarkan data donatur
        $deliveryMethod = $invoiceService->determineDeliveryMethod($donasi->donatur, $donasi);
        $this->line("- Metode terpilih: {$deliveryMethod}");
        
        // Test invoice generation
        $this->info("\nMengenerate invoice...");
        
        try {
            $result = $invoiceService->processInvoiceDelivery(
                $donasi, 
                $deliveryMethod, 
                'Test invoice generation dari command'
            );
            
            if ($result['success']) {
                $this->info("✅ Invoice berhasil digenerate!");
                $this->line("Pesan: {$result['message']}");
                
                if (isset($result['invoice_record'])) {
                    $invoice = $result['invoice_record'];
                    $this->line("Nomor Invoice: {$invoice->nomor_invoice}");
                    $this->line("Status: {$invoice->delivery_status}");
                    $this->line("File PDF: {$invoice->pdf_file_path}");
                    
                    if (isset($result['download_url'])) {
                        $this->line("Download URL: {$result['download_url']}");
                    }
                }
            } else {
                $this->error("❌ Invoice gagal digenerate!");
                $this->line("Error: {$result['message']}");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception occurred: " . $e->getMessage());
            Log::error('Invoice test failed', [
                'donasi_id' => $donasi->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return 0;
    }
}

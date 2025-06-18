<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\InvoiceDonasi;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Exception;

class InvoicePdfService
{
    /**
     * Generate PDF invoice untuk donasi
     */
    public function generateInvoicePDF(Donasi $donasi): string
    {
        try {
            // Ensure relationships are loaded
            $donasi->load(['donatur', 'jenisDonasi', 'metodePembayaran']);
            
            // Generate invoice number
            $invoiceNumber = InvoiceDonasi::generateNomorInvoice($donasi);
            
            // Prepare data untuk PDF
            $data = $this->prepareInvoiceData($donasi, $invoiceNumber);
            
            // Generate PDF using Spatie PDF
            $pdf = Pdf::view('invoices.donasi-form', $data)
                ->format('a5')
                ->orientation('landscape')
                ->margins(5, 5, 5, 5);
            
            // Generate filename
            $filename = 'invoice-' . $donasi->nomor_transaksi_unik . '.pdf';
            $path = 'invoices/' . $filename;
            
            // Ensure directory exists
            Storage::disk('public')->makeDirectory('invoices');
            
            // Save PDF to storage
            Storage::disk('public')->put($path, base64_decode($pdf->base64()));
            
            $fullPath = Storage::disk('public')->path($path);
            
            Log::info('Invoice PDF generated successfully', [
                'donasi_id' => $donasi->id,
                'invoice_number' => $invoiceNumber,
                'file_path' => $fullPath,
            ]);
            
            return $fullPath;
            
        } catch (Exception $e) {
            Log::error('Invoice PDF generation failed', [
                'donasi_id' => $donasi->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Download PDF invoice untuk donasi
     */
    public function downloadInvoicePDF(Donasi $donasi)
    {
        try {
            // Ensure relationships are loaded
            $donasi->load(['donatur', 'jenisDonasi', 'metodePembayaran']);
            
            // Generate invoice number
            $invoiceNumber = InvoiceDonasi::generateNomorInvoice($donasi);
            
            // Prepare data untuk PDF
            $data = $this->prepareInvoiceData($donasi, $invoiceNumber);
            
            // Generate PDF using Spatie PDF
            $pdf = Pdf::view('invoices.donasi-form-compact', $data)
                ->format('a5')
                ->orientation('landscape')
                ->margins(5, 5, 5, 5);
            
            // Generate filename
            $filename = 'invoice-' . $donasi->nomor_transaksi_unik . '.pdf';
            
            Log::info('Invoice PDF download requested', [
                'donasi_id' => $donasi->id,
                'invoice_number' => $invoiceNumber,
                'filename' => $filename,
            ]);
            
            // Get PDF content as base64 and decode to binary
            $pdfContent = base64_decode($pdf->base64());
            
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
            
        } catch (Exception $e) {
            Log::error('Invoice PDF download failed', [
                'donasi_id' => $donasi->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Stream PDF invoice untuk ditampilkan di browser
     */
    public function streamInvoicePDF(Donasi $donasi)
    {
        try {
            // Ensure relationships are loaded
            $donasi->load(['donatur', 'jenisDonasi', 'metodePembayaran']);
            
            // Generate invoice number
            $invoiceNumber = InvoiceDonasi::generateNomorInvoice($donasi);
            
            // Prepare data untuk PDF
            $data = $this->prepareInvoiceData($donasi, $invoiceNumber);
            
            // Generate PDF using Spatie PDF
            $pdf = Pdf::view('invoices.donasi-form-compact', $data)
                ->format('a5')
                ->orientation('landscape')
                ->margins(5, 5, 5, 5);
            
            // Generate filename
            $filename = 'invoice-' . $donasi->nomor_transaksi_unik . '.pdf';
            
            Log::info('Invoice PDF stream requested', [
                'donasi_id' => $donasi->id,
                'invoice_number' => $invoiceNumber,
                'filename' => $filename,
            ]);
            
            // Get PDF content as base64 and decode to binary
            $pdfContent = base64_decode($pdf->base64());
            
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
            
        } catch (Exception $e) {
            Log::error('Invoice PDF stream failed', [
                'donasi_id' => $donasi->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Prepare data untuk invoice template
     */
    private function prepareInvoiceData(Donasi $donasi, string $invoiceNumber): array
    {
        return [
            'invoice_number' => $invoiceNumber,
            'donasi' => $donasi,
            'donatur' => $donasi->donatur,
            'tanggal_invoice' => now()->format('d F Y'),
            'tanggal_donasi' => $donasi->tanggal_donasi->format('d F Y'),
            'jumlah_formatted' => 'Rp ' . number_format($donasi->jumlah, 0, ',', '.'),
            'metode_pembayaran' => $donasi->metodePembayaran?->nama ?? 'Tidak diketahui',
            'jenis_donasi' => $donasi->jenisDonasi?->nama ?? 'Donasi Umum',
            'nomor_transaksi' => $donasi->nomor_transaksi_unik,
            'keterangan' => $donasi->keterangan ?? '',
            'organisasi' => [
                'nama' => config('app.organization_name', 'Laz Insan Madani Jambi'),
                'alamat' => config('app.organization_address', 'Jl. Contoh No. 123, Jakarta'),
                'telepon' => config('app.organization_phone', '021-1234567'),
                'email' => config('app.organization_email', 'info@amalkit.org'),
                'website' => config('app.organization_website', 'www.amalkit.org'),
            ],
        ];
    }
    
    /**
     * Get invoice template path
     */
    public function getTemplatePath(): string
    {
        return resource_path('views/invoices/donasi-form.blade.php');
    }
    
    /**
     * Check if template exists, create if not
     */
    public function ensureTemplateExists(): void
    {
        $templatePath = $this->getTemplatePath();
        
        if (!file_exists($templatePath)) {
            $this->createDefaultTemplate();
        }
    }
    
    /**
     * Create default invoice template
     */
    private function createDefaultTemplate(): void
    {
        $templateDir = dirname($this->getTemplatePath());
        
        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0755, true);
        }
        
        $templateContent = $this->getDefaultTemplateContent();
        file_put_contents($this->getTemplatePath(), $templateContent);
    }
    
    /**
     * Get default template content
     */
    private function getDefaultTemplateContent(): string
    {
        return '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .org-info {
            text-align: center;
        }
        .org-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .org-details {
            font-size: 12px;
            color: #666;
        }
        .invoice-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 30px 0;
            color: #2563eb;
        }
        .invoice-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .info-value {
            margin-bottom: 15px;
        }
        .donation-details {
            background-color: #f8fafc;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .detail-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .detail-label {
            display: table-cell;
            width: 40%;
            font-weight: bold;
        }
        .detail-value {
            display: table-cell;
            width: 60%;
        }
        .amount-highlight {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
        .thank-you {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="org-info">
            <div class="org-name">{{ $organisasi[\'nama\'] }}</div>
            <div class="org-details">
                {{ $organisasi[\'alamat\'] }}<br>
                Tel: {{ $organisasi[\'telepon\'] }} | Email: {{ $organisasi[\'email\'] }} | {{ $organisasi[\'website\'] }}
            </div>
        </div>
    </div>

    <div class="invoice-title">INVOICE DONASI</div>

    <div class="invoice-info">
        <div class="info-left">
            <div class="info-label">Nomor Invoice:</div>
            <div class="info-value">{{ $invoice_number }}</div>
            
            <div class="info-label">Tanggal Invoice:</div>
            <div class="info-value">{{ $tanggal_invoice }}</div>
            
            <div class="info-label">Nomor Transaksi:</div>
            <div class="info-value">{{ $nomor_transaksi }}</div>
        </div>
        <div class="info-right">
            <div class="info-label">Donatur:</div>
            <div class="info-value">
                @if($donasi->atas_nama_hamba_allah)
                    Hamba Allah (Anonim)
                @else
                    {{ $donatur->nama }}<br>
                    @if($donatur->alamat_lengkap && $donatur->alamat_lengkap !== \'-\')
                        {{ $donatur->alamat_lengkap }}<br>
                    @endif
                    @if($donatur->nomor_hp)
                        HP: {{ $donatur->nomor_hp }}<br>
                    @endif
                    @if($donatur->email)
                        Email: {{ $donatur->email }}
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="donation-details">
        <div class="detail-row">
            <div class="detail-label">Jenis Donasi:</div>
            <div class="detail-value">{{ $jenis_donasi }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Tanggal Donasi:</div>
            <div class="detail-value">{{ $tanggal_donasi }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Metode Pembayaran:</div>
            <div class="detail-value">{{ $metode_pembayaran }}</div>
        </div>
        @if($keterangan)
        <div class="detail-row">
            <div class="detail-label">Keterangan:</div>
            <div class="detail-value">{{ $keterangan }}</div>
        </div>
        @endif
        <div class="detail-row">
            <div class="detail-label">Jumlah Donasi:</div>
            <div class="detail-value amount-highlight">{{ $jumlah_formatted }}</div>
        </div>
    </div>

    <div class="footer">
        <div class="thank-you">Jazakallahu Khairan Katsiran</div>
        <p>Terima kasih atas kepercayaan dan dukungan Anda.<br>
        Semoga amal kebaikan ini menjadi investasi terbaik di akhirat.</p>
        
        <p><em>Invoice ini digenerate secara otomatis pada {{ now()->format(\'d F Y H:i:s\') }}</em></p>
    </div>
</body>
</html>';
    }
}

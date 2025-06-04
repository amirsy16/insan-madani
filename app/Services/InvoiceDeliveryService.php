<?php

namespace App\Services;

use App\Models\Donatur;
use App\Models\Donasi;
use App\Models\InvoiceDonasi;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class InvoiceDeliveryService
{
    protected InvoicePdfService $pdfService;
    
    public function __construct(InvoicePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }
    /**
     * Tentukan metode pengiriman terbaik berdasarkan data donatur
     */
    public function determineDeliveryMethod(Donatur $donatur, Donasi $donasi): string
    {
        // Priority 1: Email jika tersedia dan valid
        if (!empty($donatur->email) && filter_var($donatur->email, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        
        // Priority 2: SMS jika nomor HP tersedia (WhatsApp sementara dinonaktifkan)
        if (!empty($donatur->nomor_hp)) {
            return 'sms';
        }
        
        // Priority 3: Download manual (selalu tersedia)
        return 'download';
    }
    
    /**
     * Dapatkan semua metode pengiriman yang tersedia untuk donatur
     */
    public function getAvailableDeliveryMethods(Donatur $donatur): array
    {
        $methods = ['download']; // Selalu tersedia
        
        if (!empty($donatur->email) && filter_var($donatur->email, FILTER_VALIDATE_EMAIL)) {
            $methods[] = 'email';
        }
        
        if (!empty($donatur->nomor_hp)) {
            // $methods[] = 'whatsapp'; // Akan diaktifkan setelah membeli layanan WhatsApp blast
            $methods[] = 'sms';
        }
        
        // Untuk donasi tunai di kantor
        $methods[] = 'print';
        
        return $methods;
    }
    
    /**
     * Format metode pengiriman untuk UI
     */
    public function formatDeliveryMethodsForUI(Donatur $donatur): array
    {
        $methods = [];
        
        // Download manual - selalu tersedia
        $methods['download'] = 'Download Manual';
        
        // Email jika tersedia
        if (!empty($donatur->email) && filter_var($donatur->email, FILTER_VALIDATE_EMAIL)) {
            $methods['email'] = 'Email: ' . $donatur->email;
        }
        
        // WhatsApp dan SMS jika nomor HP tersedia
        if (!empty($donatur->nomor_hp)) {
            $formattedPhone = '62' . ltrim($donatur->nomor_hp, '0');
            // $methods['whatsapp'] = 'WhatsApp: ' . $formattedPhone; // Akan diaktifkan setelah membeli layanan WhatsApp blast
            $methods['sms'] = 'SMS: ' . $formattedPhone;
        }
        
        // Print untuk pickup
        $methods['print'] = 'Print untuk Pickup';
        
        return $methods;
    }
    
    /**
     * Validasi apakah metode pengiriman valid untuk donatur
     */
    public function isDeliveryMethodValid(string $method, Donatur $donatur): bool
    {
        return match($method) {
            'email' => !empty($donatur->email) && filter_var($donatur->email, FILTER_VALIDATE_EMAIL),
            // 'whatsapp', 'sms' => !empty($donatur->nomor_hp), // WhatsApp sementara dinonaktifkan
            'sms' => !empty($donatur->nomor_hp),
            'download', 'print' => true,
            default => false,
        };
    }
    
    /**
     * Generate message berdasarkan metode pengiriman
     */
    public function generateDeliveryMessage(string $method, Donatur $donatur, string $invoiceNumber, ?string $notes = null): string
    {
        $message = match($method) {
            'email' => "Invoice {$invoiceNumber} akan dikirim ke email: {$donatur->email}",
            // 'whatsapp' => "Invoice {$invoiceNumber} akan dikirim via WhatsApp ke: 62" . ltrim($donatur->nomor_hp, '0'), // Akan diaktifkan setelah membeli layanan WhatsApp blast
            'sms' => "Invoice {$invoiceNumber} akan dikirim via SMS ke: 62" . ltrim($donatur->nomor_hp, '0'),
            'download' => "Invoice {$invoiceNumber} siap untuk didownload",
            'print' => "Invoice {$invoiceNumber} akan dicetak untuk pickup",
            default => "Invoice {$invoiceNumber} telah diproses"
        };
        
        if (!empty($notes)) {
            $message .= "\nCatatan: " . $notes;
        }
        
        return $message;
    }
    
    /**
     * Simulasi pengiriman email (placeholder untuk implementasi sebenarnya)
     */
    public function sendViaEmail(Donatur $donatur, string $invoicePath, string $invoiceNumber): bool
    {
        try {
            // Get the donasi record and invoice record for email
            $donasi = Donasi::whereHas('invoiceDonasi', function($query) use ($invoiceNumber) {
                $query->where('nomor_invoice', $invoiceNumber);
            })->first();
            
            $invoiceDonasi = InvoiceDonasi::where('nomor_invoice', $invoiceNumber)->first();
            
            if (!$donasi || !$invoiceDonasi) {
                throw new Exception('Donasi atau invoice record tidak ditemukan');
            }
            
            // Send email using Laravel's mail system
            \Illuminate\Support\Facades\Mail::to($donatur->email)
                ->send(new \App\Mail\InvoiceDonasiMail($donasi, $invoiceDonasi, $invoicePath));
            
            Log::info('Invoice email sent successfully', [
                'donatur_id' => $donatur->id,
                'email' => $donatur->email,
                'invoice_number' => $invoiceNumber,
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Invoice email failed', [
                'donatur_id' => $donatur->id,
                'email' => $donatur->email,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Simulasi pengiriman WhatsApp (placeholder untuk implementasi sebenarnya)
     */
    public function sendViaWhatsApp(Donatur $donatur, string $invoicePath, string $invoiceNumber): bool
    {
        try {
            // TODO: Implementasi WhatsApp Business API
            $phone = '62' . ltrim($donatur->nomor_hp, '0');
            $message = "Terima kasih atas donasi Anda, {$donatur->nama}. Invoice terlampir. Barakallahu fiikum 🤲";
            
            // Contoh implementasi WhatsApp API call
            // $whatsapp = new WhatsAppBusinessAPI();
            // $result = $whatsapp->sendDocument([
            //     'to' => $phone,
            //     'document' => $invoicePath,
            //     'caption' => $message,
            //     'filename' => 'Invoice_Donasi.pdf'
            // ]);
            
            Log::info('Invoice WhatsApp sent', [
                'donatur_id' => $donatur->id,
                'phone' => $phone,
                'invoice_number' => $invoiceNumber,
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Invoice WhatsApp failed', [
                'donatur_id' => $donatur->id,
                'phone' => $donatur->nomor_hp,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Simulasi pengiriman SMS (placeholder untuk implementasi sebenarnya)
     */
    public function sendViaSms(Donatur $donatur, string $invoiceUrl, string $invoiceNumber): bool
    {
        try {
            // TODO: Implementasi SMS Gateway
            $phone = '62' . ltrim($donatur->nomor_hp, '0');
            $message = "Terima kasih atas donasi Anda. Invoice dapat didownload di: {$invoiceUrl}";
            
            // Contoh implementasi SMS Gateway
            // $sms = new SmsGateway();
            // $result = $sms->send($phone, $message);
            
            Log::info('Invoice SMS sent', [
                'donatur_id' => $donatur->id,
                'phone' => $phone,
                'invoice_number' => $invoiceNumber,
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Invoice SMS failed', [
                'donatur_id' => $donatur->id,
                'phone' => $donatur->nomor_hp,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Handle download manual
     */
    public function prepareForDownload(string $invoicePath, string $invoiceNumber): array
    {
        return [
            'success' => true,
            'download_url' => url("storage/invoices/{$invoiceNumber}.pdf"),
            'message' => 'Invoice siap untuk didownload',
        ];
    }
    
    /**
     * Handle print untuk pickup
     */
    public function markForPrint(string $invoiceNumber): array
    {
        Log::info('Invoice marked for print', [
            'invoice_number' => $invoiceNumber,
            'timestamp' => now(),
        ]);
        
        return [
            'success' => true,
            'message' => 'Invoice telah disiapkan untuk dicetak',
        ];
    }
    
    /**
     * Process complete invoice delivery
     */
    public function processInvoiceDelivery(
        Donasi $donasi, 
        string $deliveryMethod, 
        ?string $notes = null
    ): array {
        try {
            // Ensure relationships are loaded
            $donasi->load(['donatur', 'jenisDonasi', 'metodePembayaran']);
            
            // Validate delivery method
            if (!$this->isDeliveryMethodValid($deliveryMethod, $donasi->donatur)) {
                throw new Exception('Metode pengiriman tidak valid untuk donatur ini');
            }
            
            // Generate invoice number
            $invoiceNumber = InvoiceDonasi::generateNomorInvoice($donasi);
            
            // Generate PDF
            $pdfPath = $this->pdfService->generateInvoicePDF($donasi);
            
            // Create invoice record
            $invoiceDonasi = InvoiceDonasi::create([
                'donasi_id' => $donasi->id,
                'nomor_invoice' => $invoiceNumber,
                'delivery_method' => $deliveryMethod,
                'delivery_status' => 'pending',
                'sent_to_email' => $deliveryMethod === 'email' ? $donasi->donatur->email : null,
                'sent_to_phone' => $deliveryMethod === 'sms' ? $donasi->donatur->nomor_hp : null, // WhatsApp sementara dinonaktifkan
                'delivery_notes' => $notes,
                'pdf_file_path' => $pdfPath,
                'created_by_user_id' => Auth::id(),
            ]);
            
            // Execute delivery
            $result = $this->executeDelivery($deliveryMethod, $donasi->donatur, $pdfPath, $invoiceNumber);
            
            // Update delivery status
            if ($result['success']) {
                $invoiceDonasi->markAsSent();
                if ($deliveryMethod === 'email') {
                    $invoiceDonasi->markAsDelivered(); // Email adalah instant delivery
                }
            } else {
                $invoiceDonasi->markAsFailed($result['error'] ?? 'Unknown error');
            }
            
            return [
                'success' => $result['success'],
                'invoice_record' => $invoiceDonasi,
                'message' => $this->generateDeliveryMessage($deliveryMethod, $donasi->donatur, $invoiceNumber, $notes),
                'download_url' => $invoiceDonasi->download_url ?? null,
            ];
            
        } catch (Exception $e) {
            Log::error('Invoice delivery processing failed', [
                'donasi_id' => $donasi->id,
                'delivery_method' => $deliveryMethod,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Gagal memproses invoice: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Execute actual delivery based on method
     */
    private function executeDelivery(string $method, Donatur $donatur, string $pdfPath, string $invoiceNumber): array
    {
        return match($method) {
            'email' => ['success' => $this->sendViaEmail($donatur, $pdfPath, $invoiceNumber)],
            // 'whatsapp' => ['success' => $this->sendViaWhatsApp($donatur, $pdfPath, $invoiceNumber)], // Akan diaktifkan setelah membeli layanan WhatsApp blast
            'sms' => ['success' => $this->sendViaSms($donatur, url('storage/invoices/' . basename($pdfPath)), $invoiceNumber)],
            'download' => $this->prepareForDownload($pdfPath, $invoiceNumber),
            'print' => $this->markForPrint($invoiceNumber),
            default => ['success' => false, 'error' => 'Unknown delivery method']
        };
    }
}

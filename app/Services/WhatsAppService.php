<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiKey;
    protected string $numberKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.watzap.api_key', 'ILOZ2ZNXJIQILIAX');
        $this->numberKey = config('services.watzap.number_key', 'GL9jBTuExuUDzncU');
        $this->baseUrl = 'https://api.watzap.id/v1';
    }

    /**
     * Check API status
     */
    public function checkApiStatus(): array
    {
        try {
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for development
                'timeout' => 30,
            ])->post("{$this->baseUrl}/checking_key", [
                'api-key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data,
                    'message' => 'API status check successful'
                ];
            }

            return [
                'success' => false,
                'message' => 'API check failed: ' . $response->body(),
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WatZap API status check failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'API check exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate WhatsApp number
     */
    public function validateNumber(string $phoneNumber): array
    {
        try {
            // Format phone number (ensure it starts with 62)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for development
                'timeout' => 30,
            ])->post("{$this->baseUrl}/validate_number", [
                'api_key' => $this->apiKey,
                'number_key' => $this->numberKey,
                'phone_no' => $formattedPhone,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data,
                    'is_valid' => $data['status'] ?? false,
                    'message' => $data['message'] ?? 'Number validation completed'
                ];
            }

            return [
                'success' => false,
                'message' => 'Number validation failed: ' . $response->body(),
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WatZap number validation failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Number validation exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send text message
     */
    public function sendMessage(string $phoneNumber, string $message, bool $waitUntilSend = false): array
    {
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $payload = [
                'api_key' => $this->apiKey,
                'number_key' => $this->numberKey,
                'phone_no' => $formattedPhone,
                'message' => $message,
            ];

            // Don't wait for send completion to avoid timeout
            // if ($waitUntilSend) {
            //     $payload['wait_until_send'] = '1';
            // }

            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for development
                'timeout' => 20, // Reduced timeout
                'connect_timeout' => 10, // Add connection timeout
            ])->post("{$this->baseUrl}/send_message", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('WatZap message sent successfully', [
                    'phone' => $formattedPhone,
                    'message_length' => strlen($message),
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'data' => $data,
                    'message' => 'Message sent successfully'
                ];
            }

            Log::warning('WatZap message send failed', [
                'phone' => $formattedPhone,
                'response' => $response->body(),
                'status_code' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => 'Message send failed: ' . $response->body(),
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WatZap message send exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Message send exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send file via URL
     */
    public function sendFile(string $phoneNumber, string $fileUrl, string $caption = ''): array
    {
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $payload = [
                'api_key' => $this->apiKey,
                'number_key' => $this->numberKey,
                'phone_no' => $formattedPhone,
                'url' => $fileUrl,
            ];

            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for development
                'timeout' => 25, // Reduced timeout for file sending
                'connect_timeout' => 10, // Add connection timeout
            ])->post("{$this->baseUrl}/send_file_url", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('WatZap file sent successfully', [
                    'phone' => $formattedPhone,
                    'file_url' => $fileUrl,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'data' => $data,
                    'message' => 'File sent successfully'
                ];
            }

            Log::warning('WatZap file send failed', [
                'phone' => $formattedPhone,
                'file_url' => $fileUrl,
                'response' => $response->body(),
                'status_code' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => 'File send failed: ' . $response->body(),
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('WatZap file send exception', [
                'phone' => $phoneNumber,
                'file_url' => $fileUrl,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'File send exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send invoice via WhatsApp
     */
    public function sendInvoice(string $phoneNumber, string $invoiceUrl, array $invoiceData): array
    {
        try {
            // Create message text
            $message = $this->createInvoiceMessage($invoiceData);
            
            // Send message without waiting for completion to avoid timeout
            $messageResult = $this->sendMessage($phoneNumber, $message, false);
            
            if (!$messageResult['success']) {
                Log::error('WatZap message send failed, skipping file send', [
                    'phone' => $phoneNumber,
                    'message_error' => $messageResult['message']
                ]);
                return $messageResult;
            }

            // Add small delay before sending file
            sleep(3);

            // Then send the PDF file
            $fileResult = $this->sendFile($phoneNumber, $invoiceUrl);
            
            // Log detailed results
            Log::info('WatZap invoice send completed', [
                'phone' => $phoneNumber,
                'invoice_url' => $invoiceUrl,
                'message_success' => $messageResult['success'],
                'file_success' => $fileResult['success'],
                'file_error' => $fileResult['success'] ? null : $fileResult['message']
            ]);
            
            // Return success even if file failed, but log the file failure
            if (!$fileResult['success']) {
                Log::error('WatZap file send failed after successful message', [
                    'phone' => $phoneNumber,
                    'invoice_url' => $invoiceUrl,
                    'file_error' => $fileResult['message'],
                    'file_status_code' => $fileResult['status_code'] ?? null
                ]);
            }
            
            return [
                'success' => $messageResult['success'], // Success based on message + file
                'message_result' => $messageResult,
                'file_result' => $fileResult,
                'message' => $fileResult['success'] 
                    ? 'Invoice message and PDF sent successfully via WhatsApp'
                    : 'Invoice message sent, but PDF failed: ' . $fileResult['message']
            ];

        } catch (Exception $e) {
            Log::error('WatZap invoice send exception', [
                'phone' => $phoneNumber,
                'invoice_url' => $invoiceUrl,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Invoice send exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to ensure it starts with 62
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Remove leading zeros
        $phone = ltrim($phone, '0');
        
        // Add 62 if not already there
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

    /**
     * Create invoice message for WhatsApp
     */
    private function createInvoiceMessage(array $invoiceData): string
    {
        $donaturName = $invoiceData['atas_nama_hamba_allah'] ? 'Hamba Allah' : $invoiceData['donatur_nama'];
        $jenisDonasi = $invoiceData['jenis_donasi'] ?? 'Donasi';
        $jumlah = number_format($invoiceData['jumlah'], 0, ',', '.');
        $tanggalDonasi = $invoiceData['tanggal_donasi'] ?? date('Y-m-d');
        $nomorInvoice = $invoiceData['nomor_invoice'] ?? '';
        $organisasi = config('app.organization_name', 'Yayasan');

        return "🌙 *Assalamu'alaikum Warahmatullahi Wabarakatuh*

Kepada Yth. *{$donaturName}*,

Alhamdulillahi rabbil 'alamiin, terima kasih atas kepercayaan dan donasi Anda kepada {$organisasi}.

📋 *Detail Donasi:*
• No. Invoice: {$nomorInvoice}
• Jenis Donasi: {$jenisDonasi}
• Jumlah: Rp {$jumlah}
• Tanggal: {$tanggalDonasi}

📄 Invoice PDF akan dikirim dalam pesan terpisah.

جَزَاكَ اللهُ خَيْرًا كَثِيْرًا
*Jazakallahu Khairan Katsiran*

Semoga Allah membalas kebaikan Anda dengan kebaikan yang berlimpah.

Wassalamu'alaikum Warahmatullahi Wabarakatuh

---
{$organisasi}
Menyalurkan Amanah dengan Amanah";
    }

    /**
     * Test WhatsApp connection
     */
    public function testConnection(): array
    {
        return $this->checkApiStatus();
    }
}

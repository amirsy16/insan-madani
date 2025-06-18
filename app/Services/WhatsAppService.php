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

            // Add wait flag if requested
            if ($waitUntilSend) {
                $payload['wait_until_send'] = '1';
            }

            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for development
                'timeout' => $waitUntilSend ? 60 : 15, // Longer timeout if waiting
                'connect_timeout' => 5,
            ])->post("{$this->baseUrl}/send_message", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('WatZap message sent successfully', [
                    'phone' => $formattedPhone,
                    'message_length' => strlen($message),
                    'wait_until_send' => $waitUntilSend,
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
                'verify' => false,
                'timeout' => 30, // Standard timeout for file sending
                'connect_timeout' => 5,
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
     * Send invoice via WhatsApp - Sequential delivery (message first, then PDF)
     */
    public function sendInvoice(string $phoneNumber, string $invoiceUrl, array $invoiceData): array
    {
        try {
            Log::info('WhatsApp invoice delivery started', [
                'phone' => $phoneNumber,
                'invoice_url' => $invoiceUrl
            ]);
            
            // Step 1: Send text message first
            $message = $this->createInvoiceMessage($invoiceData);
            $messageResult = $this->sendMessage($phoneNumber, $message, true);
            
            if (!$messageResult['success']) {
                Log::error('WhatsApp message failed', [
                    'phone' => $phoneNumber,
                    'error' => $messageResult['message']
                ]);
                
                return [
                    'success' => false,
                    'message_result' => $messageResult,
                    'file_result' => null,
                    'message' => 'Message delivery failed: ' . $messageResult['message']
                ];
            }
            
            // Step 2: Brief wait before sending file
            sleep(3);
            
            // Step 3: Send PDF file
            $fileResult = $this->sendFile($phoneNumber, $invoiceUrl);
            
            Log::info('WhatsApp invoice delivery completed', [
                'phone' => $phoneNumber,
                'message_success' => $messageResult['success'],
                'file_success' => $fileResult['success']
            ]);
            
            return [
                'success' => $messageResult['success'] && $fileResult['success'],
                'message_result' => $messageResult,
                'file_result' => $fileResult,
                'message' => 'Invoice sent via WhatsApp'
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp invoice send exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message_result' => null,
                'file_result' => null,
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
        $nomorInvoice = $invoiceData['nomor_invoice'] ?? '';
        $organisasi = config('app.organization_name', 'LAZ Insan Madani Jambi');
        
        // Format tanggal dalam bahasa Indonesia
        $tanggalDonasi = $invoiceData['tanggal_donasi'] ?? date('Y-m-d');
        $formattedDate = date('d F Y', strtotime($tanggalDonasi));
        
        // Prioritaskan nilai barang jika ada, jika tidak gunakan jumlah donasi
        $jumlahDisplay = 0;
        if (isset($invoiceData['perkiraan_nilai_barang']) && $invoiceData['perkiraan_nilai_barang'] > 0) {
            $jumlahDisplay = $invoiceData['perkiraan_nilai_barang'];
        } else {
            $jumlahDisplay = $invoiceData['jumlah'] ?? 0;
        }
        $jumlah = number_format($jumlahDisplay, 0, ',', '.');

        return "🌙 *Assalamu'alaikum Warahmatullahi Wabarakatuh*

Kepada Yth. *{$donaturName}*,

Alhamdulillahi rabbil 'alamiin, terima kasih atas kepercayaan dan donasi Anda kepada {$organisasi}. Donasi Anda telah tercatat kedalam sistem manajemen donasi kami.

📋 *Detail Donasi:*
• No. Invoice: {$nomorInvoice}
• Jenis Donasi: {$jenisDonasi}
• Jumlah: Rp {$jumlah}
• Tanggal: {$formattedDate}

📄 Invoice PDF akan dikirim dalam pesan terpisah.

جَزَاكَ اللهُ خَيْرًا كَثِيْرًا
*Jazakallahu Khairan Katsiran*

\"Semoga Allah melimpahkan ganjaran pahala terhadap harta yang telah diberikan dan semoga Allah memberkahi harta yang masih tersisa, serta semoga Allah menjadikan dirimu suci dan bersih\".
Aamiin yaa Rabbal 'Alaamiin

Wassalamu'alaikum Warahmatullahi Wabarakatuh

---
{$organisasi}
Ditangan Kami Donasi Anda Lebih Berarti.";
    }

    /**
     * Test WhatsApp connection
     */
    public function testConnection(): array
    {
        return $this->checkApiStatus();
    }
}

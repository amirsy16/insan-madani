<?php

namespace App\Jobs;

use App\Models\Donatur;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $donatur;
    protected $invoiceUrl;
    protected $invoiceData;
    protected $invoiceNumber;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run (optimized for speed).
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(Donatur $donatur, string $invoiceUrl, array $invoiceData, string $invoiceNumber)
    {
        $this->donatur = $donatur;
        $this->invoiceUrl = $invoiceUrl;
        $this->invoiceData = $invoiceData;
        $this->invoiceNumber = $invoiceNumber;
    }

    /**
     * Execute the job with message-first delivery order and speed optimization.
     */
    public function handle(WhatsAppService $whatsappService): void
    {
        try {
            Log::info('Starting message-first WhatsApp invoice job', [
                'donatur_id' => $this->donatur->id,
                'invoice_number' => $this->invoiceNumber,
                'invoice_url' => $this->invoiceUrl,
                'phone' => $this->donatur->nomor_hp,
                'delivery_strategy' => 'Message fully processed → then PDF'
            ]);

            $result = $whatsappService->sendInvoice($this->donatur->nomor_hp, $this->invoiceUrl, $this->invoiceData);

            if ($result['success']) {
                Log::info('WhatsApp invoice job completed successfully', [
                    'donatur_id' => $this->donatur->id,
                    'invoice_number' => $this->invoiceNumber,
                    'message_success' => $result['message_result']['success'] ?? false,
                    'file_success' => $result['file_result']['success'] ?? false,
                    'delivery_time' => $result['delivery_time'] ?? 'unknown',
                    'delivery_strategy' => 'Message fully processed first → then PDF (guaranteed order)'
                ]);
                
                // Log if PDF failed but message succeeded (new priority)
                if (isset($result['file_result']) && !$result['file_result']['success']) {
                    Log::warning('WhatsApp invoice: message sent but PDF failed', [
                        'donatur_id' => $this->donatur->id,
                        'invoice_number' => $this->invoiceNumber,
                        'file_error' => $result['file_result']['message'],
                    ]);
                }
            } else {
                Log::error('WhatsApp invoice job failed', [
                    'donatur_id' => $this->donatur->id,
                    'invoice_number' => $this->invoiceNumber,
                    'error' => $result['message'],
                ]);
                
                // Retry the job if it failed
                $this->fail($result['message']);
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp invoice job exception', [
                'donatur_id' => $this->donatur->id,
                'invoice_number' => $this->invoiceNumber,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp invoice job failed permanently', [
            'donatur_id' => $this->donatur->id,
            'invoice_number' => $this->invoiceNumber,
            'error' => $exception->getMessage(),
        ]);
    }
}

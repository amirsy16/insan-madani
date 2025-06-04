<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDonasi extends Model
{
    use HasFactory;

    protected $table = 'invoice_donasi';

    protected $fillable = [
        'donasi_id',
        'nomor_invoice',
        'delivery_method',
        'delivery_status',
        'sent_to_email',
        'sent_to_phone',
        'delivery_notes',
        'pdf_file_path',
        'created_by_user_id',
        'sent_at',
        'delivered_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function donasi(): BelongsTo
    {
        return $this->belongsTo(Donasi::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Generate nomor invoice unik
     */
    public static function generateNomorInvoice(Donasi $donasi): string
    {
        return 'INV-' . $donasi->nomor_transaksi_unik;
    }

    /**
     * Scope untuk filter berdasarkan status delivery
     */
    public function scopeByDeliveryStatus($query, string $status)
    {
        return $query->where('delivery_status', $status);
    }

    /**
     * Scope untuk filter berdasarkan metode delivery
     */
    public function scopeByDeliveryMethod($query, string $method)
    {
        return $query->where('delivery_method', $method);
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark invoice as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark invoice as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'delivery_status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get delivery method display name
     */
    public function getDeliveryMethodDisplayAttribute(): string
    {
        return match($this->delivery_method) {
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            'download' => 'Download Manual',
            'print' => 'Print untuk Pickup',
            default => 'Unknown'
        };
    }

    /**
     * Get delivery status display name
     */
    public function getDeliveryStatusDisplayAttribute(): string
    {
        return match($this->delivery_status) {
            'pending' => 'Menunggu',
            'sent' => 'Terkirim',
            'delivered' => 'Tersampaikan',
            'failed' => 'Gagal',
            default => 'Unknown'
        };
    }

    /**
     * Get download URL for invoice PDF
     */
    public function getDownloadUrlAttribute(): string
    {
        return url('storage/invoices/' . basename($this->pdf_file_path));
    }

    /**
     * Check if invoice can be resent
     */
    public function canBeResent(): bool
    {
        return in_array($this->delivery_status, ['failed', 'pending']);
    }
}

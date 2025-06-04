<?php

namespace App\Mail;

use App\Models\Donasi;
use App\Models\InvoiceDonasi;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class InvoiceDonasiMail extends Mailable
{
    use Queueable, SerializesModels;

    public Donasi $donasi;
    public InvoiceDonasi $invoiceDonasi;
    public string $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Donasi $donasi, InvoiceDonasi $invoiceDonasi, string $pdfPath)
    {
        $this->donasi = $donasi;
        $this->invoiceDonasi = $invoiceDonasi;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice Donasi - ' . $this->invoiceDonasi->nomor_invoice,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-donasi',
            with: [
                'donasi' => $this->donasi,
                'invoiceDonasi' => $this->invoiceDonasi,
                'donatur' => $this->donasi->donatur,
                'namaOrganisasi' => config('app.organization_name', 'Yayasan Amal Kita'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('Invoice_Donasi_' . $this->invoiceDonasi->nomor_invoice . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}

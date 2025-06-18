<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Donasi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border: 1px solid #e2e8f0;
        }
        .footer {
            background-color: #1f2937;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
        }
        .org-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .greeting {
            font-size: 18px;
            color: #2563eb;
            margin-bottom: 20px;
        }
        .donation-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #059669;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-label {
            font-weight: bold;
            color: #374151;
        }
        .info-value {
            color: #1f2937;
        }
        .amount {
            font-size: 20px;
            font-weight: bold;
            color: #059669;
        }
        .thank-you {
            text-align: center;
            font-size: 16px;
            color: #2563eb;
            margin: 30px 0;
            font-weight: bold;
        }
        .attachment-note {
            background-color: #dbeafe;
            border: 1px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .contact-info {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="org-name">{{ $namaOrganisasi }}</div>
        <p>Invoice Donasi</p>
    </div>

    <div class="content">
        <div class="greeting">
            Assalamu'alaikum Warahmatullahi Wabarakatuh
        </div>

        <p>
            @if($donasi->atas_nama_hamba_allah)
                Kepada Yth. Hamba Allah (Donatur Anonim),
            @else
                Kepada Yth. {{ $donatur->nama }},
            @endif
        </p>

        <p>
            Alhamdulillahi rabbil 'alamiin, terima kasih atas kepercayaan dan dukungan Anda kepada {{ $namaOrganisasi }}. 
            Semoga Allah SWT membalas kebaikan Anda dengan balasan yang berlipat ganda.
        </p>

        <div class="donation-info">
            <h3 style="margin-top: 0; color: #2563eb;">Detail Donasi Anda</h3>
            
            <div class="info-row">
                <span class="info-label">Nomor Invoice:</span>
                <span class="info-value">{{ $invoiceDonasi->nomor_invoice }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Nomor Transaksi:</span>
                <span class="info-value">{{ $donasi->nomor_transaksi_unik }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Tanggal Donasi:</span>
                <span class="info-value">{{ $donasi->tanggal_donasi->format('d F Y') }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Jenis Donasi:</span>
                <span class="info-value">{{ $donasi->jenisDonasi->nama ?? 'Donasi Umum' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Metode Pembayaran:</span>
                <span class="info-value">{{ $donasi->metodePembayaran->nama ?? 'Tidak diketahui' }}</span>
            </div>
            
            <div class="info-row" style="border-bottom: 2px solid #059669;">
                <span class="info-label">Jumlah Donasi:</span>
                <span class="info-value amount">Rp {{ number_format($donasi->jumlah, 0, ',', '.') }}</span>
            </div>
            
            @if($donasi->keterangan)
            <div style="margin-top: 15px;">
                <span class="info-label">Keterangan:</span><br>
                <span class="info-value">{{ $donasi->keterangan }}</span>
            </div>
            @endif
        </div>

        <div class="attachment-note">
            <strong>📎 File Terlampir:</strong><br>
            Invoice donasi Anda telah terlampir dalam email ini dalam format PDF. 
            Silakan simpan file ini sebagai bukti donasi Anda.
        </div>

        <div class="thank-you">
            جَزَاكَ اللهُ خَيْرًا كَثِيْرًا<br>
            <em>Jazakallahu Khairan Katsiran</em><br>
            Semoga Allah membalas kebaikan Anda dengan kebaikan yang berlimpah
        </div>

        <p>
            Donasi Anda sangat berarti bagi kami dan akan disalurkan sesuai dengan amanah yang diberikan. 
            Semoga menjadi investasi terbaik untuk kehidupan akhirat Anda.
        </p>

        <p>
            Jika ada pertanyaan atau membutuhkan informasi lebih lanjut, jangan ragu untuk menghubungi kami.
        </p>

        <p>
            Wassalamu'alaikum Warahmatullahi Wabarakatuh
        </p>

        <div class="contact-info">
            <p><strong>{{ $namaOrganisasi }}</strong></p>
            <p>
                Email: {{ config('app.organization_email', 'info@amalkit.org') }}<br>
                Telepon: {{ config('app.organization_phone', '021-1234567') }}<br>
                Website: {{ config('app.organization_website', 'www.amalkit.org') }}
            </p>
        </div>
    </div>

    <div class="footer">
        <p>Email ini digenerate secara otomatis pada {{ now()->format('d F Y H:i:s') }}</p>
        <p>{{ $namaOrganisasi }} - Menyalurkan Amanah dengan Amanah</p>
    </div>
</body>
</html>

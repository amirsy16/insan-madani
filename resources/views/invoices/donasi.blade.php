-@php
    // Debug: uncomment line below to see all available variables
    // dd(get_defined_vars());
    
    // Auto-detection logic for Zakat vs Donasi
    $isZakat = false;
    
    // Define specific zakat types that should use zakat template
    $zakatTypes = ['zakat maal', 'zakat fitrah', 'zakat perusahaan'];
    
    // Check if this is a zakat transaction based on specific zakat types only
    $jenisToCheck = '';
    
    if (isset($jenis_zakat)) {
        $jenisToCheck = strtolower(trim($jenis_zakat));
    } elseif (isset($jenis_donasi)) {
        $jenisToCheck = strtolower(trim($jenis_donasi));
    } elseif (isset($donasi) && isset($donasi->jenisDonasi->nama)) {
        $jenisToCheck = strtolower(trim($donasi->jenisDonasi->nama));
    }
    
    // Only set as zakat if it matches specific zakat types
    if (!empty($jenisToCheck)) {
        foreach ($zakatTypes as $zakatType) {
            if (stripos($jenisToCheck, $zakatType) !== false) {
                $isZakat = true;
                break;
            }
        }
    }
    
    // Set variables based on transaction type
    if ($isZakat) {
        $transactionType = 'ZAKAT';
        $personLabel = 'Muzakki'; // Label for Zakat payer
        $jenisTransaksi = $jenis_zakat ?? ($jenis_donasi ?? $jenisToCheck ?? 'Zakat');
        $tanggalTransaksi = $tanggal_zakat ?? ($tanggal_donasi ?? date('Y-m-d'));
        $islamicGreeting = 'Barakallahu fiikum';
        $islamicMessage = 'Semoga zakat yang diberikan menjadi pembersih harta dan jiwa, serta mendatangkan berkah dari Allah SWT.';
        $ayatQuran = '"Dan dirikanlah shalat, tunaikanlah zakat dan ruku\'lah beserta orang-orang yang ruku\'" - QS. Al-Baqarah: 43';
    } else {
        $transactionType = 'DONASI';
        $personLabel = 'Donatur'; // Label for Donor
        $jenisTransaksi = $jenis_donasi ?? $jenisToCheck ?? 'Donasi Umum';
        $tanggalTransaksi = $tanggal_donasi ?? date('Y-m-d');
        $islamicGreeting = 'Jazakallahu khairan';
        $islamicMessage = 'Semoga donasi yang diberikan menjadi amal jariyah yang terus mengalir pahalanya hingga akhirat.';
        $ayatQuran = '"Perumpamaan (nafkah yang dikeluarkan oleh) orang-orang yang menafkahkan hartanya di jalan Allah adalah serupa dengan sebutir benih yang menumbuhkan tujuh bulir, pada tiap-tiap bulir seratus biji. Allah melipat gandakan (ganjaran) bagi siapa yang Dia kehendaki. Dan Allah Maha Luas (karunia-Nya) lagi Maha Mengetahui." - QS. Al-Baqarah: 261';
    }
    
    // Common variables with safe defaults - checking multiple possible variable names
    $invoiceNumber = $invoice_number ?? $nomor_transaksi ?? 'INV-' . date('YmdHis');
    
    // Get data from donasi object if available
    if (isset($donasi)) {
        // Preserve jenisTransaksi from detection logic above, but allow donasi object to provide more specific info
        if (isset($donasi->jenisDonasi->nama) && !empty($donasi->jenisDonasi->nama)) {
            // Re-check if this specific jenis from donasi object is zakat
            $jenisFromDonasi = strtolower(trim($donasi->jenisDonasi->nama));
            $isZakatFromDonasi = false;
            foreach ($zakatTypes as $zakatType) {
                if (stripos($jenisFromDonasi, $zakatType) !== false) {
                    $isZakatFromDonasi = true;
                    break;
                }
            }
            
            // Update isZakat and related variables if needed
            if ($isZakatFromDonasi && !$isZakat) {
                $isZakat = true;
                $transactionType = 'ZAKAT';
                $personLabel = 'Muzakki';
                $jenisTransaksi = $donasi->jenisDonasi->nama;
                $islamicGreeting = 'Barakallahu fiikum';
                $islamicMessage = 'Semoga zakat yang diberikan menjadi pembersih harta dan jiwa, serta mendatangkan berkah dari Allah SWT.';
                $ayatQuran = '"Dan dirikanlah shalat, tunaikanlah zakat dan ruku\'lah beserta orang-orang yang ruku\'" - QS. Al-Baqarah: 43';
            } elseif (!$isZakatFromDonasi && $isZakat) {
                $isZakat = false;
                $transactionType = 'DONASI';
                $personLabel = 'Donatur';
                $jenisTransaksi = $donasi->jenisDonasi->nama;
                $islamicGreeting = 'Jazakallahu khairan';
                $islamicMessage = 'Semoga donasi yang diberikan menjadi amal jariyah yang terus mengalir pahalanya hingga akhirat.';
                $ayatQuran = '"Perumpamaan (nafkah yang dikeluarkan oleh) orang-orang yang menafkahkan hartanya di jalan Allah adalah serupa dengan sebutir benih yang menumbuhkan tujuh bulir, pada tiap-tiap bulir seratus biji. Allah melipat gandakan (ganjaran) bagi siapa yang Dia kehendaki. Dan Allah Maha Luas (karunia-Nya) lagi Maha Mengetahui." - QS. Al-Baqarah: 261';
            } else {
                // Keep the same type but use jenis from donasi object
                $jenisTransaksi = $donasi->jenisDonasi->nama;
            }
        }
        
        $tanggalTransaksi = $donasi->tanggal_donasi ?? $tanggal_donasi ?? date('Y-m-d');
        if (isset($donasi->jumlah)) {
            if (!isset($jumlah_formatted)) {
                $jumlahFormatted = 'Rp ' . number_format($donasi->jumlah, 0, ',', '.');
            }
        }
        $metodePembayaran = $donasi->metodePembayaran?->nama ?? $metode_pembayaran ?? 'Tidak diketahui';
        $keterangan = $donasi->keterangan ?? $keterangan ?? '';
        $nomor_transaksi = $donasi->nomor_transaksi ?? $nomor_transaksi ?? '';
    } else {
        // No donasi object, keep existing values
        $tanggalTransaksi = $tanggal_donasi ?? date('Y-m-d');
        $metodePembayaran = $metode_pembayaran ?? 'Tidak diketahui';
        $keterangan = $keterangan ?? $catatan ?? $notes ?? $description ?? $note ?? '';
    }
    
    // Get data from donatur object if available
    $nama = '';
    $email = '';
    $telepon = '';
    $alamat = '';
    
    if (isset($donatur)) {
        $nama = $donatur->nama ?? $donatur->nama_donatur ?? '';
        $email = $donatur->email ?? $donatur->email_donatur ?? '';
        $telepon = $donatur->telepon ?? $donatur->no_telepon ?? $donatur->phone ?? '';
        $alamat = $donatur->alamat ?? $donatur->address ?? '';
    }
    
    // Fallback to direct variables if donatur object doesn't exist or is empty
    if (empty($nama)) {
        $nama = $nama ?? $nama_donatur ?? $nama_muzakki ?? $name ?? $full_name ?? $donatur_name ?? $muzakki_name ?? 'Tidak diketahui';
    }
    
    if (empty($email)) {
        $email = $email ?? $email_donatur ?? $email_muzakki ?? $donatur_email ?? $muzakki_email ?? '';
    }
    
    if (empty($telepon)) {
        $telepon = $telepon ?? $telepon_donatur ?? $telepon_muzakki ?? $phone ?? $no_telepon ?? $no_hp ?? $donatur_phone ?? $muzakki_phone ?? '';
    }
    
    if (empty($alamat)) {
        $alamat = $alamat ?? $alamat_donatur ?? $alamat_muzakki ?? $address ?? $donatur_alamat ?? $muzakki_alamat ?? '';
    }
    
    // Use formatted amount if provided
    $jumlahFormatted = $jumlah_formatted ?? ($jumlahFormatted ?? 'Rp 0');
    
    // Organization info
    $organizationName = 'Insan Madani Jambi';
    $organizationAddress = 'Head Office : Jl. Otto Iskandardinata, Kec. Pasar - Kota Jambi';
    $organizationPhone = '628117441471';
    $organizationEmail = 'info@insanmadanijambi.org';
    
    // Logo handling
    $logoPath = public_path('images/LOGOIM.jpg'); 
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        try {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
        } catch (Exception $e) {
            $logoBase64 = '';
        }
    }
    
    // Date formatting
    $tanggalFormatted = date('d F Y', strtotime($tanggalTransaksi));
    $currentYear = date('Y');
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $transactionType }} - {{ $invoiceNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 10px;
            font-size: 10px;
            line-height: 1.3;
        }

        .form-container {
            max-width: 842px; /* A5 landscape width (21cm = ~842px) */
            margin: 0 auto;
            background: white;
            border: 2px solid #6c757d;
            position: relative;
        }
        
        .header {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            padding: 8px 12px;
            color: white;
            position: relative;
        }

        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .header .subtitle {
            font-size: 9px;
            font-style: italic;
            opacity: 0.9;
        }

        .form-number {
            position: absolute;
            top: -6px;
            right: 12px;
            background: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 8px;
        }

        .logo-section {
            position: absolute;
            top: 8px;
            right: 80px;
            background: #6c757d;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 8px;
            font-weight: bold;
        }

        .awards {
            position: absolute;
            top: 8px;
            right: 180px;
            display: flex;
            gap: 8px;
        }

        .award {
            text-align: center;
            font-size: 6px;
            color: white;
        }

        .award-circle {
            width: 25px;
            height: 25px;
            border: 1px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1px;
            font-size: 4px;
            line-height: 1;
        }

        .logo-insan {
            position: absolute;
            top: 8px;
            right: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .logo-icon {
            width: 25px;
            height: 25px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-weight: bold;
            font-size: 10px;
        }

        .logo-text {
            color: white;
            font-size: 8px;
            font-weight: bold;
            line-height: 1.1;
        }

        .organization-info {
            padding: 8px 12px;
            border-bottom: 1px solid #6c757d;
            font-size: 8px;
            line-height: 1.2;
        }

        .main-content {
            display: flex;
            padding: 8px;
            gap: 15px;
        }

        .left-section {
            flex: 1;
            max-width: 320px;
        }

        .right-section {
            width: 460px;
        }

        .form-group {
            margin-bottom: 6px;
        }

        .form-group label {
            display: block;
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 1px;
        }

        .form-group .label-en {
            font-style: italic;
            color: #666;
            font-weight: normal;
        }

        .form-group .data-value {
            border: none;
            border-bottom: 1px solid #6c757d;
            padding: 2px 0;
            font-size: 9px;
            background: transparent;
            width: 100%;
            min-height: 12px;
        }

        .date-branch {
            display: flex;
            gap: 6px;
            margin-bottom: 8px;
        }

        .date-branch .form-group {
            flex: 1;
        }

        .donation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 7px;
        }

        .donation-table th {
            background: #f8f9fa;
            padding: 3px 2px;
            border: 1px solid #6c757d;
            text-align: center;
            font-weight: bold;
        }

        .donation-table td {
            padding: 3px 2px;
            border: 1px solid #6c757d;
            text-align: center;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 7px;
        }

        .payment-table th {
            background: #f8f9fa;
            padding: 3px 2px;
            border: 1px solid #6c757d;
            text-align: center;
            font-weight: bold;
        }

        .payment-table td {
            padding: 3px 2px;
            border: 1px solid #6c757d;
            text-align: center;
        }

        .total-section {
            background: #f8f9fa;
            padding: 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #6c757d;
            margin-bottom: 6px;
            font-size: 9px;
        }

        .total-amount {
            font-size: 12px;
            color: #6c757d;
            margin-top: 2px;
        }

        .words-section {
            border: 1px solid #6c757d;
            padding: 4px;
            margin-bottom: 6px;
            min-height: 25px;
        }

        .words-section label {
            font-size: 7px;
            font-weight: bold;
        }

        .signature-section {
            display: flex;
            gap: 8px;
            margin-top: 6px;
        }

        .signature-box {
            flex: 1;
            text-align: center;
            border: 1px solid #6c757d;
            padding: 20px 4px 4px;
            font-size: 7px;
        }

        .tax-section {
            background: #f8f9fa;
            padding: 6px;
            margin: 8px 0;
            border: 1px solid #dee2e6;
        }

        .tax-section h4 {
            font-size: 8px;
            margin-bottom: 4px;
        }

        .greeting-section {
            background: linear-gradient(135deg, #fff8e2, #ffedb3);
            padding: 6px;
            margin-bottom: 6px;
            text-align: center;
            border-left: 3px solid #b8860b;
            border-radius: 3px;
        }

        .greeting-title {
            font-size: 9px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1px;
        }

        .greeting-message {
            font-size: 7px;
            color: #6b7280;
            font-style: italic;
            line-height: 1.2;
        }

        .quote-section {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            padding: 6px;
            border-radius: 3px;
            margin-bottom: 6px;
            border-left: 3px solid #b8860b;
        }

        .quote-text {
            font-size: 7px;
            color: #374151;
            font-style: italic;
            text-align: center;
            line-height: 1.3;
        }

        .organization-name {
            background: #6c757d;
            color: white;
            padding: 3px 6px;
            text-align: center;
            font-size: 6px;
            font-weight: bold;
            margin: 6px 0;
        }

        .footer {
            background: #6c757d;
            color: white;
            padding: 4px 8px;
            font-size: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .contact-info {
            display: flex;
            gap: 8px;
            align-items: center;
            flex: 1;
        }

        .social-icons {
            display: flex;
            gap: 4px;
        }

        .social-icon {
            width: 12px;
            height: 12px;
            background: white;
            color: #6c757d;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
        }

        .disclaimer {
            background: #f9f9f9;
            padding: 4px;
            font-size: 6px;
            line-height: 1.2;
            margin: 6px 0;
            border-left: 2px solid #6c757d;
        }

        .checkbox-checked {
            color: #e74c3c;
            font-weight: bold;
        }

        /* Print Styles */
        @media print {
            body { 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: white;
                padding: 0;
                font-size: 9px;
            }
            
            .form-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: none;
            }
            
            @page {
                margin: 0.3in;
                size: A5 landscape;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            
            .right-section {
                width: 100%;
            }
            
            .date-branch {
                flex-direction: column;
            }
            
            .signature-section {
                flex-direction: column;
            }
            
            .contact-info {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-number">No. 24</div>
        
        <div class="header">
            <h1>Formulir Setoran Donasi</h1>
            <div class="subtitle">Deposit Donations Form</div>
            
            <div class="awards">
                <div class="award">
                    <div class="award-circle">
                        <div>PREDIKAT<br>TERBAIK<br>SKALA<br>PROVINSI</div>
                    </div>
                    <div>BAIK</div>
                </div>
                <div class="award">
                    <div class="award-circle">
                        <div>OPINI<br>AUDIT<br>KEUANGAN</div>
                    </div>
                    <div>WTP</div>
                </div>
            </div>
        </div>

        <div class="logo-section">LEMBAGA AMIL ZAKAT</div>
        
        @if($logoBase64)
            <div class="logo-insan">
                <img src="{{ $logoBase64 }}" alt="Logo" class="logo-icon" style="border-radius: 50%; width: 30px; height: 30px; object-fit: contain;">
                <div class="logo-text">{{ $organizationName }}</div>
            </div>
        @else
            <div class="logo-insan">
                <div class="logo-icon">♥</div>
                <div class="logo-text">{{ $organizationName }}</div>
            </div>
        @endif

        <div class="organization-info">
            <strong>bismillaahirrahmaanirrahiim</strong><br>
            kepada to <strong>LAZ Yayasan {{ $organizationName }}</strong><br>
            mohon dicatat transaksi berikut <em>please record this transaction</em>
        </div>

        <div class="main-content">
            <div class="left-section">
                <div class="form-group">
                    <label>nama <span class="label-en">name</span></label>
                    <div class="data-value">{{ $nama }}</div>
                </div>
                
                <div class="form-group">
                    <label>ID {{ strtolower($personLabel) }} <span class="label-en">{{ strtolower($personLabel) }} ID</span></label>
                    <div class="data-value">{{ $invoiceNumber }}</div>
                </div>
                
                @if($alamat)
                <div class="form-group">
                    <label>alamat <span class="label-en">address</span></label>
                    <div class="data-value">{{ $alamat }}</div>
                    <div class="data-value" style="margin-top: 3px;"></div>
                </div>
                @endif
                
                @if($telepon)
                <div class="form-group">
                    <label>nomor telepon <span class="label-en">phone number</span></label>
                    <div class="data-value">{{ $telepon }}</div>
                </div>
                @endif

                <div class="tax-section">
                    <h4>untuk pengurang pajak penghasilan <em>for income tax deduction</em></h4>
                    <div style="border: 1px solid #6c757d; padding: 4px; background: white; margin-bottom: 6px;">
                        <label style="font-size: 7px; font-weight: bold;">Nomor Pokok Wajib Pajak (NPWP)</label>
                        <div style="border-bottom: 1px solid #6c757d; margin-top: 2px; min-height: 10px;"></div>
                    </div>
                    <div style="font-size: 6px; line-height: 1.2; margin-bottom: 6px;">
                        Diisi sebagai lampiran SPT Tahunan Pajak Penghasilan untuk pengurang Penghasilan Kena Pajak (PKP) sesuai keputusan Dirjen Pajak No. KEP-163/PJ/2003
                    </div>
                    
                    <div class="signature-section">
                        <div class="signature-box">
                            <div>Tanda Tangan Penyetor</div>
                            <div><em>depositors signature</em></div>
                        </div>
                        <div class="signature-box">
                            <div>Pengesahan Petugas Amil</div>
                            <div><em>amil officer authentication</em></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-section">
                <div class="date-branch">
                    <div class="form-group">
                        <label>tanggal <span class="label-en">date</span></label>
                        <div class="data-value">{{ $tanggalFormatted }}</div>
                    </div>
                    <div class="form-group">
                        <label>cabang <span class="label-en">branch</span></label>
                        <div class="data-value">Jambi</div>
                    </div>
                    <div class="form-group">
                        <label>mata uang <span class="label-en">currency</span></label>
                        <div class="data-value">IDR</div>
                    </div>
                </div>

                <table class="donation-table">
                    <thead>
                        <tr>
                            <th>jenis {{ strtolower($transactionType) }}<br><em>kind of {{ strtolower($transactionType) }}</em></th>
                            <th>uraian<br><em>note</em></th>
                            <th>nominal<br><em>amount</em></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $isZakat ? '☑' : '☐' }}</td>
                            <td>zakat<br><em>zakat</em></td>
                            <td>{{ $isZakat ? $jumlahFormatted : '' }}</td>
                        </tr>
                        <tr>
                            <td>{{ !$isZakat ? '☑' : '☐' }}</td>
                            <td>infak/ sedekah<br><em>donation</em></td>
                            <td>{{ !$isZakat ? $jumlahFormatted : '' }}</td>
                        </tr>
                        <tr>
                            <td>☐</td>
                            <td>Infak DSRL</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>berupa<br><em>consist of</em></th>
                            <th>penerbit/ nomor<br><em>issued by/ number</em></th>
                            <th>nominal<br><em>amount</em></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ strtolower($metodePembayaran) === 'cash' || strtolower($metodePembayaran) === 'tunai' ? '☑' : '☐' }}</td>
                            <td>tunai<br><em>cash</em></td>
                            <td>{{ strtolower($metodePembayaran) === 'cash' || strtolower($metodePembayaran) === 'tunai' ? $jumlahFormatted : '' }}</td>
                        </tr>
                        <tr>
                            <td>{{ stripos($metodePembayaran, 'transfer') !== false || stripos($metodePembayaran, 'bank') !== false ? '☑' : '☐' }}</td>
                            <td>transfer<br><em>{{ $metodePembayaran }}</em></td>
                            <td>{{ stripos($metodePembayaran, 'transfer') !== false || stripos($metodePembayaran, 'bank') !== false ? $jumlahFormatted : '' }}</td>
                        </tr>
                        <tr>
                            <td>{{ stripos($metodePembayaran, 'card') !== false || stripos($metodePembayaran, 'debit') !== false ? '☑' : '☐' }}</td>
                            <td>cek/ debet<br><em>cheque/ debit card</em></td>
                            <td>{{ stripos($metodePembayaran, 'card') !== false || stripos($metodePembayaran, 'debit') !== false ? $jumlahFormatted : '' }}</td>
                        </tr>
                        <tr>
                            <td>☐</td>
                            <td>barang/ jasa<br><em>goods/ services</em></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <div class="total-section">
                    <strong>TOTAL</strong>
                    <div class="total-amount">{{ $jumlahFormatted }}</div>
                </div>

                <div class="words-section">
                    <label>terbilang <em>in words</em></label>
                    <div style="margin-top: 2px; font-size: 7px;">
                        @if($keterangan)
                            {{ $keterangan }}
                        @else
                            {{ $jumlahFormatted }} Rupiah
                        @endif
                    </div>
                </div>

                <div style="display: flex; gap: 8px; align-items: center; margin-top: 6px;">
                    <div style="width: 60px; height: 60px; background: #6c757d; display: flex; align-items: center; justify-content: center; color: white; font-size: 6px; text-align: center; line-height: 1.1;">
                        QR CODE<br>PLACEHOLDER
                    </div>
                    <div style="flex: 1; font-size: 6px; line-height: 1.2;">
                        <strong>scan QR/S ini untuk berdonasi melalui aplikasi pembayaran digital</strong><br><br>
                        - Lembar Putih : Keuangan<br>
                        - Lembar Kuning : Donatur
                    </div>
                </div>
            </div>
        </div>

        <!-- Quote Section -->
        <div class="quote-section">
            <p class="quote-text">{{ $ayatQuran }}</p>
        </div>

        <div class="disclaimer">
            <em>Harta yang diberikan merupakan milik seseorang/instansi/lembaga, telah melewati haul dan nishabnya. LAZ Insan Madani hanya menerima donasi dari sumber yang halal dan tidak bersentuhan dengan persaniuan atau hukum melanggar perisahaan atau hukum menyalahi ketentuan. Hanya ALLAH tempat persembahan dan kebahagiaan dunia dan akhirat baginya.</em><br><br>
            <strong>Semoga Allah memberikan pahala atas apa yang telah Anda berikan, menyucikan harta suci dan memberikan baik yang terbatas kepada keluarga besar Anda yang tersayang. Doa untuk pembersihan Zakat!</strong>
        </div>

        <div class="organization-name">
            <strong>LAZ {{ strtoupper($organizationName) }} - LEMBAGA AMIL ZAKAT RESMI SKALA PROVINSI<br>
            IZIN DIRJEN BIMAS ISLAM KEMENTERIAN AGAMA REPUBLIK INDONESIA</strong>
        </div>

        <div class="footer">
            <div class="contact-info">
                <div><strong>Head Office :</strong> Jl. Otto Iskandardinata No. 15 Kel. Sei Asam Kec. Pasar Jambi Kota Jambi</div>
                <div>📞 0811.743.1231</div>
            </div>
            <div class="social-icons">
                <div class="social-icon">@</div>
                <div class="social-icon">📺</div>
                <div class="social-icon">f</div>
            </div>
            <div><strong>insanmadanijambi.org</strong></div>
        </div>
    </div>
</body>
</html>
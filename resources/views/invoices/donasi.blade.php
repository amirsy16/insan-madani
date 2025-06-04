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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9fafb;
            line-height: 1.4;
            color: #374151;
            padding: 10px 0;
            font-size: 14px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            min-height: calc(100vh - 20px);
            max-height: calc(100vh - 20px);
            overflow: hidden;
        }
        
        /* Header Section */
        .header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .logo {
            width: 80px;
            height: auto;
            max-height: 80px;
            object-fit: contain;
            object-position: center;
            border-radius: 6px;
        }
        
        .org-info-container h1 {
            font-size: 22px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .org-info {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        .invoice-section {
            text-align: right;
            flex-shrink: 0;
        }
        
        .invoice-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .invoice-badge.zakat {
            background: linear-gradient(135deg, #8b1538, #5d0e26);
        }
        
        .invoice-badge.donasi {
            background: linear-gradient(135deg, #8b1538, #5d0e26);
        }
        
        .invoice-details {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.4;
        }
        
        /* Greeting Section */
        .greeting-section {
            text-align: center;
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 6px;
            border-left: 4px solid;
        }
        
        .greeting-section.zakat {
            border-left-color: #b8860b;
            background: linear-gradient(135deg, #fff8e2, #ffedb3);
        }
        
        .greeting-section.donasi {
            border-left-color: #b8860b;
            background: linear-gradient(135deg, #fff8e2, #ffedb3);
        }
        
        .greeting-title {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }
        
        .greeting-message {
            font-size: 13px;
            color: #6b7280;
            font-style: italic;
            line-height: 1.4;
        }
        
        /* Data Grid */
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .data-card {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        
        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .icon {
            width: 16px;
            height: 16px;
        }
        
        .icon.zakat {
            color: #8b1538;
        }
        
        .icon.donasi {
            color: #8b1538;
        }
        
        .data-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            font-size: 12px;
            gap: 12px;
        }
        
        .data-row:last-child {
            margin-bottom: 0;
        }
        
        .data-label {
            font-weight: 500;
            color: #6b7280;
            min-width: 70px;
            flex-shrink: 0;
        }
        
        .data-value {
            color: #1f2937;
            text-align: right;
            word-break: break-word;
        }
        
        /* Amount Section */
        .amount-section {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px solid;
            text-align: center;
        }
        
        .amount-section.zakat {
            background: linear-gradient(135deg, #fef7f7, #fef2f2);
            border-color: #8b1538;
        }
        
        .amount-section.donasi {
            background: linear-gradient(135deg, #fef7f7, #fef2f2);
            border-color: #8b1538;
        }
        
        .amount-title {
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 5px;
        }
        
        .amount-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .amount-value.zakat {
            color: #8b1538;
        }
        
        .amount-value.donasi {
            color: #8b1538;
        }
        
        .amount-description {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.4;
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Quote Section */
        .quote-section {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid;
            position: relative;
        }
        
        .quote-section.zakat {
            border-left-color: #b8860b;
        }
        
        .quote-section.donasi {
            border-left-color: #b8860b;
        }
        
        .quote-text {
            font-size: 12px;
            color: #374151;
            font-style: italic;
            text-align: center;
            line-height: 1.5;
        }
        
        .quote-divider {
            width: 40px;
            height: 2px;
            margin: 10px auto 0;
            border-radius: 1px;
        }
        
        .quote-divider.zakat {
            background-color: #b8860b;
        }
        
        .quote-divider.donasi {
            background-color: #b8860b;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        
        .footer-message {
            color: #374151;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .footer-info {
            font-size: 11px;
            color: #9ca3af;
            line-height: 1.4;
        }
        

        
        /* Print Styles */
        @media print {
            .no-print { 
                display: none !important; 
            }
            
            body { 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: white;
                padding: 0;
                font-size: 12px;
            }
            
            .container {
                box-shadow: none;
                margin: 0;
                padding: 15px;
                max-width: none;
                min-height: auto;
                max-height: none;
            }
            
            @page {
                margin: 0.5in;
                size: A4;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 20px;
            }
            
            .header-flex {
                flex-direction: column;
                gap: 20px;
            }
            
            .invoice-section {
                text-align: left;
            }
            
            .logo-section {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }
            
            .org-info-container h1 {
                font-size: 24px;
            }
            
            .data-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .amount-value {
                font-size: 28px;
            }
            
            .quote-text {
                font-size: 14px;
            }
            
            .data-row {
                flex-direction: column;
                gap: 4px;
            }
            
            .data-label {
                min-width: auto;
            }
            
            .data-value {
                text-align: left;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                margin: 5px;
                padding: 15px;
            }
            
            .logo {
                width: 70px;
                max-height: 70px;
            }
            
            .org-info-container h1 {
                font-size: 20px;
            }
            
            .invoice-badge {
                font-size: 16px;
                padding: 10px 16px;
            }
            
            .amount-value {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="header-flex">
                <div class="logo-section">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo {{ $organizationName }}" class="logo">
                    @endif
                    <div class="org-info-container">
                        <h1>{{ $organizationName }}</h1>
                        <div class="org-info">
                            <div>{{ $organizationAddress }}</div>
                            <div>{{ $organizationPhone }} | {{ $organizationEmail }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="invoice-section">
                    <div class="invoice-badge {{ $isZakat ? 'zakat' : 'donasi' }}">
                        INVOICE {{ $transactionType }}
                    </div>
                    <div class="invoice-details">
                        <div>No: {{ $invoiceNumber }}</div>
                        <div>Tanggal: {{ $tanggalFormatted }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Greeting Section -->
        <div class="greeting-section {{ $isZakat ? 'zakat' : 'donasi' }}">
            <h3 class="greeting-title">{{ $islamicGreeting }}</h3>
            <p class="greeting-message">{{ $islamicMessage }}</p>
        </div>

        <!-- Data Grid -->
        <div class="data-grid">
            <div class="data-card">
                <h4 class="card-title">
                    <svg class="icon {{ $isZakat ? 'zakat' : 'donasi' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    Data {{ $personLabel }}
                </h4>
                <div>
                    <div class="data-row">
                        <span class="data-label">Nama:</span>
                        <span class="data-value">{{ $nama }}</span>
                    </div>
                    @if($email)
                    <div class="data-row">
                        <span class="data-label">Email:</span>
                        <span class="data-value">{{ $email }}</span>
                    </div>
                    @endif
                    @if($telepon)
                    <div class="data-row">
                        <span class="data-label">Telepon:</span>
                        <span class="data-value">{{ $telepon }}</span>
                    </div>
                    @endif
                    @if($alamat)
                    <div class="data-row">
                        <span class="data-label">Alamat:</span>
                        <span class="data-value">{{ $alamat }}</span>
                    </div>
                    @endif
                    <div class="data-row">
                        <span class="data-label">Tanggal:</span>
                        <span class="data-value">{{ $tanggalFormatted }}</span>
                    </div>
                </div>
            </div>

            <div class="data-card">
                <h4 class="card-title">
                    <svg class="icon {{ $isZakat ? 'zakat' : 'donasi' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    Informasi Transaksi
                </h4>
                <div>
                    <div class="data-row">
                        <span class="data-label">Jenis {{ $transactionType }}:</span>
                        <span class="data-value">{{ $jenisTransaksi }}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Metode Pembayaran:</span>
                        <span class="data-value">{{ $metodePembayaran }}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">No. Invoice:</span>
                        <span class="data-value">{{ $invoiceNumber }}</span>
                    </div>
                    @if($keterangan)
                    <div class="data-row">
                        <span class="data-label">Keterangan:</span>
                        <span class="data-value">{{ $keterangan }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Amount Section -->
        <div class="amount-section {{ $isZakat ? 'zakat' : 'donasi' }}">
            <p class="amount-title">Total {{ $transactionType }}</p>
            <p class="amount-value {{ $isZakat ? 'zakat' : 'donasi' }}">{{ $jumlahFormatted }}</p>
            <p class="amount-description">
                @if($isZakat)
                    Zakat yang telah diterima akan disalurkan kepada 8 golongan yang berhak menerima zakat (mustahiq).
                @else
                    Donasi yang telah diterima akan disalurkan untuk program-program kemanusiaan dan dakwah.
                @endif
            </p>
        </div>

        <!-- Quote Section -->
        <div class="quote-section {{ $isZakat ? 'zakat' : 'donasi' }}">
            <p class="quote-text">{{ $ayatQuran }}</p>
            <div class="quote-divider {{ $isZakat ? 'zakat' : 'donasi' }}"></div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-message">Terima kasih atas {{ strtolower($transactionType) }} yang telah diberikan.</p>
            <div class="footer-info">
                <div>Invoice ini dibuat secara otomatis pada {{ date('d F Y H:i') }} WIB.</div>
                <div>&copy; {{ $currentYear }} {{ $organizationName }}. Semua hak dilindungi.</div>
            </div>
        </div>
    </div>

</body>
</html>
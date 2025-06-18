@php
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
    $organizationAddress = 'Head Office : Jl. Otto Iskandardinata No. 15 Kel. Sei Asam Kec. Pasar - Kota Jambi';
    $organizationPhone = '0811.743.1231';
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
    
    // Convert amount to words (terbilang)
    function terbilang($angka) {
        $angka = abs($angka);
        $baca = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        
        if ($angka < 12) {
            return $baca[$angka];
        } elseif ($angka < 20) {
            return $baca[$angka - 10] . " belas";
        } elseif ($angka < 100) {
            $puluhan = intval($angka / 10);
            $satuan = $angka % 10;
            return $baca[$puluhan] . " puluh " . $baca[$satuan];
        } elseif ($angka < 200) {
            return "seratus " . terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $ratusan = intval($angka / 100);
            return $baca[$ratusan] . " ratus " . terbilang($angka % 100);
        } elseif ($angka < 2000) {
            return "seribu " . terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $ribuan = intval($angka / 1000);
            return terbilang($ribuan) . " ribu " . terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $jutaan = intval($angka / 1000000);
            return terbilang($jutaan) . " juta " . terbilang($angka % 1000000);
        } else {
            $milyaran = intval($angka / 1000000000);
            return terbilang($milyaran) . " milyar " . terbilang($angka % 1000000000);
        }
    }
    
    // Get numeric value for terbilang
    $jumlahNumeric = 0;
    if (isset($donasi) && isset($donasi->jumlah)) {
        $jumlahNumeric = $donasi->jumlah;
    } elseif (isset($jumlah_formatted)) {
        // Extract numeric value from formatted string
        $jumlahNumeric = (int) preg_replace('/[^\d]/', '', $jumlah_formatted);
    }
    
    $terbilangText = $jumlahNumeric > 0 ? ucfirst(terbilang($jumlahNumeric)) . ' rupiah' : '';
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $transactionType }} - {{ $invoiceNumber }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            width: 210mm;
            height: 148mm;
            background: white;
            font-size: 9px;
            line-height: 1.2;
        }
        
        .form-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            position: relative;
        }
        
        .header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 8px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            flex: 1;
        }
        
        .header-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .header-subtitle {
            font-size: 11px;
            font-style: italic;
            opacity: 0.9;
        }
        
        .header-center {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .award-badge {
            text-align: center;
            font-size: 7px;
            line-height: 1.1;
        }
        
        .award-circle {
            width: 45px;
            height: 45px;
            border: 2px solid white;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 2px;
        }
        
        .award-text {
            font-weight: bold;
            font-size: 6px;
        }
        
        .header-right {
            background: white;
            color: #e74c3c;
            padding: 8px 12px;
            border-radius: 15px;
            text-align: center;
            font-weight: bold;
        }
        
        .form-number {
            font-size: 10px;
            margin-bottom: 2px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .logo-placeholder {
            width: 35px;
            height: 35px;
            background: white;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #e74c3c;
        }
        
        .organization-name {
            font-size: 11px;
            font-weight: bold;
        }
        
        .content {
            background: white;
            flex: 1;
            padding: 10px 12px;
            display: flex;
            gap: 10px;
        }
        
        .left-section {
            flex: 1.2;
        }
        
        .bismillah {
            font-size: 8px;
            margin-bottom: 3px;
            font-style: italic;
        }
        
        .request-text {
            font-size: 8px;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .form-field {
            margin-bottom: 6px;
            display: flex;
            align-items: center;
        }
        
        .field-label {
            font-size: 8px;
            width: 80px;
            font-weight: normal;
        }
        
        .field-line {
            flex: 1;
            border-bottom: 1px solid #333;
            height: 12px;
            margin-left: 5px;
            position: relative;
        }
        
        .field-value {
            position: absolute;
            bottom: 2px;
            left: 2px;
            font-size: 8px;
            color: #333;
        }
        
        .tax-section {
            background: #f7f7f7;
            border: 1px solid #ddd;
            padding: 6px;
            margin: 8px 0;
        }
        
        .tax-title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .tax-field {
            border: 1px solid #333;
            height: 15px;
            margin-bottom: 4px;
        }
        
        .tax-note {
            font-size: 7px;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
        }
        
        .signature-box {
            text-align: center;
            font-size: 7px;
            width: 80px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            height: 25px;
            margin-bottom: 2px;
        }
        
        .right-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .date-branch-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .date-field, .branch-field, .currency-field {
            font-size: 8px;
        }
        
        .date-line, .branch-line, .currency-line {
            border-bottom: 1px solid #333;
            width: 80px;
            height: 12px;
            margin-left: 5px;
            position: relative;
        }
        
        .date-value, .branch-value, .currency-value {
            position: absolute;
            bottom: 2px;
            left: 2px;
            font-size: 8px;
            color: #333;
        }
        
        .donation-table {
            border: 1px solid #333;
            margin-bottom: 8px;
        }
        
        .table-header {
            background: #e2e8f0;
            padding: 4px;
            display: flex;
            border-bottom: 1px solid #333;
        }
        
        .table-col {
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            padding: 2px;
        }
        
        .col-type {
            flex: 1.2;
            border-right: 1px solid #333;
        }
        
        .col-note {
            flex: 1;
            border-right: 1px solid #333;
        }
        
        .col-amount {
            flex: 0.8;
        }
        
        .table-row {
            display: flex;
            border-bottom: 1px solid #333;
            min-height: 18px;
            align-items: center;
        }
        
        .table-row:last-child {
            border-bottom: none;
        }
        
        .row-checkbox {
            width: 12px;
            text-align: center;
            border-right: 1px solid #333;
        }
        
        .row-text {
            flex: 1;
            padding: 2px 4px;
            font-size: 7px;
            border-right: 1px solid #333;
        }
        
        .row-amount {
            flex: 0.8;
            padding: 2px;
            font-size: 7px;
        }
        
        .checkbox {
            width: 8px;
            height: 8px;
            border: 1px solid #333;
            display: inline-block;
        }
        
        .checkbox-checked {
            background: #e74c3c;
            color: white;
            font-weight: bold;
            text-align: center;
            line-height: 6px;
            font-size: 6px;
        }
        
        .payment-table {
            border: 1px solid #333;
            margin-bottom: 8px;
        }
        
        .total-row {
            background: #f0f0f0;
            font-weight: bold;
            border-top: 2px solid #333;
        }
        
        .words-section {
            border: 1px solid #333;
            padding: 4px;
            margin-bottom: 8px;
        }
        
        .words-label {
            font-size: 7px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .words-box {
            height: 30px;
            border: 1px solid #ddd;
            padding: 2px;
            font-size: 7px;
            display: flex;
            align-items: center;
        }
        
        .qr-disclaimer {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        
        .qr-code {
            width: 50px;
            height: 50px;
            border: 1px solid #333;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6px;
            text-align: center;
            color: #666;
        }
        
        .disclaimer-text {
            flex: 1;
            font-size: 6px;
            line-height: 1.3;
        }
        
        .footer {
            background: #e74c3c;
            color: white;
            padding: 6px 12px;
            font-size: 7px;
            text-align: center;
        }
        
        .footer-org {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .footer-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .contact-info {
            display: flex;
            gap: 15px;
        }
        
        .social-icons {
            display: flex;
            gap: 5px;
        }
        
        .social-icon {
            width: 12px;
            height: 12px;
            background: white;
            border-radius: 2px;
        }
        
        .underline {
            text-decoration: underline;
        }
        
        .italic {
            font-style: italic;
        }
        
        .bold {
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
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="header-title">Formulir Setoran {{ $transactionType }}</div>
                <div class="header-subtitle">{{ $transactionType === 'ZAKAT' ? 'Zakat Deposit Form' : 'Donation Deposit Form' }}</div>
            </div>
            
            <div class="header-center">
                <div class="award-badge">
                    <div class="award-circle">
                        <div style="font-size: 5px;">PREDIKAT</div>
                        <div style="font-size: 5px;">TERBAIK</div>
                        <div style="font-size: 5px;">KEMENAG</div>
                        <div style="font-size: 5px;">PROVINSI</div>
                    </div>
                    <div class="award-text">BAIK</div>
                </div>
                
                <div class="award-badge">
                    <div class="award-circle">
                        <div style="font-size: 5px;">OPINI</div>
                        <div style="font-size: 5px;">AUDIT</div>
                        <div style="font-size: 5px;">KEUANGAN</div>
                    </div>
                    <div class="award-text">WTP</div>
                </div>
            </div>
            
            <div class="header-right">
                <div class="form-number">No. {{ $invoiceNumber }}</div>
                <div style="border-top: 1px solid #e74c3c; margin: 2px 0;"></div>
                <div class="logo-section">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo" class="logo-placeholder" style="object-fit: contain;">
                    @else
                        <div class="logo-placeholder">♥</div>
                    @endif
                    <div>
                        <div class="organization-name">{{ $organizationName }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="left-section">
                <div class="bismillah">bismillaahirrahmaanirrahim</div>
                <div class="request-text">
                    kepada to <span class="bold">LAZ Yayasan {{ $organizationName }}</span><br>
                    mohon dicatat transaksi berikut <span class="italic">please record this transaction</span>
                </div>
                
                <div class="form-field">
                    <span class="field-label">nama<br><span class="italic">name</span></span>
                    <div class="field-line">
                        <div class="field-value">{{ $nama }}</div>
                    </div>
                </div>
                
                <div class="form-field">
                    <span class="field-label">ID {{ strtolower($personLabel) }}<br><span class="italic">{{ strtolower($personLabel) }} ID</span></span>
                    <div class="field-line">
                        <div class="field-value">{{ $invoiceNumber }}</div>
                    </div>
                </div>
                
                <div class="form-field">
                    <span class="field-label">alamat<br><span class="italic">address</span></span>
                    <div class="field-line">
                        <div class="field-value">{{ $alamat }}</div>
                    </div>
                </div>
                
                <div class="form-field">
                    <span class="field-label"></span>
                    <div class="field-line"></div>
                </div>
                
                <div class="form-field">
                    <span class="field-label">nomor telepon<br><span class="italic">phone number</span></span>
                    <div class="field-line">
                        <div class="field-value">{{ $telepon }}</div>
                    </div>
                </div>
                
                <div class="tax-section">
                    <div class="tax-title">untuk pengurang pajak penghasilan <span class="italic">for income tax reduction</span></div>
                    <div style="display: flex; gap: 5px; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 7px;">Nomor Pokok Wajib Pajak (NPWP)</span>
                    </div>
                    <div class="tax-field"></div>
                    <div class="tax-note">
                        Diisi sebagai lampiran SPT Tahunan Pajak Penghasilan untuk pengurang Penghasilan Kena Pajak (PKP) sesuai keputusan Dirjen Pajak No. KEP-163/PJ/2003
                    </div>
                    
                    <div class="signature-section">
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <div>Tanda Tangan Penyetor<br><span class="italic">depositors signature</span></div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <div>Pengesahan Petugas Amil<br><span class="italic">amil officer authentication</span></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="right-section">
                <div class="date-branch-section">
                    <div class="date-field">
                        tanggal <span class="italic">date</span>
                        <div class="date-line">
                            <div class="date-value">{{ $tanggalFormatted }}</div>
                        </div>
                    </div>
                    <div class="branch-field">
                        cabang <span class="italic">branch</span>
                        <div class="branch-line">
                            <div class="branch-value">Jambi</div>
                        </div>
                    </div>
                    <div class="currency-field">
                        mata uang <span class="italic">currency</span>
                        <div class="currency-line">
                            <div class="currency-value">IDR</div>
                        </div>
                    </div>
                </div>
                
                <div class="donation-table">
                    <div class="table-header">
                        <div class="table-col col-type">jenis {{ strtolower($transactionType) }}<br><span class="italic">kind of {{ strtolower($transactionType) }}</span></div>
                        <div class="table-col col-note">uraian<br><span class="italic">note</span></div>
                        <div class="table-col col-amount">nominal<br><span class="italic">amount</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div class="row-checkbox">
                            @if($isZakat)
                                <span class="checkbox checkbox-checked">✓</span>
                            @else
                                <span class="checkbox"></span>
                            @endif
                        </div>
                        <div class="row-text">zakat<br><span class="italic">zakat</span></div>
                        <div class="row-amount">{{ $isZakat ? $jumlahFormatted : '' }}</div>
                    </div>
                    
                    <div class="table-row">
                        <div class="row-checkbox">
                            @if(!$isZakat)
                                <span class="checkbox checkbox-checked">✓</span>
                            @else
                                <span class="checkbox"></span>
                            @endif
                        </div>
                        <div class="row-text">infak/ sedekah<br><span class="italic">donation</span></div>
                        <div class="row-amount">{{ !$isZakat ? $jumlahFormatted : '' }}</div>
                    </div>
                    
                    <div class="table-row">
                        <div class="row-checkbox"><span class="checkbox"></span></div>
                        <div class="row-text">infak DSKL</div>
                        <div class="row-amount"></div>
                    </div>
                </div>
                
                <div class="payment-table">
                    <div class="table-header">
                        <div class="table-col col-type">berupa<br><span class="italic">consist of</span></div>
                        <div class="table-col col-note">penerbit/ nomor<br><span class="italic">issued by/ number</span></div>
                        <div class="table-col col-amount">nominal<br><span class="italic">amount</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div class="row-checkbox">
                            @if(strtolower($metodePembayaran) === 'cash' || strtolower($metodePembayaran) === 'tunai')
                                <span class="checkbox checkbox-checked">✓</span>
                            @else
                                <span class="checkbox"></span>
                            @endif
                        </div>
                        <div class="row-text">tunai<br><span class="italic">cash</span></div>
                        <div class="row-amount">{{ strtolower($metodePembayaran) === 'cash' || strtolower($metodePembayaran) === 'tunai' ? $jumlahFormatted : '' }}</div>
                    </div>
                    
                    <div class="table-row">
                        <div class="row-checkbox">
                            @if(stripos($metodePembayaran, 'transfer') !== false || stripos($metodePembayaran, 'bank') !== false)
                                <span class="checkbox checkbox-checked">✓</span>
                            @else
                                <span class="checkbox"></span>
                            @endif
                        </div>
                        <div class="row-text">transfer<br><span class="italic">{{ $metodePembayaran }}</span></div>
                        <div class="row-amount">{{ stripos($metodePembayaran, 'transfer') !== false || stripos($metodePembayaran, 'bank') !== false ? $jumlahFormatted : '' }}</div>
                    </div>
                    
                    <div class="table-row">
                        <div class="row-checkbox">
                            @if(stripos($metodePembayaran, 'card') !== false || stripos($metodePembayaran, 'debit') !== false)
                                <span class="checkbox checkbox-checked">✓</span>
                            @else
                                <span class="checkbox"></span>
                            @endif
                        </div>
                        <div class="row-text">cek/ debet<br><span class="italic">cheque/ debit card</span></div>
                        <div class="row-amount">{{ stripos($metodePembayaran, 'card') !== false || stripos($metodePembayaran, 'debit') !== false ? $jumlahFormatted : '' }}</div>
                    </div>
                    
                    <div class="table-row">
                        <div class="row-checkbox"><span class="checkbox"></span></div>
                        <div class="row-text">barang/ jasa<br><span class="italic">goods/ services</span></div>
                        <div class="row-amount"></div>
                    </div>
                    
                    <div class="table-row total-row">
                        <div class="row-checkbox"></div>
                        <div class="row-text"><strong>TOTAL</strong></div>
                        <div class="row-amount"><strong>{{ $jumlahFormatted }}</strong></div>
                    </div>
                </div>
                
                <div class="words-section">
                    <div class="words-label">terbilang <span class="italic">in words</span></div>
                    <div class="words-box">
                        {{ $terbilangText ?: ($keterangan ?: $jumlahFormatted . ' Rupiah') }}
                    </div>
                </div>
                
                <div class="qr-disclaimer">
                    <div class="qr-code">
                        QR CODE
                    </div>
                    <div class="disclaimer-text">
                        <div style="margin-bottom: 3px;"><strong>{{ $islamicGreeting }}</strong></div>
                        <div style="margin-bottom: 3px;">{{ $islamicMessage }}</div>
                        <div style="margin-top: 3px; font-size: 5px;"><strong>scan QR untuk berdonasi melalui aplikasi pembayaran digital</strong></div>
                        <div style="margin-top: 3px; font-size: 5px;">
                            - Lembar Putih : Keuangan<br>
                            - Lembar Kuning : {{ $personLabel }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-org">
                <div style="margin-bottom: 2px;">LAZ {{ strtoupper($organizationName) }} - LEMBAGA AMIL ZAKAT RESMI SKALA PROVINSI</div>
                <div><span class="italic">IZIN DIRJEN BIMAS ISLAM KEMENTERIAN AGAMA REPUBLIK INDONESIA</span></div>
            </div>
            <div class="footer-details">
                <div class="contact-info">
                    <div>{{ $organizationAddress }}</div>
                    <div>📞 {{ $organizationPhone }}</div>
                </div>
                <div class="social-icons">
                    <div class="social-icon"></div>
                    <div class="social-icon"></div>
                    <div class="social-icon"></div>
                </div>
                <div>{{ $organizationEmail }}</div>
            </div>
        </div>
    </div>
</body>
</html>

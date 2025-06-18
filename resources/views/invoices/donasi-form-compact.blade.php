@php
    // Data processing for invoice template
    $isZakat = false;
    $zakatTypes = ['zakat maal', 'zakat fitrah', 'zakat perusahaan'];
    
    $jenisToCheck = '';
    if (isset($jenis_zakat)) {
        $jenisToCheck = strtolower(trim($jenis_zakat));
    } elseif (isset($jenis_donasi)) {
        $jenisToCheck = strtolower(trim($jenis_donasi));
    } elseif (isset($donasi) && isset($donasi->jenisDonasi->nama)) {
        $jenisToCheck = strtolower(trim($donasi->jenisDonasi->nama));
    }
    
    if (!empty($jenisToCheck)) {
        foreach ($zakatTypes as $zakatType) {
            if (stripos($jenisToCheck, $zakatType) !== false) {
                $isZakat = true;
                break;
            }
        }
    }
    
    // Set variables
    $transactionType = $isZakat ? 'ZAKAT' : 'DONASI';
    $personLabel = $isZakat ? 'Muzakki' : 'Donatur';
    $invoiceNumber = $invoice_number ?? $nomor_transaksi ?? 'INV-' . date('YmdHis');
    
    // Extract data from objects with proper fallbacks
    if (isset($donasi)) {
        $nama = $donasi->donatur->nama ?? '';
        $email = $donasi->donatur->email ?? '';
        $telepon = $donasi->donatur->nomor_hp ?? '';
        $alamat = $donasi->donatur->alamat_lengkap ?? '';
        $jenisTransaksi = $donasi->jenisDonasi->nama ?? '';
        $tanggalTransaksi = $donasi->tanggal_donasi ?? '';
        $metodePembayaran = $donasi->metodePembayaran->nama ?? '';
        $keterangan = $donasi->keterangan ?? '';
        $jumlahNumeric = $donasi->jumlah ?? 0;
        $jumlahFormatted = 'Rp ' . number_format($jumlahNumeric, 0, ',', '.');
    } else {
        $nama = $nama_donatur ?? 'N/A';
        $email = $email_donatur ?? '';
        $telepon = $telepon_donatur ?? '';
        $alamat = $alamat_donatur ?? '';
        $jenisTransaksi = $jenis_donasi ?? 'Donasi Umum';
        $tanggalTransaksi = $tanggal_donasi ?? date('Y-m-d');
        $metodePembayaran = $metode_pembayaran ?? 'Transfer Bank';
        $keterangan = $catatan ?? '';
        $jumlahFormatted = $jumlah_formatted ?? 'Rp 0';
        $jumlahNumeric = is_numeric($jumlah ?? 0) ? $jumlah : 0;
    }
    
    // Date formatting
    $tanggalFormatted = date('d/m/Y', strtotime($tanggalTransaksi));
    
    // Simple terbilang function
    function terbilang($number) {
        $x = abs($number);
        $angka = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        
        if ($x < 12) return " " . $angka[$x];
        if ($x < 20) return terbilang($x - 10) . " belas";
        if ($x < 100) return terbilang($x/10) . " puluh" . terbilang($x % 10);
        if ($x < 200) return " seratus" . terbilang($x - 100);
        if ($x < 1000) return terbilang($x/100) . " ratus" . terbilang($x % 100);
        if ($x < 2000) return " seribu" . terbilang($x - 1000);
        if ($x < 1000000) return terbilang($x/1000) . " ribu" . terbilang($x % 1000);
        if ($x < 1000000000) return terbilang($x/1000000) . " juta" . terbilang($x % 1000000);
        
        return "";
    }
    
    $jumlahTerbilang = $jumlahNumeric > 0 ? ucfirst(trim(terbilang($jumlahNumeric))) . ' rupiah' : '';
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Formulir Setoran {{ $transactionType }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        
        /* DomPDF Compatible Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        
        td, th {
            border: 1px solid #333;
            padding: 3px;
            vertical-align: top;
        }
        
        .header-bg {
            background-color: #4a5568;
            color: white;
            padding: 8px;
        }
        
        .header-table {
            width: 100%;
            border: none;
        }
        
        .header-table td {
            border: none;
            padding: 2px;
        }
        
        .title-large {
            font-size: 16px;
            font-weight: bold;
        }
        
        .title-small {
            font-size: 10px;
            font-style: italic;
        }
        
        .award-circle {
            width: 40px;
            height: 40px;
            border: 2px solid white;
            text-align: center;
            font-size: 6px;
            padding: 2px;
        }
        
        .form-section {
            padding: 10px;
        }
        
        .field-row {
            margin-bottom: 8px;
        }
        
        .field-label {
            display: inline-block;
            width: 80px;
            font-size: 8px;
        }
        
        .field-line {
            display: inline-block;
            width: 200px;
            border-bottom: 1px solid #333;
            height: 12px;
        }
        
        .tax-box {
            border: 1px solid #333;
            padding: 5px;
            background-color: #f7f7f7;
            margin: 10px 0;
        }
        
        .donation-table {
            width: 100%;
            font-size: 7px;
        }
        
        .donation-table th {
            background-color: #e2e8f0;
            font-weight: bold;
            text-align: center;
            padding: 4px;
        }
        
        .donation-table td {
            padding: 2px 4px;
        }
        
        .checkbox-col {
            width: 15px;
            text-align: center;
        }
        
        .type-col {
            width: 35%;
        }
        
        .note-col {
            width: 35%;
        }
        
        .amount-col {
            width: 20%;
        }
        
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .signature-table {
            width: 100%;
            margin-top: 10px;
        }
        
        .signature-table td {
            border: none;
            text-align: center;
            font-size: 7px;
            width: 50%;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            height: 25px;
            margin-bottom: 5px;
        }
        
        .footer-bg {
            background-color: #4a5568;
            color: white;
            padding: 5px;
            font-size: 7px;
            text-align: center;
        }
        
        .main-content {
            display: table;
            width: 100%;
        }
        
        .left-column {
            display: table-cell;
            width: 55%;
            padding: 10px;
            vertical-align: top;
        }
        
        .right-column {
            display: table-cell;
            width: 45%;
            padding: 10px;
            vertical-align: top;
        }
        
        .small-text {
            font-size: 7px;
        }
        
        .italic {
            font-style: italic;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .underline {
            text-decoration: underline;
        }
        
        .right-align {
            text-align: right;
        }
        
        .center-align {
            text-align: center;
        }
        
        .words-box {
            border: 1px solid #333;
            height: 30px;
            margin: 5px 0;
            padding: 3px;
            font-size: 7px;
        }
        
        .qr-section {
            border: 1px solid #333;
            padding: 5px;
            font-size: 6px;
        }
        
        .checkbox-checked {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-bg">
        <table class="header-table">
            <tr>
                <td style="width: 40%;">
                    <div class="title-large">Formulir Setoran {{ $transactionType }}</div>
                    <div class="title-small">Deposit {{ $transactionType }} Form</div>
                </td>
                <td style="width: 30%; text-align: center;">
                    <table style="display: inline-block; margin: 0 10px;">
                        <tr>
                            <td class="award-circle">
                                PREDIKAT<br>TERBAIK<br>KEMENAG<br>PROVINSI<br><strong>BAIK</strong>
                            </td>
                            <td class="award-circle">
                                OPINI<br>AUDIT<br>KEUANGAN<br><br><strong>WTP</strong>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 30%; background-color: white; color: #333; text-align: center; padding: 8px;">
                    <div style="font-size: 10px; margin-bottom: 5px;">No. {{ $invoiceNumber }}</div>
                    <div style="border-top: 1px solid #333; margin: 5px 0;"></div>
                    <div style="font-size: 10px;"><strong>Insan Madani Jambi</strong></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="left-column">
            <div style="font-size: 8px; margin-bottom: 5px; font-style: italic;">bismillaahirrahmaanirrahim</div>
            <div style="font-size: 8px; margin-bottom: 10px;">
                kepada to <strong>LAZ Yayasan Insan Madani Jambi</strong><br>
                mohon dicatat transaksi berikut <em>please record this transaction</em>
            </div>
            
            <div class="field-row">
                <span class="field-label">nama<br><em>name</em></span>
                <span class="field-line">{{ $nama }}</span>
            </div>
            
            <div class="field-row">
                <span class="field-label">ID {{ strtolower($personLabel) }}<br><em>{{ strtolower($personLabel) }} ID</em></span>
                <span class="field-line">{{ $invoiceNumber }}</span>
            </div>
            
            <div class="field-row">
                <span class="field-label">alamat<br><em>address</em></span>
                <span class="field-line">{{ $alamat }}</span>
            </div>
            
            <div class="field-row">
                <span class="field-label"></span>
                <span class="field-line"></span>
            </div>
            
            <div class="field-row">
                <span class="field-label">nomor telepon<br><em>phone number</em></span>
                <span class="field-line">{{ $telepon }}</span>
            </div>
            
            <div class="tax-box">
                <div style="font-size: 8px; font-weight: bold; margin-bottom: 5px;">
                    untuk pengurang pajak penghasilan <em>for income tax reduction</em>
                </div>
                <div style="font-size: 7px; margin-bottom: 3px;">Nomor Pokok Wajib Pajak (NPWP)</div>
                <div style="border: 1px solid #333; height: 15px; margin-bottom: 5px;"></div>
                <div class="small-text">
                    Diisi sebagai lampiran SPT Tahunan Pajak Penghasilan untuk pengurang Penghasilan 
                    Kena Pajak (PKP) sesuai keputusan Dirjen Pajak No. KEP-163/PJ/2003
                </div>
                
                <table class="signature-table">
                    <tr>
                        <td>
                            <div class="signature-line"></div>
                            Tanda Tangan Penyetor<br><em>depositors signature</em>
                        </td>
                        <td>
                            <div class="signature-line"></div>
                            Pengesahan Petugas Amil<br><em>amil officer authentication</em>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="right-column">
            <table style="margin-bottom: 10px; border: none;">
                <tr>
                    <td style="border: none; font-size: 8px;">
                        tanggal <em>date</em><br>
                        <div style="border-bottom: 1px solid #333; height: 12px;">{{ $tanggalFormatted }}</div>
                    </td>
                    <td style="border: none; font-size: 8px;">
                        cabang <em>branch</em><br>
                        <div style="border-bottom: 1px solid #333; height: 12px;">Jambi</div>
                    </td>
                    <td style="border: none; font-size: 8px;">
                        valuta <em>currency</em><br>
                        <div style="border-bottom: 1px solid #333; height: 12px;">IDR</div>
                    </td>
                </tr>
            </table>
            
            <table class="donation-table">
                <thead>
                    <tr>
                        <th class="type-col">jenis {{ strtolower($transactionType) }}<br><em>kind of {{ strtolower($transactionType) }}</em></th>
                        <th class="note-col">uraian<br><em>note</em></th>
                        <th class="amount-col">nominal<br><em>amount</em></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="{{ $isZakat ? 'checkbox-checked' : '' }}">{{ $isZakat ? '☑' : '☐' }}</span> zakat<br><em>zakat</em></td>
                        <td>{{ $isZakat ? $jenisTransaksi : '' }}</td>
                        <td>{{ $isZakat ? $jumlahFormatted : '' }}</td>
                    </tr>
                    <tr>
                        <td><span class="{{ !$isZakat ? 'checkbox-checked' : '' }}">{{ !$isZakat ? '☑' : '☐' }}</span> infak/sedekah<br><em>donation</em></td>
                        <td>{{ !$isZakat ? $jenisTransaksi : '' }}</td>
                        <td>{{ !$isZakat ? $jumlahFormatted : '' }}</td>
                    </tr>
                    <tr>
                        <td>☐ infak DSKL</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            
            <table class="donation-table">
                <thead>
                    <tr>
                        <th class="type-col">berupa<br><em>consist of</em></th>
                        <th class="note-col">penerbit/nomor<br><em>issued by/number</em></th>
                        <th class="amount-col">nominal<br><em>amount</em></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="{{ strtolower($metodePembayaran) === 'cash' || strtolower($metodePembayaran) === 'tunai' ? 'checkbox-checked' : '' }}">{{ strtolower($metodePembayaran) === 'cash' || strtolower($metodePembayaran) === 'tunai' ? '☑' : '☐' }}</span> tunai<br><em>cash</em></td>
                        <td>{{ strtolower($metodePembayaran) === 'cash' || strtolower($metodePembayaran) === 'tunai' ? $metodePembayaran : '' }}</td>
                        <td>{{ strtolower($metodePembayaran) === 'cash' || strtolower($metodePembayaran) === 'tunai' ? $jumlahFormatted : '' }}</td>
                    </tr>
                    <tr>
                        <td><span class="{{ stripos($metodePembayaran, 'transfer') !== false || stripos($metodePembayaran, 'bank') !== false ? 'checkbox-checked' : '' }}">{{ stripos($metodePembayaran, 'transfer') !== false || stripos($metodePembayaran, 'bank') !== false ? '☑' : '☐' }}</span> transfer<br><em>transfer</em></td>
                        <td>{{ stripos($metodePembayaran, 'transfer') !== false || stripos($metodePembayaran, 'bank') !== false ? $metodePembayaran : '' }}</td>
                        <td>{{ stripos($metodePembayaran, 'transfer') !== false || stripos($metodePembayaran, 'bank') !== false ? $jumlahFormatted : '' }}</td>
                    </tr>
                    <tr>
                        <td><span class="{{ stripos($metodePembayaran, 'card') !== false || stripos($metodePembayaran, 'debit') !== false ? 'checkbox-checked' : '' }}">{{ stripos($metodePembayaran, 'card') !== false || stripos($metodePembayaran, 'debit') !== false ? '☑' : '☐' }}</span> cek/debet<br><em>cheque/debit card</em></td>
                        <td>{{ stripos($metodePembayaran, 'card') !== false || stripos($metodePembayaran, 'debit') !== false ? $metodePembayaran : '' }}</td>
                        <td>{{ stripos($metodePembayaran, 'card') !== false || stripos($metodePembayaran, 'debit') !== false ? $jumlahFormatted : '' }}</td>
                    </tr>
                    <tr>
                        <td>☐ barang/jasa<br><em>goods/services</em></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>TOTAL</strong></td>
                        <td></td>
                        <td><strong>{{ $jumlahFormatted }}</strong></td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin: 10px 0;">
                <div class="small-text bold">terbilang <em>in words</em></div>
                <div class="words-box">{{ $jumlahTerbilang ?: $keterangan }}</div>
            </div>
            
            <div class="qr-section">
                <div style="float: left; width: 50px; height: 50px; border: 1px solid #333; margin-right: 10px; text-align: center; padding: 15px 0; font-size: 6px;">QR CODE</div>
                <div style="font-size: 6px;">
                    <strong>scan QR Code ini untuk berdonasi melalui aplikasi pembayaran digital</strong><br><br>
                    Semoga Allah memberikan pahala atas apa yang telah Anda berikan, menyucikan harta 
                    dan jiwa, serta memberikan berkah yang berlimpah kepada keluarga Anda yang tersayang<br><br>
                    <strong>- Lembar Putih : Keuangan &nbsp;&nbsp;&nbsp; - Lembar Kuning : {{ $personLabel }}</strong>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-bg">
        <div style="font-weight: bold; margin-bottom: 3px;">
            LAZ INSAN MADANI JAMBI - LEMBAGA AMIL ZAKAT RESMI SKALA PROVINSI
        </div>
        <div style="font-style: italic; margin-bottom: 5px;">
            IZIN DIRJEN BIMAS ISLAM KEMENTERIAN AGAMA REPUBLIK INDONESIA
        </div>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none;">Head Office : Jl. Otto Iskandardinata No. 15 Kel. Sei Asam Kec. Pasar Jambi Kota Jambi</td>
                <td style="border: none;">📞 0811.743.1231</td>
                <td style="border: none;">📱 📧 🌐</td>
                <td style="border: none;">insanmadanijambi.org</td>
            </tr>
        </table>
    </div>
</body>
</html>

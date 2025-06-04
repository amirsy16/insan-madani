<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perubahan Dana</title>
    <style>
        /* PDF-specific styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background-color: #fff;
            margin: 30px;
            padding: 0;
        }
        
        /* Additional page break rules */
        .keep-together {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            orphans: 5;
            widows: 5;
        }
        
        /* Force new page if necessary */
        .force-new-page {
            page-break-before: always !important;
        }
        
        /* Prevent any breaks in signature area */
        .signature-wrapper {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            page-break-before: auto;
            page-break-after: avoid;
            display: block !important;
            min-height: 350px;
            orphans: 10;
            widows: 10;
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 25px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px 30px;
            border-bottom: 3px solid #2c5aa0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
        }
        
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: #2c5aa0;
            letter-spacing: 1px;
        }
        
        .header h2 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: #495057;
        }
        
        .period-info {
            font-size: 11px;
            margin-bottom: 5px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .report-table th {
            padding: 12px 15px;
            text-align: center;
            font-weight: bold;
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3d72 100%);
            color: white;
            border: 1px solid #dee2e6;
        }
        
        .report-table td {
            padding: 8px 15px;
            vertical-align: top;
            border: 1px solid #dee2e6;
        }
        
        .section-header {
            font-weight: bold;
            text-align: left;
            background: linear-gradient(90deg, #e3f2fd 0%, #f8f9fa 100%);
            padding: 12px 15px !important;
            color: #1976d2;
            border-left: 4px solid #2196f3;
        }
        
        .subsection-header {
            font-weight: bold;
            padding: 8px 15px;
            padding-left: 25px;
        }
        
        .item-detail {
            padding: 6px 15px;
            padding-left: 35px;
        }
        
        .amount {
            text-align: right;
            font-family: monospace;
            padding-right: 20px !important;
        }
        
        .total-line {
            font-weight: bold;
        }
        
        .highlight {
            background: linear-gradient(45deg, #fff3cd 0%, #ffeaa7 100%);
            font-weight: bold;
            color: #856404;
        }
        
        /* Signature Section - Compact version */
        .signature-section {
            margin: 20px auto 10px auto;
            page-break-inside: avoid !important;
            page-break-before: avoid;
            break-inside: avoid;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            max-width: 85%;
            min-height: 120px;
            orphans: 5;
            widows: 5;
        }
        
        .signature-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #2c5aa0;
        }
        
        .signature-header h3 {
            font-size: 10px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 3px;
        }
        
        .signature-header p {
            font-size: 8px;
            color: #6c757d;
            margin: 0;
        }
        
        .signature-container {
            display: table;
            width: 100%;
            margin-top: 10px;
            page-break-inside: avoid !important;
            break-inside: avoid;
        }
        
        .signature-left {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-right: 15px;
        }
        
        .signature-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-left: 15px;
        }
        
        .signature-box {
            border: 1px solid #dee2e6;
            border-radius: 3px;
            padding: 10px;
            background-color: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            margin: 5px 0;
            page-break-inside: avoid !important;
            break-inside: avoid;
            min-height: 80px;
        }
        
        .signature-title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 3px;
            color: #495057;
        }
        
        .signature-space {
            height: 35px;
            border-bottom: 1px dotted #6c757d;
            margin: 8px 0;
            position: relative;
        }
        
        .signature-space::after {
            content: "Tanda Tangan";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 6px;
            color: #6c757d;
            font-style: italic;
        }
        
        .signature-name {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 2px;
            color: #212529;
        }
        
        .signature-position {
            font-size: 7px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        /* Balance Summary - Compact version */
        .balance-summary {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
            border: 1px solid #28a745;
            border-radius: 5px;
            padding: 12px;
            margin: 15px auto 5px auto;
            text-align: center;
            max-width: 70%;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        
        .balance-summary h3 {
            color: #155724;
            font-size: 10px;
            margin-bottom: 5px;
        }
        
        .balance-amount {
            font-size: 12px;
            font-weight: bold;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
    {{-- Header --}}
    <div class="header">
        @php
            $logoPath = public_path('images/LOGOIM.jpg');
            $logoBase64 = '';
            if (file_exists($logoPath)) {
                $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
            }
        @endphp
        
        @if($logoBase64)
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="{{ $logoBase64 }}" alt="Logo Insan Madani" style="height: 60px; width: auto;">
            </div>
        @endif
        
        <h1>LAPORAN PERUBAHAN DANA</h1>
        <h2>YAYASAN MADANI BERKELANJUTAN</h2>
        <div class="period-info">
            Periode {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s.d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
        </div>
    </div>

    {{-- Main Report Table --}}
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 70%;">Keterangan</th>
                <th style="width: 30%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @php
                $filteredReportData = collect($reportData)->filter(function($data, $key) {
                    return $key !== 'summary' && is_array($data) && isset($data['title']);
                });
                $sectionLetter = 'A';
            @endphp
            
            @foreach($filteredReportData as $fundKey => $data)
            {{-- Section Header --}}
            <tr>
                <td class="section-header">{{ $sectionLetter }}. {{ strtoupper($data['title']) }}</td>
                <td class="amount"></td>
            </tr>
            
            {{-- 1. Penerimaan Dana --}}
            <tr>
                <td class="subsection-header">1. Penerimaan Dana</td>
                <td class="amount"></td>
            </tr>
            
            {{-- Detail Penerimaan --}}
            @if(isset($data['rincian_penerimaan']) && count($data['rincian_penerimaan']) > 0)
                @foreach($data['rincian_penerimaan'] as $jenis => $jumlah)
                <tr>
                    <td class="item-detail">- {{ $jenis }}</td>
                    <td class="amount">{{ number_format($jumlah, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @endif
            
            {{-- Total Penerimaan --}}
            <tr>
                <td class="amount"></td>
                <td class="amount total-line">{{ number_format($data['penerimaan'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            
            {{-- Bagian Amil (jika bukan Hak Amil) --}}
            @if(isset($data['bagian_amil']) && $data['bagian_amil'] > 0 && strtolower($data['title']) !== 'hak amil')
            <tr>
                <td class="item-detail">Bagian Amil</td>
                <td class="amount">{{ number_format($data['bagian_amil'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="amount"></td>
                <td class="amount total-line">{{ number_format(($data['penerimaan'] ?? 0) - ($data['bagian_amil'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            @endif
            
            {{-- 2. Penyaluran Dana --}}
            <tr>
                <td class="subsection-header">2. Penyaluran Dana</td>
                <td class="amount"></td>
            </tr>
            
            {{-- 2.1 Penyaluran berdasarkan Asnaf --}}
            @if(isset($data['rincian_penyaluran_asnaf']) && count($data['rincian_penyaluran_asnaf']) > 0)
            <tr>
                <td class="subsection-header">2.1 Penyaluran Dana berdasarkan Asnaf</td>
                <td class="amount"></td>
            </tr>
            
            {{-- Detail per Asnaf --}}
            @foreach($data['rincian_penyaluran_asnaf'] as $asnaf => $jumlah)
                @if(strtolower($data['title']) === 'dana infaq/sedekah')
                    {{-- Untuk Dana Infaq/Sedekah, tampilkan sub-detail --}}
                    <tr>
                        <td class="subsection-header">2.1.{{ chr(96 + $loop->iteration) }} Penyaluran {{ $asnaf }}</td>
                        <td class="amount"></td>
                    </tr>
                    @if(isset($data['detail_penyaluran_' . strtolower(str_replace(' ', '_', $asnaf))])  && is_array($data['detail_penyaluran_' . strtolower(str_replace(' ', '_', $asnaf))]))
                        @foreach($data['detail_penyaluran_' . strtolower(str_replace(' ', '_', $asnaf))] as $subJenis => $subJumlah)
                        <tr>
                            <td class="item-detail">- {{ $subJenis }}</td>
                            <td class="amount">{{ $subJumlah > 0 ? number_format($subJumlah, 0, ',', '.') : '-' }}</td>
                        </tr>
                        @endforeach
                    @endif
                    <tr>
                        <td class="amount"></td>
                        <td class="amount total-line">{{ $jumlah > 0 ? number_format($jumlah, 0, ',', '.') : '-' }}</td>
                    </tr>
                @else
                    {{-- Untuk yang lain, tampilkan langsung --}}
                    <tr>
                        <td class="item-detail">- {{ $asnaf }}</td>
                        <td class="amount">{{ $jumlah > 0 ? number_format($jumlah, 0, ',', '.') : '-' }}</td>
                    </tr>
                @endif
            @endforeach
            
            <tr>
                <td class="amount"></td>
                <td class="amount total-line">{{ number_format(array_sum($data['rincian_penyaluran_asnaf']), 0, ',', '.') }}</td>
            </tr>
            @endif
            
            {{-- 2.2 Penyaluran berdasarkan Bidang Program --}}
            @if(isset($data['rincian_penyaluran']) && count($data['rincian_penyaluran']) > 0)
            <tr>
                <td class="subsection-header">2.2 Penyaluran Dana berdasarkan Bidang Program</td>
                <td class="amount"></td>
            </tr>
            @foreach($data['rincian_penyaluran'] as $bidang => $jumlah)
                <tr>
                    <td class="item-detail">- {{ $bidang }}</td>
                    <td class="amount">{{ $jumlah > 0 ? number_format($jumlah, 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="amount"></td>
                <td class="amount total-line">{{ number_format(array_sum($data['rincian_penyaluran']), 0, ',', '.') }}</td>
            </tr>
            @endif
            
            {{-- Total Penyaluran --}}
            <tr>
                <td class="amount"></td>
                <td class="amount total-line">{{ number_format($data['penyaluran'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            
            {{-- Surplus/Defisit --}}
            <tr>
                <td class="subsection-header">Surplus (Defisit)</td>
                <td class="amount">{{ ($data['surplus_defisit'] ?? 0) < 0 ? '(' . number_format(abs($data['surplus_defisit']), 0, ',', '.') . ')' : number_format($data['surplus_defisit'], 0, ',', '.') }}</td>
            </tr>
            
            {{-- Saldo Awal --}}
            <tr>
                <td class="subsection-header">Saldo Awal</td>
                <td class="amount">{{ number_format($data['saldo_awal'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            
            {{-- Saldo Akhir --}}
            <tr>
                <td class="subsection-header highlight">Saldo Akhir</td>
                <td class="amount highlight">{{ number_format($data['saldo_akhir'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            
            @php $sectionLetter++; @endphp
            @endforeach
            
            {{-- Hak Amil Section --}}
            @if(!empty($penerimaanHakAmilDetail) || !empty($penggunaanHakAmilDetail) || ($totalPenerimaanHakAmil ?? 0) > 0 || ($totalPenggunaanHakAmil ?? 0) > 0)
            <tr>
                <td class="section-header">{{ $sectionLetter }}. PENERIMAAN DAN PENGGUNAAN HAK AMIL (dalam rupiah)</td>
                <td class="amount"></td>
            </tr>
            
            {{-- 1. Penerimaan Hak Amil --}}
            <tr>
                <td class="subsection-header">1. Penerimaan Hak Amil</td>
                <td class="amount"></td>
            </tr>
            
            {{-- Detail Penerimaan Hak Amil --}}
            @if(!empty($penerimaanHakAmilDetail))
                @foreach($penerimaanHakAmilDetail as $jenis => $jumlah)
                <tr>
                    <td class="item-detail">- {{ $jenis }}</td>
                    <td class="amount">{{ number_format($jumlah ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td class="item-detail">- Penerimaan hak amil dari zakat asnaf amil (maksimal 12.5%)</td>
                    <td class="amount">{{ number_format($totalPenerimaanHakAmil ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="item-detail">- Penerimaan hak amil dari zakat asnaf Fisabilillah</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Penerimaan hak amil dari Infaq</td>
                    <td class="amount">{{ isset($summaryData['total_bagian_amil_infaq']) ? number_format($summaryData['total_bagian_amil_infaq'], 0, ',', '.') : '-' }}</td>
                </tr>
                <tr>
                    <td class="item-detail">- Penerimaan hak amil dari dana CSR</td>
                    <td class="amount">{{ isset($summaryData['total_bagian_amil_csr']) ? number_format($summaryData['total_bagian_amil_csr'], 0, ',', '.') : '-' }}</td>
                </tr>
                <tr>
                    <td class="item-detail">- Penerimaan hak amil dari DSKL</td>
                    <td class="amount">{{ isset($summaryData['total_bagian_amil_dskl']) ? number_format($summaryData['total_bagian_amil_dskl'], 0, ',', '.') : '-' }}</td>
                </tr>
                <tr>
                    <td class="item-detail">- Penerimaan bagi hasil atas penempatan hak amil</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Penerimaan hasil penjualan aset tetap operasional</td>
                    <td class="amount">-</td>
                </tr>
            @endif
            
            <tr>
                <td class="amount"></td>
                <td class="amount total-line">{{ number_format($totalPenerimaanHakAmil ?? 0, 0, ',', '.') }}</td>
            </tr>
            
            {{-- 2. Penggunaan Hak Amil --}}
            <tr>
                <td class="subsection-header">2. Penggunaan Hak Amil</td>
                <td class="amount"></td>
            </tr>
            
            {{-- Detail Penggunaan Hak Amil --}}
            @if(!empty($penggunaanHakAmilDetail))
                @foreach($penggunaanHakAmilDetail as $jenis => $jumlah)
                <tr>
                    <td class="item-detail">- {{ $jenis }}</td>
                    <td class="amount">{{ number_format($jumlah ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td class="item-detail">- Belanja Pegawai</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Biaya Publikasi dan Dokumentasi</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Biaya Perjalanan Dinas</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Beban Administrasi Umum</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Beban Penyusutan, Pemeliharaan dan Penghapusan</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Pengadaan Aset Tetap</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Biaya Jasa Pihak Ketiga</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Biaya Rumah Tangga / Protokoler Yayasan</td>
                    <td class="amount">-</td>
                </tr>
                <tr>
                    <td class="item-detail">- Penggunaan Lain Hak Amil</td>
                    <td class="amount">-</td>
                </tr>
            @endif
            
            <tr>
                <td class="amount"></td>
                <td class="amount total-line">{{ number_format($totalPenggunaanHakAmil ?? 0, 0, ',', '.') }}</td>
            </tr>
            
            {{-- Surplus/Defisit Hak Amil --}}
            <tr>
                <td class="subsection-header">Surplus (defisit)</td>
                <td class="amount">{{ ($surplusDefisitHakAmil ?? 0) < 0 ? '(' . number_format(abs($surplusDefisitHakAmil), 0, ',', '.') . ')' : number_format($surplusDefisitHakAmil, 0, ',', '.') }}</td>
            </tr>
            
            {{-- Saldo Awal Hak Amil --}}
            <tr>
                <td class="subsection-header">Saldo Awal</td>
                <td class="amount">{{ number_format(($summaryData['saldo_awal_hak_amil'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            
            {{-- Saldo Akhir Hak Amil --}}
            <tr>
                <td class="subsection-header highlight">Saldo Akhir</td>
                <td class="amount highlight">{{ number_format(($summaryData['saldo_awal_hak_amil'] ?? 0) + ($surplusDefisitHakAmil ?? 0), 0, ',', '.') }}</td>
            </tr>
            @endif
            
        </tbody>
    </table>

    {{-- Balance Summary Section --}}
    @php
        $totalSaldoAkhir = 0;
        foreach($filteredReportData as $data) {
            $totalSaldoAkhir += $data['saldo_akhir'] ?? 0;
        }
        if(isset($summaryData['saldo_awal_hak_amil']) && isset($surplusDefisitHakAmil)) {
            $totalSaldoAkhir += ($summaryData['saldo_awal_hak_amil'] + $surplusDefisitHakAmil);
        }
    @endphp
    
    {{-- Balance Summary and Signature Section - Compact Layout --}}
    <div class="keep-together" style="page-break-inside: avoid !important; break-inside: avoid;">
        <div class="balance-summary">
            <h3>Saldo Dana Zakat, Infaq/Sedekah, CSR, DSKL, Amil dan Non Halal</h3>
            <div class="balance-amount">{{ number_format($totalSaldoAkhir, 0, ',', '.') }}</div>
        </div>

        {{-- Signature Section - Compact --}}
        <div class="signature-section">
        <div class="signature-header">
            <h3>LAZ Insan Madani Jambi</h3>
            <p>Laporan ini telah diperiksa dan disetujui oleh:</p>
        </div>
        
        <div class="signature-container">
            <div class="signature-left">
                <div class="signature-box">
                    <div class="signature-title">Diketahui Oleh:</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">FUJI LESTARI, S.E.</div>
                    <div class="signature-position">Direktur Eksekutif</div>
                </div>
            </div>
            
            <div class="signature-right">
                <div class="signature-box">
                    <div class="signature-title">Disusun Oleh:</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">JOKO NURHADI</div>
                    <div class="signature-position">Bendahara</div>
                </div>
            </div>
            </div>
        </div>
    </div>

    </div>

</body>
</html>

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
            font-size: 12px; /* Increased from 10px */
            line-height: 1.3;
            color: #000000; /* Black text */
            background-color: #fff;
            margin: 20px; /* Reduced from 30px */
            padding: 0;
        }

        .container {
            max-width: 95%; /* Set for centering, was 100% */
            margin: 0 auto;
            padding: 20px; /* Reduced from 25px */
            background-color: #ffffff;
            /* border: 1px solid #dee2e6; */ /* Optional: remove for less boxing */
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05); /* Subtle black shadow */
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px; /* Reduced from 25px */
            padding: 15px 20px; /* Reduced from 20px 30px */
            border-bottom: 3px solid #000000; /* Black border */
            background: #f5f5f5; /* Light gray background */
            border-radius: 8px;
        }
        
        .header h1 {
            font-size: 20px; /* Increased from 16px */
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: #000000; /* Black text */
            letter-spacing: 1px;
        }
        
        .header h2 {
            font-size: 16px; /* Increased from 14px */
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: #000000; /* Black text */
        }
        
        .period-info {
            font-size: 12px; /* Increased from 11px */
            margin-bottom: 5px;
            color: #333333; /* Dark gray for less emphasis */
            font-weight: 500;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px auto; /* Reduced from 20px auto */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Subtle black shadow */
        }

        .report-table th {
            padding: 10px 12px; /* Adjusted from 12px 15px */
            text-align: center;
            font-weight: bold;
            background: #f5f5f5; /* Light gray background */
            color: #000000; /* Black text */
            /* border: 1px solid #dee2e6; */ /* Grid line removed */
            border-bottom: 2px solid #000000; /* Black border */
            font-size: 13px; /* Added for clarity */
        }

        .report-table td {
            padding: 8px 12px; /* Adjusted from 8px 15px */
            vertical-align: top;
            /* border: 1px solid #dee2e6; */ /* Grid line removed */
            border-bottom: 1px solid #cccccc; /* Light gray border for row separation */
        }

        .section-header {
            font-weight: bold;
            text-align: left;
            background: #f5f5f5; /* Light gray background */
            padding: 10px 12px !important; /* Adjusted from 12px 15px */
            color: #000000; /* Black text */
            border-left: 4px solid #000000; /* Black border */
            font-size: 14px; /* Added for clarity */
        }

        .subsection-header {
            font-weight: bold;
            padding: 8px 12px; /* Adjusted */
            padding-left: 20px; /* Adjusted from 25px */
            font-size: 13px; /* Added for clarity */
        }

        .item-detail {
            padding: 6px 12px; /* Adjusted */
            padding-left: 30px; /* Adjusted from 35px */
        }

        .amount {
            text-align: right;
            font-family: monospace;
            padding-right: 15px !important; /* Adjusted from 20px */
        }
        
        .total-line {
            font-weight: bold;
            border-top: 1px solid #aaaaaa; /* Gray border for totals */
        }
        
        .highlight {
            background-color: yellow !important; /* Yellow highlighter */
            color: #000000 !important; /* Black text */
            background-image: none !important; /* Remove any gradient */
            font-weight: bold;
        }
        
        /* Simple Balance Summary - styles removed as it's handled by inline styles now if restored */
        /* .balance-summary { ... } */
        /* .balance-summary h3 { ... } */
        /* .balance-amount { ... } */
        
        /* Footer info */
        .footer-info {
            text-align: center;
            margin-top: 25px; /* Reduced from 30px */
            padding: 10px; /* Reduced from 15px */
            border-top: 1px solid #cccccc; /* Light gray border */
            color: #333333; /* Dark gray text */
            font-size: 10px; /* Increased from 9px */
        }
        
        .footer-info .organization {
            font-weight: bold;
            color: #000000; /* Black text */
            margin-bottom: 5px;
        }
        
        /* Simple Signature Section */
        .signature-section {
            margin: 25px auto 15px auto; /* Adjusted from 30px auto 20px auto */
            max-width: 100%;
        }

        .signature-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 15px 0; /* Reduced from 20px 0 */
        }

        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 8px; /* Reduced from 0 10px */
        }

        .signature-title {
            font-size: 11px; /* Increased from 9px */
            margin-bottom: 30px; /* Reduced from 40px */
            color: #000000; /* Black text */
        }

        .signature-space {
            height: 35px; /* Reduced from 40px */
            margin-bottom: 5px;
        }

        .signature-name {
            font-size: 11px; /* Increased from 9px */
            font-weight: bold;
            color: #000000; /* Black text */
            text-decoration: underline;
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
        <h2>YAYASAN INSAN MADANI</h2>
        <div class="period-info">
            Periode {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s.d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
        </div>
    </div>

    {{-- Main Report Table (same as original) --}}
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
            
            {{-- Hak Amil Section (same as original) --}}
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

    {{-- Balance Summary Section - Restored without box --}}
    @php
        $totalSaldoAkhir = 0;
        foreach($filteredReportData as $data) {
            $totalSaldoAkhir += $data['saldo_akhir'] ?? 0;
        }
        if(isset($summaryData['saldo_awal_hak_amil']) && isset($surplusDefisitHakAmil)) {
            $totalSaldoAkhir += ($summaryData['saldo_awal_hak_amil'] + $surplusDefisitHakAmil);
        }
    @endphp
    
    <div style="text-align: center; margin-top: 25px; margin-bottom: 25px;">
        <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 8px; color: #000000;">Total Saldo Dana Zakat, Infaq/Sedekah, CSR, DSKL, Amil dan Non Halal</h3>
        <div style="font-size: 16px; font-weight: bold; color: #000000;">Rp {{ number_format($totalSaldoAkhir, 0, ',', '.') }}</div>
    </div>

    {{-- Simple Signature Section --}}
    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td style="width: 50%;">
                    <div class="signature-title">Direktur Eksekutif</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">(_______________________)</div>
                </td>
                <td style="width: 50%;">
                    <div class="signature-title">Bendahara</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">(_______________________)</div>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>

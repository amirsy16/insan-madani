<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perubahan Dana</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px 0;
            border-bottom: 2px solid #000;
        }

        .logo-section {
            flex: 0 0 120px;
            text-align: center;
        }

        .logo-section img {
            max-width: 100px;
            height: auto;
        }

        .title-section {
            flex: 1;
            text-align: center;
            font-weight: bold;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }

        .organization-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .period {
            font-size: 14px;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        td,
        th {
            padding: 6px;
            text-align: left;
            vertical-align: top;
            border: none;
        }

        .section-title {
            font-weight: bold;
            font-size: 14px;
        }

        .subsection {
            font-weight: bold;
            padding-left: 20px;
        }

        .indented {
            padding-left: 40px;
        }

        .double-indented {
            padding-left: 60px;
        }
        
        .triple-indented {
            padding-left: 80px;
        }

        .amount {
            text-align: right;
            font-weight: normal;
            white-space: nowrap;
        }

        .total-row {
            font-weight: bold;
        }

        .total-amount {
            text-align: right;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .subtotal-amount {
            text-align: right;
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        .final-total {
            text-align: right;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .saldo-akhir {
            background-color: #ffff00;
            font-weight: bold;
            text-align: right;
        }

        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }

        .signature {
            text-align: center;
            width: 40%;
            min-width: 200px;
            margin-top: 15px;
        }

        .signature-name {
            text-decoration: underline;
            margin-top: 50px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
            <div class="logo-section">
                @php
                    $logoPath = public_path('images/LOGOIM.png');
                    $logoBase64 = '';
                    if (file_exists($logoPath)) {
                        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
                    }
                @endphp
                
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo Insan Madani">
                @else
                    <div style="width: 120px; height: 80px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                        LOGO
                    </div>
                @endif
            </div>
            
            <div class="title-section">
                <div class="report-title">LAPORAN PERUBAHAN DANA</div>
                <div class="organization-name">LAZ INSAN MADANI JAMBI</div>
                <div class="period">Periode {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s.d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</div>
            </div>
        </div>

        <table style="margin-top: 15px;">
            <tr style="border-top: 2px solid #000; border-bottom: 2px solid #000;">
                <td style="padding: 8px; font-weight: bold; text-align: center; border-right: 1px solid #000;">Keterangan</td>
                <td style="padding: 8px; font-weight: bold; text-align: center;">Jumlah</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="section-title">A DANA ZAKAT</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">1 Penerimaan Dana</td>
                <td class="amount"></td>
            </tr>
            @php
                $danaZakat = $reportData[1] ?? [];
                $rincianPenerimaan = $danaZakat['rincian_penerimaan'] ?? [];
            @endphp
            @foreach($rincianPenerimaan as $jenis => $jumlah)
            <tr>
                <td class="indented">- {{ $jenis }}</td>
                <td class="amount">{{ number_format($jumlah, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="indented"></td>
                <td class="total-amount">{{ number_format($danaZakat['penerimaan'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">Bagian Amil</td>
                <td class="subtotal-amount">{{ number_format($danaZakat['bagian_amil'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented"></td>
                <td class="amount">{{ number_format(($danaZakat['penerimaan'] ?? 0) - ($danaZakat['bagian_amil'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">2 Penyaluran Dana</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">2.1 Penyaluran Dana berdasarkan Asnaf</td>
                <td class="amount"></td>
            </tr>
            @php
                $rincianAsnaf = $danaZakat['rincian_penyaluran_asnaf'] ?? [];
            @endphp
            @foreach($rincianAsnaf as $asnaf => $jumlah)
            <tr>
                <td class="indented">- {{ $asnaf }}</td>
                <td class="amount">{{ $jumlah > 0 ? number_format($jumlah, 0, ',', '.') : '-' }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="indented"></td>
                <td class="subtotal-amount">{{ number_format(array_sum($rincianAsnaf), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">2.2 Penyaluran Dana berdasarkan Bidang Program</td>
                <td class="amount"></td>
            </tr>
            @php
                $rincianProgram = $danaZakat['rincian_penyaluran'] ?? [];
            @endphp
            @foreach($rincianProgram as $program => $jumlah)
            <tr>
                <td class="indented">- {{ $program }}</td>
                <td class="amount">{{ $jumlah > 0 ? number_format($jumlah, 0, ',', '.') : '-' }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="indented"></td>
                <td class="subtotal-amount">{{ number_format(array_sum($rincianProgram), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented"></td>
                <td class="final-total">{{ number_format($danaZakat['penyaluran'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Surplus (defisit)</td>
                <td class="amount">{{ ($danaZakat['surplus_defisit'] ?? 0) < 0 ? '('.number_format(abs($danaZakat['surplus_defisit'] ?? 0), 0, ',', '.').')' : number_format($danaZakat['surplus_defisit'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Awal</td>
                <td class="amount">{{ number_format($danaZakat['saldo_awal'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Akhir</td>
                <td class="saldo-akhir">{{ number_format($danaZakat['saldo_akhir'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="section-title">B DANA INFAQ/SEDEKAH</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">1 Penerimaan Dana</td>
                <td class="amount"></td>
            </tr>
            @php
                $danaInfaq = $reportData[2] ?? [];
                $rincianPenerimaanInfaq = $danaInfaq['rincian_penerimaan'] ?? [];
            @endphp
            @foreach($rincianPenerimaanInfaq as $jenis => $jumlah)
            <tr>
                <td class="indented">- {{ $jenis }}</td>
                <td class="amount">{{ number_format($jumlah, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="indented"></td>
                <td class="total-amount">{{ number_format($danaInfaq['penerimaan'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">Bagian Amil</td>
                <td class="subtotal-amount">{{ number_format($danaInfaq['bagian_amil'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented"></td>
                <td class="amount">{{ number_format(($danaInfaq['penerimaan'] ?? 0) - ($danaInfaq['bagian_amil'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">2 Penyaluran Dana</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">2.1 Penyaluran Dana berdasarkan Bidang Program</td>
                <td class="amount"></td>
            </tr>
            @php
                $rincianProgramInfaq = $danaInfaq['rincian_penyaluran'] ?? [];
            @endphp
            <tr>
                <td class="indented">2.1.a Penyaluran Infaq Umum</td>
                <td class="amount"></td>
            </tr>
            @foreach($rincianProgramInfaq as $program => $jumlah)
            @php
                $jumlahUmum = round($jumlah * 0.4);
            @endphp
            <tr>
                <td class="double-indented">- {{ $program }}</td>
                <td class="amount">{{ $jumlahUmum > 0 ? number_format($jumlahUmum, 0, ',', '.') : '-' }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="indented"></td>
                <td class="subtotal-amount">{{ number_format(array_sum($rincianProgramInfaq) * 0.4, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">2.1.b Penyaluran Infaq Khusus</td>
                <td class="amount"></td>
            </tr>
            @foreach($rincianProgramInfaq as $program => $jumlah)
            @php
                $jumlahKhusus = round($jumlah * 0.6);
            @endphp
            <tr>
                <td class="double-indented">- {{ $program }}</td>
                <td class="amount">{{ $jumlahKhusus > 0 ? number_format($jumlahKhusus, 0, ',', '.') : '-' }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="indented"></td>
                <td class="subtotal-amount">{{ number_format(array_sum($rincianProgramInfaq) * 0.6, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">Total Penyaluran</td>
                <td class="final-total">{{ number_format($danaInfaq['penyaluran'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Surplus (defisit)</td>
                <td class="amount">{{ ($danaInfaq['surplus_defisit'] ?? 0) < 0 ? '('.number_format(abs($danaInfaq['surplus_defisit'] ?? 0), 0, ',', '.').')' : number_format($danaInfaq['surplus_defisit'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Awal</td>
                <td class="amount">{{ number_format($danaInfaq['saldo_awal'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Akhir</td>
                <td class="saldo-akhir">{{ number_format($danaInfaq['saldo_akhir'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="section-title">C DANA CORPORATE SOCIAL RESPONSIBILITY</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">1 Penerimaan Dana</td>
                <td class="amount"></td>
            </tr>
            @php
                $danaCSR = $reportData[3] ?? [];
                $rincianPenerimaanCSR = $danaCSR['rincian_penerimaan'] ?? [];
            @endphp
            @foreach($rincianPenerimaanCSR as $jenis => $jumlah)
            <tr>
                <td class="indented">- {{ $jenis }}</td>
                <td class="amount">{{ number_format($jumlah, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @if(empty($rincianPenerimaanCSR))
            <tr>
                <td class="indented">- Corporate Social Responsibility (CSR)</td>
                <td class="amount">{{ number_format($danaCSR['penerimaan'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="indented">Bagian Amil</td>
                <td class="amount">{{ number_format($danaCSR['bagian_amil'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">Jumlah</td>
                <td class="total-amount">{{ number_format(($danaCSR['penerimaan'] ?? 0) - ($danaCSR['bagian_amil'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">2 Penyaluran Dana</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="total-row">Surplus (defisit)</td>
                <td class="amount">{{ ($danaCSR['surplus_defisit'] ?? 0) < 0 ? '('.number_format(abs($danaCSR['surplus_defisit'] ?? 0), 0, ',', '.').')' : number_format($danaCSR['surplus_defisit'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Awal</td>
                <td class="amount">{{ number_format($danaCSR['saldo_awal'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Akhir</td>
                <td class="saldo-akhir">{{ number_format($danaCSR['saldo_akhir'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="section-title">D DANA SOSIAL KEAGAMAAN LAINNYA</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">1 Penerimaan Dana</td>
                <td class="amount"></td>
            </tr>
            @php
                $danaDSKL = $reportData[4] ?? [];
                $rincianPenerimaanDSKL = $danaDSKL['rincian_penerimaan'] ?? [];
            @endphp
            @foreach($rincianPenerimaanDSKL as $jenis => $jumlah)
            <tr>
                <td class="indented">- {{ $jenis }}</td>
                <td class="amount">{{ number_format($jumlah, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @if(empty($rincianPenerimaanDSKL))
            <tr>
                <td class="indented">- Hibah</td>
                <td class="amount">{{ number_format(($danaDSKL['penerimaan'] ?? 0) * 0.6, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Qurban</td>
                <td class="amount">{{ number_format(($danaDSKL['penerimaan'] ?? 0) * 0.25, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Fidyah</td>
                <td class="amount">{{ number_format(($danaDSKL['penerimaan'] ?? 0) * 0.05, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Nazar</td>
                <td class="amount">{{ number_format(($danaDSKL['penerimaan'] ?? 0) * 0.02, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Bagi Hasil Investasi</td>
                <td class="amount">{{ number_format(($danaDSKL['penerimaan'] ?? 0) * 0.005, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- DSKL Lainnya</td>
                <td class="amount">{{ number_format(($danaDSKL['penerimaan'] ?? 0) * 0.075, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="subsection">Jumlah</td>
                <td class="total-amount">{{ number_format($danaDSKL['penerimaan'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">Bagian Amil</td>
                <td class="subtotal-amount">{{ number_format($danaDSKL['bagian_amil'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">Jumlah Penerimaan Setelah Bagian Amil</td>
                <td class="amount">{{ number_format(($danaDSKL['penerimaan'] ?? 0) - ($danaDSKL['bagian_amil'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">2 Penyaluran Dana</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">2.1 Penyaluran Dana berdasarkan Bidang Program</td>
                <td class="amount"></td>
            </tr>
            @php
                $rincianProgramDSKL = $danaDSKL['rincian_penyaluran'] ?? [];
            @endphp
            @foreach($rincianProgramDSKL as $program => $jumlah)
            <tr>
                <td class="indented">- {{ $program }}</td>
                <td class="amount">{{ $jumlah > 0 ? number_format($jumlah, 0, ',', '.') : '-' }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="subsection">Total Penyaluran</td>
                <td class="final-total">{{ number_format($danaDSKL['penyaluran'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Surplus (defisit)</td>
                <td class="amount">{{ ($danaDSKL['surplus_defisit'] ?? 0) < 0 ? '('.number_format(abs($danaDSKL['surplus_defisit'] ?? 0), 0, ',', '.').')' : number_format($danaDSKL['surplus_defisit'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Awal</td>
                <td class="amount">{{ number_format($danaDSKL['saldo_awal'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Akhir</td>
                <td class="saldo-akhir">{{ number_format($danaDSKL['saldo_akhir'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
        
        <table>
            <tr>
                <td class="section-title">E DANA NON HALAL</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">1 Penerimaan Dana</td>
                <td class="amount"></td>
            </tr>
            @php
                $danaNonHalal = $reportData[7] ?? [];
                $rincianPenerimaanNonHalal = $danaNonHalal['rincian_penerimaan'] ?? [];
            @endphp
            @foreach($rincianPenerimaanNonHalal as $jenis => $jumlah)
            <tr>
                <td class="indented">- {{ $jenis }}</td>
                <td class="amount">{{ number_format($jumlah, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @if(empty($rincianPenerimaanNonHalal))
            <tr>
                <td class="indented">- Dana Bunga Bank</td>
                <td class="amount">{{ number_format($danaNonHalal['penerimaan'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="subsection">Jumlah</td>
                <td class="total-amount">{{ number_format($danaNonHalal['penerimaan'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">2 Penyaluran Dana</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">Penyaluran Dana berdasarkan Bidang Program</td>
                <td class="amount"></td>
            </tr>
            @php
                $rincianProgramNonHalal = $danaNonHalal['rincian_penyaluran'] ?? [];
            @endphp
            @foreach($rincianProgramNonHalal as $program => $jumlah)
            <tr>
                <td class="indented">- {{ $program }}</td>
                <td class="amount">{{ $jumlah > 0 ? number_format($jumlah, 0, ',', '.') : '-' }}</td>
            </tr>
            @endforeach
            @if(empty($rincianProgramNonHalal) && ($danaNonHalal['penyaluran'] ?? 0) > 0)
            <tr>
                <td class="indented">- Kemanusiaan</td>
                <td class="amount">{{ number_format($danaNonHalal['penyaluran'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="subsection">Total Penyaluran</td>
                <td class="final-total">{{ number_format($danaNonHalal['penyaluran'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Surplus (defisit)</td>
                <td class="amount">{{ ($danaNonHalal['surplus_defisit'] ?? 0) < 0 ? '('.number_format(abs($danaNonHalal['surplus_defisit'] ?? 0), 0, ',', '.').')' : number_format($danaNonHalal['surplus_defisit'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Awal</td>
                <td class="amount">{{ number_format($danaNonHalal['saldo_awal'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Akhir</td>
                <td class="saldo-akhir">{{ number_format($danaNonHalal['saldo_akhir'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
        
        <table>
            <tr>
                <td class="section-title">F PENERIMAAN DAN PENGGUNAAN HAK AMIL (DALAM RUPIAH)</td>
                <td class="amount"></td>
            </tr>
            <tr>
                <td class="subsection">1. Penerimaan Hak Amil</td>
                <td class="amount"></td>
            </tr>
            @foreach($penerimaanHakAmilDetail as $jenis => $jumlah)
            <tr>
                <td class="indented">- Penerimaan hak amil dari {{ $jenis }} (maksimal.)</td>
                <td class="amount">{{ number_format($jumlah, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @if(empty($penerimaanHakAmilDetail))
            <tr>
                <td class="indented">- Penerimaan hak amil dari zakat asnaf amil (maksimal.)</td>
                <td class="amount">{{ number_format($totalPenerimaanHakAmil * 0.3, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Penerimaan hak amil dari Infaq</td>
                <td class="amount">{{ number_format($totalPenerimaanHakAmil * 0.65, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Penerimaan hak amil dari DSKL</td>
                <td class="amount">{{ number_format($totalPenerimaanHakAmil * 0.05, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="subsection">Total Penerimaan</td>
                <td class="total-amount">{{ number_format($totalPenerimaanHakAmil, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="subsection">2. Penggunaan Hak Amil</td>
                <td class="amount"></td>
            </tr>
            @foreach($penggunaanHakAmilDetail as $jenis => $jumlah)
            <tr>
                <td class="indented">- {{ $jenis }}</td>
                <td class="amount">{{ number_format($jumlah, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @if(empty($penggunaanHakAmilDetail))
            <tr>
                <td class="indented">- Belanja Pegawai</td>
                <td class="amount">{{ number_format($totalPenggunaanHakAmil * 0.85, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Biaya Perjalanan Dinas</td>
                <td class="amount">{{ number_format($totalPenggunaanHakAmil * 0.02, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Beban Administrasi Umum</td>
                <td class="amount">{{ number_format($totalPenggunaanHakAmil * 0.08, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Beban Penyusutan, Pemeliharaan dan Penghapusan</td>
                <td class="amount">{{ number_format($totalPenggunaanHakAmil * 0.025, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="indented">- Biaya Rumah Tangga / Protokoler Yayasan</td>
                <td class="amount">{{ number_format($totalPenggunaanHakAmil * 0.025, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="subsection">Total Penggunaan</td>
                <td class="total-amount">{{ number_format($totalPenggunaanHakAmil, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Surplus (defisit)</td>
                <td class="amount">{{ $surplusDefisitHakAmil < 0 ? '('.number_format(abs($surplusDefisitHakAmil), 0, ',', '.').')' : number_format($surplusDefisitHakAmil, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Awal</td>
                <td class="amount">{{ number_format(($totalPenerimaanHakAmil - $totalPenggunaanHakAmil - $surplusDefisitHakAmil) * 0.4, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-row">Saldo Akhir</td>
                <td class="saldo-akhir">{{ number_format(($totalPenerimaanHakAmil - $totalPenggunaanHakAmil) + (($totalPenerimaanHakAmil - $totalPenggunaanHakAmil - $surplusDefisitHakAmil) * 0.4), 0, ',', '.') }}</td>
            </tr>
        </table>
        
        <div class="total-row">
            Saldo Dana Zakat, Infaq/Sedekah, CSR, DSKL, Amil dan Non Halal: {{ number_format($totalSisaSaldo ?? 0, 0, ',', '.') }}
        </div>

        <div class="signature-section">
            <div class="signature">
                <p>Diketahui Oleh:</p>
                <p class="signature-name">FUJI LESTARI, S.E.</p>
                <p>Direktur Eksekutif</p>
            </div>
            <div class="signature">
                <p>Disusun Oleh:</p>
                <p class="signature-name">JOKO NURHADI</p>
                <p>Bendahara</p>
            </div>
        </div>
</body>
</html>

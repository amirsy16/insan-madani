{{-- Baris Judul Seksi
<tr>
    <th scope="row" class="px-2 py-2 font-bold text-lg" colspan="2">
        {{ $title }}
    </th>
</tr>
<tr>
    <th scope="row" class="px-2 py-2 font-bold">
        1. Penerimaan Dana
    </th>
    <td class="px-2 py-2 text-right"></td>
</tr>
@if(isset($data['rincian_penerimaan']) && count($data['rincian_penerimaan']) > 0)
    @foreach($data['rincian_penerimaan'] as $jenis => $jumlah)
        <tr>
            <td class="px-2 py-1 pl-8">- {{ $jenis }}</td>
            <td class="px-2 py-1 text-right">{{ number_format($jumlah, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    <tr>
        <td class="px-2 py-1 text-right border-t border-gray-300"></td>
        <td class="px-2 py-1 text-right border-t border-gray-300">{{ number_format($data['penerimaan'], 0, ',', '.') }}</td>
    </tr>
    @if(isset($data['bagian_amil']) && $data['bagian_amil'] > 0 && strtolower($title) !== 'hak amil')
        <tr>
            <td class="px-2 py-1 pl-8 text-gray-500">Bagian Amil</td>
            <td class="px-2 py-1 text-right text-gray-500">({{ number_format($data['bagian_amil'], 0, ',', '.') }})</td>
        </tr>
    @endif
    <tr>
        <td class="px-2 py-1 text-right"></td>
        <td class="px-2 py-1 text-right font-bold">{{ number_format(($data['penerimaan'] ?? 0) - ($data['bagian_amil'] ?? 0), 0, ',', '.') }}</td>
    </tr>
@else
    <tr>
        <td class="px-2 py-1 text-right"></td>
        <td class="px-2 py-1 text-right font-bold">{{ number_format($data['penerimaan'], 0, ',', '.') }}</td>
    </tr>
@endif
<tr>
    <th scope="row" class="px-2 py-2 font-bold">
        2. Penyaluran Dana
    </th>
    <td class="px-2 py-2 text-right"></td>
</tr>
@if(isset($data['rincian_penyaluran_asnaf']) && count($data['rincian_penyaluran_asnaf']) > 0)
    <tr>
        <td class="px-2 py-1 pl-8 font-semibold">2.1 Penyaluran Dana berdasarkan Asnaf</td>
        <td class="px-2 py-1 text-right"></td>
    </tr>
    @foreach($data['rincian_penyaluran_asnaf'] as $asnaf => $jumlah)
        <tr>
            <td class="px-2 py-1 pl-16">- {{ $asnaf }}</td>
            <td class="px-2 py-1 text-right">{{ $jumlah > 0 ? number_format($jumlah, 0, ',', '.') : '-' }}</td>
        </tr>
    @endforeach
    <tr>
        <td class="px-2 py-1 text-right border-t border-gray-300"></td>
        <td class="px-2 py-1 text-right border-t border-gray-300">{{ number_format($data['penyaluran'], 0, ',', '.') }}</td>
    </tr>
@endif
@if(isset($data['rincian_penyaluran']) && count($data['rincian_penyaluran']) > 0)
    <tr>
        <td class="px-2 py-1 pl-8 font-semibold">2.{{ isset($data['rincian_penyaluran_asnaf']) ? '2' : '1' }} Penyaluran Dana berdasarkan Bidang Program</td>
        <td class="px-2 py-1 text-right"></td>
    </tr>
    @foreach($data['rincian_penyaluran'] as $bidang => $jumlah)
        <tr>
            <td class="px-2 py-1 pl-16">- {{ $bidang }}</td>
            <td class="px-2 py-1 text-right">{{ $jumlah > 0 ? number_format($jumlah, 0, ',', '.') : '-' }}</td>
        </tr>
    @endforeach
    <tr>
        <td class="px-2 py-1 text-right border-t border-gray-300"></td>
        <td class="px-2 py-1 text-right border-t border-gray-300">{{ number_format($data['penyaluran'], 0, ',', '.') }}</td>
    </tr>
@endif
<tr>
    <td class="px-2 py-1 text-right"></td>
    <td class="px-2 py-1 text-right font-bold">{{ number_format($data['penyaluran'], 0, ',', '.') }}</td>
</tr>
<tr>
    <td class="px-2 py-2 font-semibold">Surplus (defisit)</td>
    <td class="px-2 py-2 text-right font-semibold">
        {{ $data['surplus_defisit'] < 0 ? '(' . number_format(abs($data['surplus_defisit']), 0, ',', '.') . ')' : number_format($data['surplus_defisit'], 0, ',', '.') }}
    </td>
</tr>
<tr>
    <td class="px-2 py-2 font-semibold">Saldo Awal</td>
    <td class="px-2 py-2 text-right font-semibold">
        {{ number_format($data['saldo_awal'], 0, ',', '.') }}
    </td>
</tr>
<tr>
    <td class="px-2 py-2 font-bold">Saldo Akhir</td>
    <td class="px-2 py-2 text-right font-bold bg-yellow-100">
        {{ number_format($data['saldo_akhir'], 0, ',', '.') }}
    </td>
</tr> --}}
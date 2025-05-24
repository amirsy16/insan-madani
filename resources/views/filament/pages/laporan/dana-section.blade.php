{{-- Baris Judul Seksi --}}
<tr class="bg-gray-50">
    <td class="level-1" colspan="2">A. {{ $title }}</td>
</tr>

{{-- 1. Penerimaan --}}
<tr>
    <td class="level-2">1. Penerimaan Dana</td>
    <td class="amount">{{ format_rp($data['penerimaan']) }}</td>
</tr>
@if (!empty($detail_penerimaan) && $detail_penerimaan)
    <tr>
        <td class="level-3">- Zakat Perorangan</td>
        <td class="amount">{{ format_rp($data['penerimaan_detail']['perorangan'] ?? 0) }}</td>
    </tr>
    <tr>
        <td class="level-3">- Zakat Badan</td>
        <td class="amount">{{ format_rp($data['penerimaan_detail']['badan'] ?? 0) }}</td>
    </tr>
    <tr>
        <td class="level-3">- Zakat Fitrah</td>
        <td class="amount">{{ format_rp($data['penerimaan_detail']['fitrah'] ?? 0) }}</td>
    </tr>
@endif

{{-- Bagian Amil --}}
<tr>
    <td class="level-2">Bagian Amil</td>
    <td class="amount">({{ format_rp($data['bagian_amil']) }})</td>
</tr>

{{-- Penyaluran --}}
<tr>
    <td class="level-2">2. Penyaluran Dana</td>
    <td class="amount">{{ format_rp($data['penyaluran_net']) }}</td>
</tr>
<tr>
    <td class="level-3">2.1 Penyaluran Dana berdasarkan Asnaf</td>
    <td class="amount"></td>
</tr>
@foreach ($asnafList as $asnaf)
    @if ($asnaf !== 'Amil')
    <tr>
        <td class="level-4">- {{ $asnaf }}</td>
        <td class="amount">{{ format_rp($data['penyaluran_by_asnaf'][$asnaf] ?? 0) }}</td>
    </tr>
    @endif
@endforeach

<tr>
    <td class="level-3">2.2 Penyaluran Dana berdasarkan Bidang Program</td>
    <td class="amount"></td>
</tr>
@foreach ($programList as $program)
    <tr>
        <td class="level-4">- {{ $program }}</td>
        <td class="amount">{{ format_rp($data['penyaluran_by_program'][$program] ?? 0) }}</td>
    </tr>
@endforeach

{{-- Surplus, Saldo Awal, Saldo Akhir --}}
<tr class="font-semibold">
    <td class="level-2">Surplus (defisit)</td>
    <td class="amount">{{ format_rp($data['surplus_defisit']) }}</td>
</tr>
<tr>
    <td class="level-2">Saldo Awal</td>
    <td class="amount">{{ format_rp($data['saldo_awal']) }}</td>
</tr>
<tr class="font-bold">
    <td class="level-2">Saldo Akhir</td>
    <td class="amount">{{ format_rp($data['saldo_akhir']) }}</td>
</tr>
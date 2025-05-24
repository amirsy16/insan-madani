<tr class="bg-gray-50">
    <td class="level-1" colspan="2">F. {{ $title }}</td>
</tr>

{{-- Penerimaan Hak Amil --}}
<tr>
    <td class="level-2">1. Penerimaan Hak Amil</td>
    <td class="amount">{{ format_rp($data['total_penerimaan']) }}</td>
</tr>
<tr>
    <td class="level-3">- Penerimaan hak amil dari zakat</td>
    <td class="amount">{{ format_rp($data['penerimaan_amil_zakat']) }}</td>
</tr>
<tr>
    <td class="level-3">- Penerimaan hak amil dari Infaq</td>
    <td class="amount">{{ format_rp($data['penerimaan_amil_infak']) }}</td>
</tr>
<tr>
    <td class="level-3">- Penerimaan hak amil dari dana CSR</td>
    <td class="amount">{{ format_rp($data['penerimaan_amil_csr']) }}</td>
</tr>

{{-- Penggunaan Hak Amil --}}
<tr>
    <td class="level-2">2. Penggunaan Hak Amil</td>
    <td class="amount">{{ format_rp($data['total_penggunaan']) }}</td>
</tr>
@foreach ($data['penggunaan_detail'] as $item => $value)
    <tr>
        <td class="level-3">- {{ $item }}</td>
        <td class="amount">{{ format_rp($value) }}</td>
    </tr>
@endforeach
<tr class="border-t">
    <td class="level-3 text-red-600" colspan="2">
        <strong>Catatan:</strong> Data Penggunaan Hak Amil belum dapat ditampilkan karena tidak ada sumber data di database.
    </td>
</tr>


{{-- Surplus & Saldo --}}
<tr class="font-semibold">
    <td class="level-2">Surplus (defisit) Hak Amil</td>
    <td class="amount">{{ format_rp($data['surplus_defisit']) }}</td>
</tr>
<tr>
    <td class="level-2">Saldo Awal Hak Amil</td>
    <td class="amount">{{ format_rp($data['saldo_awal']) }}</td>
</tr>
<tr class="font-bold">
    <td class="level-2">Saldo Akhir Hak Amil</td>
    <td class="amount">{{ format_rp($data['saldo_akhir']) }}</td>
</tr>
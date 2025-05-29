{{-- HAK AMIL SECTION: Menggunakan desain yang sama dengan laporan perubahan dana --}}
<div class="p-6 bg-white rounded-xl shadow dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <tbody>
                <tr>
                    <th colspan="2" class="px-2 py-2 font-bold text-lg">HAK AMIL</th>
                </tr>
                <tr>
                    <td class="px-2 py-2 font-bold">1. Penerimaan Hak Amil</td>
                    <td class="px-2 py-2 text-right font-bold">{{ number_format($totalPenerimaanHakAmil ?? 0, 0, ',', '.') }}</td>
                </tr>
                @if(!empty($penerimaanHakAmilDetail))
                    @foreach($penerimaanHakAmilDetail as $jenis => $jumlah)
                        <tr>
                            <td class="px-2 py-1 pl-8">- {{ $jenis }}</td>
                            <td class="px-2 py-1 text-right">{{ number_format($jumlah ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
                <tr>
                    <td class="px-2 py-2 font-bold">2. Penggunaan Hak Amil</td>
                    <td class="px-2 py-2 text-right font-bold">{{ number_format($totalPenggunaanHakAmil ?? 0, 0, ',', '.') }}</td>
                </tr>
                @if(!empty($penggunaanHakAmilDetail))
                    @foreach($penggunaanHakAmilDetail as $jenis => $jumlah)
                        <tr>
                            <td class="px-2 py-1 pl-8">- {{ $jenis }}</td>
                            <td class="px-2 py-1 text-right">{{ number_format($jumlah ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="px-2 py-1 text-red-600" colspan="2">
                            <strong>Catatan:</strong> Tidak ada data penggunaan hak amil untuk periode ini.
                        </td>
                    </tr>
                @endif
                <tr class="font-semibold">
                    <td class="px-2 py-2">Surplus (defisit) Hak Amil</td>
                    <td class="px-2 py-2 text-right">{{ number_format($surplusDefisitHakAmil ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
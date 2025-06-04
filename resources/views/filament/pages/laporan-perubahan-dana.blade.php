<x-filament-panels::page>
    <style>
        tr:nth-child(odd) {
            background-color: rgba(229, 231, 235, 0.9); /* Light gray with high opacity */
        }
        tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 1); /* Pure white */
        }
        tr:hover {
            background-color: rgba(219, 234, 254, 0.8); /* Light blue on hover */
        }
        .dark tr:nth-child(odd) {
            background-color: rgba(55, 65, 81, 0.3);
        }
        .dark tr:nth-child(even) {
            background-color: rgba(31, 41, 55, 0.3);
        }
        .dark tr:hover {
            background-color: rgba(55, 65, 81, 0.5);
        }
    </style>
    <div class="mb-6">
        <form wire:submit.prevent="generateReport" class="p-4 bg-white rounded-xl shadow dark:bg-gray-800">
            {{ $this->form }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Terapkan Filter
                </x-filament::button>
            </div>
        </form>
    </div>

    <div class="space-y-8">
        @php
            // Filter out summary data from main report loop
            $filteredReportData = collect($reportData)->filter(function($data, $key) {
                return $key !== 'summary' && is_array($data) && isset($data['title']);
            });
        @endphp
        
        @forelse ($filteredReportData as $fundKey => $data)
            <div class="p-6 bg-white rounded-xl shadow dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <tbody>
                            {{-- HEADER SUMBER DANA --}}
                            <tr>
                                <th scope="row" class="px-2 py-2 font-bold text-lg">
                                    {{ chr(64 + $loop->iteration) }}. {{ strtoupper($data['title']) }}
                                </th>
                                <td class="px-2 py-2 text-right"></td>
                            </tr>

                            {{-- PENERIMAAN DANA --}}
                            <tr>
                                <th scope="row" class="px-2 py-2 font-bold">
                                    1. Penerimaan Dana
                                </th>
                                <td class="px-2 py-2 text-right"></td>
                            </tr>

                            {{-- RINCIAN PENERIMAAN BERDASARKAN JENIS --}}
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
                                {{-- Tambahkan baris potongan hak amil 12% jika bukan hak amil --}}
                                @if(isset($data['bagian_amil']) && $data['bagian_amil'] > 0 && strtolower($data['title']) !== 'hak amil')
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

                            {{-- PENYALURAN DANA --}}
                            <tr>
                                <th scope="row" class="px-2 py-2 font-bold">
                                    2. Penyaluran Dana
                                </th>
                                <td class="px-2 py-2 text-right"></td>
                            </tr>

                            {{-- PENYALURAN BERDASARKAN ASNAF --}}
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

                            {{-- PENYALURAN BERDASARKAN BIDANG PROGRAM --}}
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

                            {{-- SURPLUS/DEFISIT --}}
                            <tr>
                                <td class="px-2 py-2 font-semibold">Surplus (defisit)</td>
                                <td class="px-2 py-2 text-right font-semibold">
                                    {{ $data['surplus_defisit'] < 0 ? '(' . number_format(abs($data['surplus_defisit']), 0, ',', '.') . ')' : number_format($data['surplus_defisit'], 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- SALDO AWAL --}}
                            <tr>
                                <td class="px-2 py-2 font-semibold">Saldo Awal</td>
                                <td class="px-2 py-2 text-right font-semibold">
                                    {{ number_format($data['saldo_awal'], 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- SALDO AKHIR --}}
                            <tr>
                                <td class="px-2 py-2 font-bold">Saldo Akhir</td>
                                <td class="px-2 py-2 text-right font-bold bg-yellow-100">
                                    {{ number_format($data['saldo_akhir'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="p-6 bg-white rounded-xl shadow dark:bg-gray-800 text-center text-gray-500">
                Tidak ada data untuk ditampilkan. Silakan sesuaikan filter Anda.
            </div>
        @endforelse

        {{-- HAK AMIL SECTION DI BAWAH TABEL DONASI --}}
        @include('filament.pages.laporan.hak-amil-section', [
            'penerimaanHakAmilDetail' => $penerimaanHakAmilDetail,
            'totalPenerimaanHakAmil' => $totalPenerimaanHakAmil,
            'penggunaanHakAmilDetail' => $penggunaanHakAmilDetail,
            'totalPenggunaanHakAmil' => $totalPenggunaanHakAmil,
            'surplusDefisitHakAmil' => $surplusDefisitHakAmil,
        ])

        {{-- SUMMARY TOTAL SALDO SECTION --}}
        @if (!empty($summaryData))
        <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-100 rounded-xl shadow-lg border-2 border-blue-200 dark:from-gray-800 dark:to-gray-700 dark:border-gray-600">
            <h3 class="text-xl font-bold text-blue-900 dark:text-blue-100 mb-4 text-center">
                📊 RINGKASAN TOTAL LAPORAN KEUANGAN
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody>
                        <tr class="border-b border-blue-200 dark:border-gray-600">
                            <td class="px-4 py-3 font-semibold text-blue-800 dark:text-blue-200">Total Saldo Awal</td>
                            <td class="px-4 py-3 text-right font-bold text-green-700 dark:text-green-400">
                                Rp {{ number_format($summaryData['total_saldo_awal'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="border-b border-blue-200 dark:border-gray-600">
                            <td class="px-4 py-3 font-semibold text-blue-800 dark:text-blue-200">Total Penerimaan</td>
                            <td class="px-4 py-3 text-right font-bold text-green-700 dark:text-green-400">
                                Rp {{ number_format($summaryData['total_penerimaan'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="border-b border-blue-200 dark:border-gray-600">
                            <td class="px-4 py-3 font-semibold text-blue-800 dark:text-blue-200">Total Bagian Amil</td>
                            <td class="px-4 py-3 text-right font-bold text-orange-600 dark:text-orange-400">
                                Rp {{ number_format($summaryData['total_bagian_amil'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="border-b border-blue-200 dark:border-gray-600">
                            <td class="px-4 py-3 font-semibold text-blue-800 dark:text-blue-200">Total Penyaluran</td>
                            <td class="px-4 py-3 text-right font-bold text-red-600 dark:text-red-400">
                                Rp {{ number_format($summaryData['total_penyaluran'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="border-b-2 border-blue-300 dark:border-gray-500">
                            <td class="px-4 py-3 font-semibold text-blue-800 dark:text-blue-200">Total Surplus/Defisit</td>
                            <td class="px-4 py-3 text-right font-bold 
                                {{ ($summaryData['total_surplus_defisit'] ?? 0) >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                Rp {{ number_format($summaryData['total_surplus_defisit'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-blue-100 dark:bg-gray-600">
                            <td class="px-4 py-4 font-bold text-lg text-blue-900 dark:text-blue-100">
                                💰 TOTAL SISA SALDO AKHIR
                            </td>
                            <td class="px-4 py-4 text-right font-bold text-2xl 
                                {{ ($summaryData['total_saldo_akhir'] ?? 0) >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                Rp {{ number_format($summaryData['total_saldo_akhir'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            {{-- Indikator Status Keuangan --}}
            @if (!empty($financialStatus))
            <div class="mt-4 p-3 rounded-lg text-center border {{ $financialStatus['color_class'] }}">
                <span class="font-semibold">
                    {{ $financialStatus['icon'] }} Status Keuangan: {{ $financialStatus['status'] }} - {{ $financialStatus['message'] }}
                </span>
            </div>
            @endif
        </div>
        @endif
    </div>
</x-filament-panels::page>
<x-filament-panels::page>
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

    @php
    // Pastikan semua kunci yang dibutuhkan tersedia dalam data
    foreach ($reportData as $key => $data) {
        // Tambahkan kunci yang hilang dengan nilai default
        if (!isset($data['penerimaan_sebelum'])) {
            $reportData[$key]['penerimaan_sebelum'] = 0;
        }
        if (!isset($data['penyaluran_sebelum'])) {
            $reportData[$key]['penyaluran_sebelum'] = 0;
        }
        if (!isset($data['debug'])) {
            $reportData[$key]['debug'] = [];
        }
        if (!isset($data['debug']['penerimaan_sebelum'])) {
            $reportData[$key]['debug']['penerimaan_sebelum'] = $reportData[$key]['penerimaan_sebelum'];
        }
        if (!isset($data['debug']['penyaluran_sebelum'])) {
            $reportData[$key]['debug']['penyaluran_sebelum'] = $reportData[$key]['penyaluran_sebelum'];
        }
    }
    @endphp

    <div class="space-y-8">
        {{-- Summary Section - Show combined totals first --}}
        @if(isset($reportData['total']))
            <div class="p-6 bg-primary-50 rounded-xl shadow dark:bg-primary-900/50 border-2 border-primary-200 dark:border-primary-800">
                <h3 class="text-2xl font-bold mb-4 text-primary-800 dark:text-primary-200 uppercase">{{ $reportData['total']['title'] }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Saldo Awal</p>
                        <p class="text-xl font-bold">Rp {{ number_format($reportData['total']['saldo_awal'], 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Penerimaan</p>
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">Rp {{ number_format($reportData['total']['penerimaan'], 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Penyaluran</p>
                        <p class="text-xl font-bold text-red-600 dark:text-red-400">Rp {{ number_format($reportData['total']['penyaluran'], 0, ',', '.') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Surplus/Defisit</p>
                        <p @class([
                            'text-xl font-bold',
                            'text-green-600 dark:text-green-400' => $reportData['total']['surplus_defisit'] >= 0,
                            'text-red-600 dark:text-red-400' => $reportData['total']['surplus_defisit'] < 0,
                        ])>
                            {{ $reportData['total']['surplus_defisit'] < 0 ? '(Rp ' . number_format(abs($reportData['total']['surplus_defisit']), 0, ',', '.') . ')' : 'Rp ' . number_format($reportData['total']['surplus_defisit'], 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Saldo Akhir</p>
                        <p class="text-xl font-bold">Rp {{ number_format($reportData['total']['saldo_akhir'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Individual Fund Reports --}}
        @forelse ($reportData as $fundKey => $data)
            @if($fundKey !== 'total')
                <div class="p-6 bg-white rounded-xl shadow dark:bg-gray-800">
                    <h3 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200 uppercase border-b pb-2">{{ $data['title'] }}</h3>

                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <tbody>
                            {{-- SALDO AWAL --}}
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th scope="row" class="px-6 py-3 font-bold text-gray-900 dark:text-white">SALDO AWAL</th>
                                <td class="px-6 py-3 text-right font-bold text-gray-900 dark:text-white">Rp {{ number_format($data['saldo_awal'], 0, ',', '.') }}</td>
                            </tr>

                            @if(isset($data['debug']))
                                <tr class="text-xs text-gray-400">
                                    <td class="pl-12 pr-6 py-1 italic">Debug: Penerimaan Sebelum</td>
                                    <td class="px-6 py-1 text-right italic">Rp {{ number_format($data['debug']['penerimaan_sebelum'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="text-xs text-gray-400 border-b dark:border-gray-700">
                                    <td class="pl-12 pr-6 py-1 italic">Debug: Penyaluran Sebelum</td>
                                    <td class="px-6 py-1 text-right italic">(Rp {{ number_format($data['debug']['penyaluran_sebelum'] ?? 0, 0, ',', '.') }})</td>
                                </tr>
                            @endif

                            {{-- PENERIMAAN --}}
                            <tr class="border-b dark:border-gray-700">
                                <th scope="row" class="px-6 py-3 font-medium text-gray-900 dark:text-white">Penerimaan Dana</th>
                                <td class="px-6 py-3 text-right">Rp {{ number_format($data['penerimaan'], 0, ',', '.') }}</td>
                            </tr>

                            {{-- PENYALURAN --}}
                            <tr class="border-b dark:border-gray-700 bg-red-50 dark:bg-red-900/10">
                                <th scope="row" class="px-6 py-3 font-medium text-gray-900 dark:text-white">Penyaluran Dana</th>
                                <td class="px-6 py-3 text-right text-red-600 dark:text-red-500">(Rp {{ number_format($data['penyaluran'], 0, ',', '.') }})</td>
                            </tr>

                            {{-- RINCIAN PENYALURAN --}}
                            @if (!empty($data['rincian_penyaluran']))
                                @foreach ($data['rincian_penyaluran'] as $kategori => $total)
                                    <tr class="border-b dark:border-gray-700 {{ $kategori === 'Amil' && $data['is_zakat'] ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                                        <td class="pl-12 pr-6 py-2 flex justify-between">
                                            <span>{{ $kategori }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ number_format($data['persentase_penyaluran'][$kategori] ?? 0, 1) }}%
                                            </span>
                                        </td>
                                        <td class="px-6 py-2 text-right">(Rp {{ number_format($total, 0, ',', '.') }})</td>
                                    </tr>
                                @endforeach
                            @endif
                            
                            {{-- SURPLUS/DEFISIT --}}
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th scope="row" class="px-6 py-3 font-bold text-gray-900 dark:text-white">SURPLUS (DEFISIT)</th>
                                <td @class([
                                    'px-6 py-3 text-right font-bold',
                                    'text-green-600 dark:text-green-500' => $data['surplus_defisit'] >= 0,
                                    'text-red-600 dark:text-red-500' => $data['surplus_defisit'] < 0,
                                ])>
                                    {{ $data['surplus_defisit'] < 0 ? '(Rp ' . number_format(abs($data['surplus_defisit']), 0, ',', '.') . ')' : 'Rp ' . number_format($data['surplus_defisit'], 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- SALDO AKHIR --}}
                            <tr class="bg-gray-100 dark:bg-gray-900/50">
                                <th scope="row" class="px-6 py-4 font-extrabold text-lg text-gray-900 dark:text-white">SALDO AKHIR</th>
                                <td class="px-6 py-4 text-right font-extrabold text-lg text-gray-900 dark:text-white">Rp {{ number_format($data['saldo_akhir'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Special notes for Zakat --}}
                    @if($data['is_zakat'])
                        <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-sm">
                            <p class="font-medium text-amber-800 dark:text-amber-300">Catatan Zakat:</p>
                            <p class="text-amber-700 dark:text-amber-400">Bagian Amil (12.5%): Rp {{ number_format($data['bagian_amil'], 0, ',', '.') }}</p>
                        </div>
                    @endif
                </div>
            @endif
        @empty
            <div class="p-6 bg-white rounded-xl shadow dark:bg-gray-800 text-center text-gray-500">
                Tidak ada data untuk ditampilkan. Silakan sesuaikan filter Anda.
            </div>
        @endforelse
    </div>

</x-filament-panels::page>





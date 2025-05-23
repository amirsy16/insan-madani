<x-filament-panels::page>
    {{-- Section untuk Filter --}}
    <div class="mb-6">
        {{-- Filament will automatically render header actions defined in the Page class --}}
        {{-- So, the manual rendering below has been removed:
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-lg font-medium text-gray-700 dark:text-gray-200">Filter Laporan</h2>
            <div>
                {{-- $this->refreshDataAction --}} {{-- REMOVED --}}
                {{-- $this->getHeaderActions()[1] ?? '' --}} {{-- REMOVED --}}
            {{-- </div>
        </div>
        --}}
        <form wire:submit.prevent="submitFilters" class="p-4 bg-white rounded-xl shadow dark:bg-gray-800">
            {{ $this->form }}
        </form>
    </div>

    {{-- Section untuk Ringkasan Total & Komparasi --}}
    <div class="mb-6 p-4 bg-white rounded-xl shadow dark:bg-gray-800">
        <h3 class="text-xl font-semibold leading-6 text-gray-900 dark:text-white mb-2">Ringkasan Pemasukan</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Periode: {{ $this->startDate ? \Carbon\Carbon::parse($this->startDate)->translatedFormat('d M Y') : 'N/A' }} - {{ $this->endDate ? \Carbon\Carbon::parse($this->endDate)->translatedFormat('d M Y') : 'N/A' }}
            @if($this->previousPeriodLabel)
                <br/>Perbandingan dengan periode sebelumnya ({{ $this->previousPeriodLabel }})
            @else
                <br/>Perbandingan dengan periode sebelumnya: N/A
            @endif
        </p>

        @php
        $metrics = [
            [
                'title' => 'Total Pemasukan (Uang)',
                'currentValue' => $this->totalPemasukan,
                'previousValue' => $this->totalPemasukanPrev,
                'change' => $this->pemasukanChange,
                'formatter' => 'number_format_rp'
            ],
            [
                'title' => 'Total Nilai Barang',
                'currentValue' => $this->totalNilaiBarang,
                'previousValue' => $this->totalNilaiBarangPrev,
                'change' => $this->nilaiBarangChange,
                'formatter' => 'number_format_rp'
            ],
            [
                'title' => 'Grand Total Pemasukan',
                'currentValue' => $this->grandTotalPemasukan,
                'previousValue' => $this->grandTotalPemasukanPrev,
                'change' => $this->grandTotalChange,
                'formatter' => 'number_format_rp'
            ],
            [
                'title' => 'Total Transaksi',
                'currentValue' => $this->totalTransaksi,
                'previousValue' => $this->totalTransaksiPrev,
                'change' => $this->transaksiChange,
                'formatter' => 'number_format'
            ],
        ];
        @endphp

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                @php
                    $change = $metric['change'];
                    $currentValue = $metric['currentValue'];
                    $previousValue = $metric['previousValue'];
                    $formatter = $metric['formatter'];

                    $icon = null;
                    if (!is_null($change)) {
                        $icon = $change > 0 ? 'heroicon-s-arrow-trending-up' : ($change < 0 ? 'heroicon-s-arrow-trending-down' : 'heroicon-s-minus');
                    }
                    $colorClass = 'text-gray-600 dark:text-gray-400';
                    if (!is_null($change)) {
                        $colorClass = $change > 0 ? 'text-green-600 dark:text-green-400' : ($change < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400');
                    }
                    
                    $bgColorClass = 'bg-gray-50 dark:bg-gray-700/50';
                     if (!is_null($change)) {
                        $bgColorClass = $change > 0 ? 'bg-green-50 dark:bg-green-800/50' : ($change < 0 ? 'bg-red-50 dark:bg-red-800/50' : 'bg-gray-50 dark:bg-gray-700/50');
                    }

                    if ($formatter === 'number_format_rp') {
                        $displayValue = 'Rp ' . number_format($currentValue, 0, ',', '.');
                        $displayPreviousValue = 'Rp ' . number_format($previousValue, 0, ',', '.');
                    } elseif ($formatter === 'number_format') {
                        $displayValue = number_format($currentValue, 0, ',', '.');
                        $displayPreviousValue = number_format($previousValue, 0, ',', '.');
                    } else {
                        $displayValue = $currentValue; 
                        $displayPreviousValue = $previousValue;
                    }
                @endphp
                <div @class(['overflow-hidden rounded-lg px-4 py-5 shadow sm:p-6', $bgColorClass])>
                    <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ $metric['title'] }}</dt>
                    <dd class="mt-1 flex items-baseline justify-between">
                        <span class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                            {{ $displayValue }}
                        </span>
                        @if (!is_null($icon))
                            <span @class(['ml-2 flex items-baseline text-sm font-semibold', $colorClass])>
                                <x-filament::icon :icon="$icon" class="h-5 w-5 self-center"/>
                                {{ number_format(abs($change ?? 0), 1) }}%
                                <span class="sr-only"> {{ $change > 0 ? 'meningkat' : ($change < 0 ? 'menurun' : '') }} </span>
                            </span>
                        @endif
                    </dd>
                    @if (!is_null($change))
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Sebelumnya: {{ $displayPreviousValue }}
                    </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    
    {{-- Section untuk Tabel Data --}}
    <div class="p-4 bg-white rounded-xl shadow dark:bg-gray-800">
        <h3 class="text-xl font-semibold leading-6 text-gray-900 dark:text-white mb-4">Detail Transaksi Donasi</h3>
        {{ $this->table }}
    </div>

    {{-- Section untuk Ringkasan per Jenis Donasi (jika aktif) --}}
    @if($this->groupByJenisDonasi && !empty($this->summaryByJenisDonasi))
        <div class="mt-6 p-4 bg-white rounded-xl shadow dark:bg-gray-800">
            <h3 class="text-xl font-semibold leading-6 text-gray-900 dark:text-white mb-4">Ringkasan per Jenis Donasi</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-750">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jenis Donasi</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Uang (Rp)</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Nilai Barang (Rp)</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Grand Total (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->summaryByJenisDonasi as $summary)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $summary['jenis_donasi_name'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 text-right">{{ number_format($summary['total_jumlah'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 text-right">{{ number_format($summary['total_nilai_barang'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-semibold text-right">{{ number_format($summary['total'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-750">
                        <tr>
                            <td class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Keseluruhan</td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-white">
                                Rp {{ number_format(array_sum(array_column($this->summaryByJenisDonasi, 'total_jumlah')), 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-white">
                                Rp {{ number_format(array_sum(array_column($this->summaryByJenisDonasi, 'total_nilai_barang')), 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-white">
                                Rp {{ number_format(array_sum(array_column($this->summaryByJenisDonasi, 'total')), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

@push('scripts')
<script>
    // No scripts needed for chart as it was removed.
</script>
@endpush

</x-filament-panels::page>
<x-filament-panels::page>
     <div class="mb-6">
        @php
            $periode = request('periode', 'all_time');
            $periodeOptions = [
                'current_month' => 'Bulan Ini',
                'current_year' => 'Tahun Ini',
                'all_time' => 'Keseluruhan',
            ];
        @endphp
        <div class="flex gap-2">
            @foreach ($periodeOptions as $key => $label)
                <a href="?periode={{ $key }}"
                   class="px-4 py-2 rounded-lg text-sm font-semibold border transition
                        {{ $periode === $key ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
<div>
    @livewire(\App\Filament\Widgets\RingkasanStatistikUtama::class)
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        @livewire(\App\Filament\Widgets\KomposisiJenisDonasiChart::class)
    </div>
    <div>
        @livewire(\App\Filament\Widgets\TrenPenerimaanDonasiChart::class)
    </div>
</div>
</x-filament-panels::page>
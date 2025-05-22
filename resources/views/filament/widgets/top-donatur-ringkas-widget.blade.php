<x-filament-widgets::widget class="fi-wi-stats-overview">
    <div class="fi-wi-stats-overview-stats-container">
        <div class="fi-fo-component-ctn bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <!-- Header dengan tabs periode -->
            <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex overflow-x-auto">
                    @foreach ($this->timePeriodOptions as $value => $label)
                        <button 
                            wire:click="setTimePeriod('{{ $value }}')"
                            class="px-4 py-3 text-sm font-medium whitespace-nowrap {{ $timePeriod === $value ? 'text-primary-600 border-b-2 border-primary-500 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Title and filter -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white">
                    Top {{ $this->limit }} Donatur
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        ({{ $this->getTimePeriodLabel() }})
                    </span>
                </h2>
                
                <div class="flex items-center space-x-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Total: Rp {{ number_format($this->totalDonasi, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Content -->
            <div class="p-4">
                @if ($this->topDonaturs && $this->topDonaturs->count() > 0)
                    <!-- Top 3 donatur dengan tampilan spesial -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        @foreach ($this->topDonaturs->take(3) as $index => $donatur)
                            @php
                                $colors = [
                                    0 => [
                                        'bg' => 'bg-amber-50 dark:bg-amber-900/20',
                                        'border' => 'border-amber-200 dark:border-amber-800',
                                        'text' => 'text-amber-800 dark:text-amber-200',
                                        'badge' => 'bg-amber-500 text-white',
                                        'icon' => 'text-amber-500'
                                    ],
                                    1 => [
                                        'bg' => 'bg-gray-50 dark:bg-gray-700/40',
                                        'border' => 'border-gray-200 dark:border-gray-600',
                                        'text' => 'text-gray-800 dark:text-gray-200',
                                        'badge' => 'bg-gray-500 text-white',
                                        'icon' => 'text-gray-500'
                                    ],
                                    2 => [
                                        'bg' => 'bg-orange-50 dark:bg-orange-900/20',
                                        'border' => 'border-orange-200 dark:border-orange-800',
                                        'text' => 'text-orange-800 dark:text-orange-200',
                                        'badge' => 'bg-orange-500 text-white',
                                        'icon' => 'text-orange-500'
                                    ]
                                ];
                                $color = $colors[$index];
                            @endphp
                            
                            <div class="relative rounded-lg border {{ $color['border'] }} {{ $color['bg'] }} p-4 flex flex-col items-center">
                                <!-- Badge peringkat -->
                                <div class="absolute -top-3 -left-3">
                                    <div class="w-8 h-8 flex items-center justify-center rounded-full shadow {{ $color['badge'] }}">
                                        <span class="text-sm font-bold">{{ $index + 1 }}</span>
                                    </div>
                                </div>
                                
                                <!-- Avatar/Icon -->
                                <div class="w-16 h-16 rounded-full flex items-center justify-center {{ $color['bg'] }} border-2 {{ $color['border'] }} mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 {{ $color['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                
                                <!-- Nama donatur -->
                                <a 
                                    href="{{ $this->getDonaturUrl($donatur->id) }}" 
                                    class="text-base font-medium text-primary-600 hover:underline dark:text-primary-400 text-center truncate max-w-full"
                                >
                                    {{ $donatur->nama }}
                                </a>
                                
                                <!-- Jumlah donasi -->
                                <p class="mt-2 text-lg font-bold {{ $color['text'] }}">
                                    Rp {{ number_format($donatur->total_donasi_sum, 0, ',', '.') }}
                                </p>
                                
                                <!-- Info tambahan -->
                                <div class="mt-1 flex items-center justify-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $donatur->total_transaksi }} transaksi</span>
                                    <span>•</span>
                                    <span>{{ $this->getPercentageText($donatur->total_donasi_sum) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Donatur lainnya dalam bentuk list -->
                    @if ($this->topDonaturs->count() > 3)
                        <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Donatur Lainnya</h3>
                            <div class="space-y-2">
                                @foreach ($this->topDonaturs as $index => $donatur)
                                    @if ($index >= 3)
                                        <div class="flex items-center justify-between py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded-lg transition-colors">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 w-5 text-center">
                                                    {{ $index + 1 }}
                                                </span>
                                                <a 
                                                    href="{{ $this->getDonaturUrl($donatur->id) }}" 
                                                    class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400 truncate max-w-[150px]"
                                                >
                                                    {{ $donatur->nama }}
                                                </a>
                                            </div>
                                            <div class="flex flex-col items-end">
                                                <p class="text-sm text-primary-600 dark:text-primary-400 font-semibold">
                                                    Rp {{ number_format($donatur->total_donasi_sum, 0, ',', '.') }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $donatur->total_transaksi }} transaksi
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="rounded-full bg-gray-100 p-3 dark:bg-gray-700">
                            <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Belum ada data donatur untuk periode {{ $this->getTimePeriodLabel() }}.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>



<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Header with filter buttons and toggle --}}
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full mb-4">
                <div class="flex items-center gap-2">
                    <span>📊 Ringkasan Statistik Utama</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">({{ $this->timePeriodLabel }})</span>
                </div>
                
                <div class="flex items-center gap-3">
                    {{-- Filter Buttons --}}
                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                        @foreach($this->timePeriodOptions as $value => $label)
                            <button
                                wire:click="setTimePeriod('{{ $value }}')"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200
                                       {{ $this->currentPeriod === $value 
                                          ? 'bg-primary-600 text-white shadow-sm' 
                                          : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Toggle Button --}}
                    <x-filament::button
                        wire:click="toggleShowAll"
                        size="sm"
                        color="gray"
                        :icon="$this->showAll ? 'heroicon-m-eye-slash' : 'heroicon-m-eye'"
                    >
                        {{ $this->showAll ? 'Ringkas' : 'Lengkap' }}
                    </x-filament::button>
                </div>
            </div>
        </x-slot>

        {{-- Stats Grid with loading state --}}
        <div class="relative">
            {{-- Loading overlay --}}
            <div wire:loading.flex wire:target="setTimePeriod" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 items-center justify-center rounded-lg z-10">
                <div class="flex items-center space-x-2 text-primary-600">
                    <x-filament::loading-indicator class="w-5 h-5" />
                    <span class="text-sm font-medium">Memuat data...</span>
                </div>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" wire:loading.class="opacity-50" wire:target="setTimePeriod">
                @forelse($this->stats ?? [] as $index => $stat)
                    <div 
                        class="stat-item {{ $index >= 8 && !$this->showAll ? 'hidden' : '' }}"
                        wire:key="stat-{{ $index }}"
                    >
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        @if($stat['icon'] ?? null)
                                            <span class="text-sm">{{ $stat['icon'] }}</span>
                                        @endif
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $stat['label'] ?? 'Unknown' }}
                                        </h3>
                                    </div>
                                    
                                    <div class="text-2xl font-bold text-{{ $stat['color'] ?? 'gray' }}-600 dark:text-{{ $stat['color'] ?? 'gray' }}-400 mb-1">
                                        {{ $stat['value'] ?? 'Rp 0' }}
                                    </div>
                                    
                                    @if($stat['description'] ?? null)
                                        <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ $stat['description'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- Empty state --}}
                    <div class="col-span-full">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
                            <div class="text-yellow-600 dark:text-yellow-400 text-2xl mb-2">⚠️</div>
                            <h3 class="text-yellow-800 dark:text-yellow-200 font-semibold mb-1">Tidak Ada Data</h3>
                            <p class="text-yellow-700 dark:text-yellow-300 text-sm">
                                Belum ada data statistik yang tersedia untuk periode ini.
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Stats count info --}}
        @if($this->stats && count($this->stats) > 0)
            <div class="text-center mt-4 text-sm text-gray-500 dark:text-gray-400">
                @if(!$this->showAll && count($this->stats) > 8)
                    Menampilkan 8 dari {{ count($this->stats) }} statistik untuk periode {{ strtolower($this->timePeriodLabel) }}
                @else
                    Menampilkan {{ count($this->stats) }} statistik untuk periode {{ strtolower($this->timePeriodLabel) }}
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
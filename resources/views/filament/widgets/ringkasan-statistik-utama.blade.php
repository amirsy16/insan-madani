<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Header with filter buttons and toggle --}}
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full mb-4">
                <div class="flex items-center gap-2">
                    <span>📊 Ringkasan Statistik Utama</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">({{ $timePeriodLabel }})</span>
                </div>
                
                <div class="flex items-center gap-3">
                    {{-- Filter Buttons --}}
                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                        @foreach($timePeriodOptions as $value => $label)
                            <button
                                wire:click="setTimePeriod('{{ $value }}')"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200
                                       {{ $currentPeriod === $value 
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
                        :icon="$showAll ? 'heroicon-m-eye-slash' : 'heroicon-m-eye'"
                    >
                        {{ $showAll ? 'Ringkas' : 'Lengkap' }}
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
                @foreach($stats as $index => $stat)
                    <div 
                        class="stat-item {{ $index >= 8 && !$showAll ? 'hidden' : '' }}"
                        wire:key="stat-{{ $index }}"
                    >
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        @if($stat->getIcon())
                                            <x-filament::icon 
                                                :icon="$stat->getIcon()" 
                                                class="w-4 h-4 text-gray-500 dark:text-gray-400"
                                            />
                                        @endif
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $stat->getLabel() }}
                                        </h3>
                                    </div>
                                    
                                    <div class="text-2xl font-bold text-{{ $stat->getColor() ?? 'gray' }}-600 dark:text-{{ $stat->getColor() ?? 'gray' }}-400 mb-1">
                                        {{ $stat->getValue() }}
                                    </div>
                                    
                                    @if($stat->getDescription())
                                        <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                            @if($stat->getDescriptionIcon())
                                                <x-filament::icon 
                                                    :icon="$stat->getDescriptionIcon()" 
                                                    class="w-3 h-3"
                                                />
                                            @endif
                                            <span>{{ $stat->getDescription() }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Show indicator when collapsed --}}
        @if(!$showAll)
            <div class="text-center mt-4 text-sm text-gray-500 dark:text-gray-400">
                Menampilkan 8 dari {{ count($stats) }} statistik
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

<x-filament-widgets::widget>
    <div class="fi-wi-stats-overview-card relative overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/20 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Top 5 Donatur
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::now()->format('F Y') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Content -->
        @if ($topDonaturs && $topDonaturs->count() > 0)
            <div class="space-y-3">
                @foreach ($topDonaturs as $index => $donatur)
                    <div class="flex items-center justify-between py-2">
                        <!-- Left side: rank and name -->
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <!-- Rank badge -->
                            <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                                {{ $index === 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' : 
                                   ($index === 1 ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                                   ($index === 2 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400' : 
                                   'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400')) }}">
                                {{ $index + 1 }}
                            </div>
                            
                            <!-- Donatur name -->
                            <a href="{{ $this->getDonaturUrl($donatur->id) }}" 
                               class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 truncate transition-colors"
                               title="{{ $donatur->nama }}">
                                {{ $donatur->nama }}
                            </a>
                        </div>

                        <!-- Right side: donation amount -->
                        <div class="text-right flex-shrink-0">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($donatur->total_donasi, 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $donatur->jumlah_transaksi }}x donasi
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Belum ada data donatur
                </p>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>



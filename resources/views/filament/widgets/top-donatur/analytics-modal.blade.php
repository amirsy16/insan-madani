<div class="space-y-6">
    <!-- Ringkasan Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Donatur Aktif</div>
            <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {{ number_format($totalDonatur) }}
            </div>
            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                Menampilkan top {{ $topCount }} donatur
            </div>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">Total Kontribusi</div>
            <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                Rp {{ number_format($totalKontribusi, 0, ',', '.') }}
            </div>
            <div class="text-xs text-green-600 dark:text-green-400 mt-1">
                Dari semua donatur
            </div>
        </div>
        
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
            <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Rata-rata per Donatur</div>
            <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                Rp {{ number_format($totalDonatur > 0 ? $totalKontribusi / $totalDonatur : 0, 0, ',', '.') }}
            </div>
            <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                Per donatur aktif
            </div>
        </div>
    </div>

    <!-- Kategori Donatur -->
    @php
        // Simulasi data kategori - dalam implementasi nyata, ini bisa dihitung dari database
        $kategoriData = [
            'Platinum' => ['count' => 5, 'color' => 'yellow', 'min' => 50000000],
            'Gold' => ['count' => 15, 'color' => 'green', 'min' => 10000000],
            'Silver' => ['count' => 35, 'color' => 'blue', 'min' => 5000000],
            'Bronze' => ['count' => 80, 'color' => 'orange', 'min' => 1000000],
            'Regular' => ['count' => 200, 'color' => 'gray', 'min' => 0],
        ];
    @endphp
    
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribusi Kategori Donatur</h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            @foreach($kategoriData as $kategori => $data)
                <div class="bg-{{ $data['color'] }}-50 dark:bg-{{ $data['color'] }}-900/20 rounded-lg p-3 text-center">
                    <div class="text-sm font-medium text-{{ $data['color'] }}-600 dark:text-{{ $data['color'] }}-400">
                        {{ $kategori }}
                    </div>
                    <div class="text-xl font-bold text-{{ $data['color'] }}-900 dark:text-{{ $data['color'] }}-100">
                        {{ $data['count'] }}
                    </div>
                    <div class="text-xs text-{{ $data['color'] }}-600 dark:text-{{ $data['color'] }}-400 mt-1">
                        @if($data['min'] > 0)
                            ≥ Rp {{ number_format($data['min'], 0, ',', '.') }}
                        @else
                            < Rp 1.000.000
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Insights & Rekomendasi -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Insights & Rekomendasi</h3>
        <div class="space-y-3">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Fokus pada Retensi:</span> 
                        Donatur Gold dan Platinum memiliki potensi kontribusi tinggi. Pertahankan engagement dengan program khusus.
                    </p>
                </div>
            </div>
            
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Upgrade Donatur:</span> 
                        Targetkan donatur Silver dan Bronze untuk naik kategori dengan program insentif yang menarik.
                    </p>
                </div>
            </div>
            
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Donatur Tidak Aktif:</span> 
                        Identifikasi donatur yang tidak aktif lebih dari 6 bulan untuk program reaktivasi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tips Penggunaan Filter -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">Tips Penggunaan Filter</h3>
        <div class="space-y-2 text-sm text-blue-700 dark:text-blue-300">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span>Gunakan filter <strong>Jumlah Top Donatur</strong> untuk melihat lebih banyak atau sedikit donatur</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span>Filter <strong>Periode Waktu</strong> membantu melihat performa donatur di rentang waktu tertentu</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span>Gunakan <strong>Kategori Donatur</strong> untuk targeting program fundraising yang spesifik</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                <span>Filter <strong>Status Keaktifan</strong> membantu identifikasi donatur yang perlu direaaktivasi</span>
            </div>
        </div>
    </div>
</div>

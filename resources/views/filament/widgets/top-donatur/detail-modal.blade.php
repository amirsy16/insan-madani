<div class="space-y-6">
    <!-- Header Info -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $record->nama }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if($record->nomor_hp)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5h-2C7.163 18 2 12.837 2 5.5V3.5z" clip-rule="evenodd"/>
                            </svg>
                            {{ $record->nomor_hp }}
                        </span>
                    @endif
                    @if($record->email)
                        <span class="flex items-center gap-1 mt-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            {{ $record->email }}
                        </span>
                    @endif
                </p>
            </div>
            <div class="text-right">
                @php
                    $total = $record->total_kontribusi;
                    $kategori = 'Regular';
                    $color = 'gray';
                    
                    if ($total >= 50000000) {
                        $kategori = 'Platinum';
                        $color = 'yellow';
                    } elseif ($total >= 10000000) {
                        $kategori = 'Gold';
                        $color = 'green';
                    } elseif ($total >= 5000000) {
                        $kategori = 'Silver';
                        $color = 'blue';
                    } elseif ($total >= 1000000) {
                        $kategori = 'Bronze';
                        $color = 'orange';
                    }
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900 dark:text-{{ $color }}-200">
                    {{ $kategori }}
                </span>
            </div>
        </div>
    </div>

    <!-- Statistik Utama -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">Total Donasi</div>
            <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                Rp {{ number_format($record->total_kontribusi, 0, ',', '.') }}
            </div>
        </div>
        
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Frekuensi Donasi</div>
            <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {{ $record->total_donasi_count }} kali
            </div>
        </div>
        
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
            <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Rata-rata Donasi</div>
            <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                Rp {{ number_format($record->rata_rata_donasi, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Informasi Tambahan -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Informasi Donasi</h4>
            <dl class="space-y-2">
                @if($record->donasi_pertama)
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Donasi Pertama:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($record->donasi_pertama)->format('d/m/Y') }}
                        </dd>
                    </div>
                @endif
                
                @if($record->donasi_terakhir)
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Donasi Terakhir:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($record->donasi_terakhir)->format('d/m/Y') }}
                        </dd>
                    </div>
                @endif
                
                @if($record->jenis_donasi_terfavorit)
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Jenis Terfavorit:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $record->jenis_donasi_terfavorit }}
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
        
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Informasi Lokasi</h4>
            <dl class="space-y-2">
                @if($record->provinsi)
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Provinsi:</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $record->provinsi }}
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Durasi Keaktifan -->
    @if($record->donasi_pertama && $record->donasi_terakhir)
        @php
            $pertama = \Carbon\Carbon::parse($record->donasi_pertama);
            $terakhir = \Carbon\Carbon::parse($record->donasi_terakhir);
            $durasi = $pertama->diffInDays($terakhir);
            $durasiTahun = intval($durasi / 365);
            $durasiBulan = intval(($durasi % 365) / 30);
            $durasiHari = $durasi % 30;
        @endphp
        
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Durasi Keaktifan</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                @if($durasiTahun > 0)
                    {{ $durasiTahun }} tahun,
                @endif
                @if($durasiBulan > 0)
                    {{ $durasiBulan }} bulan,
                @endif
                {{ $durasiHari }} hari
                
                @if($record->total_donasi_count > 1)
                    <span class="ml-2 text-xs">
                        (Rata-rata {{ round($durasi / max($record->total_donasi_count - 1, 1)) }} hari sekali)
                    </span>
                @endif
            </p>
        </div>
    @endif
</div>

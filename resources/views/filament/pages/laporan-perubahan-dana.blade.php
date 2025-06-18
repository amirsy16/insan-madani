<x-filament-panels::page>
    <style>
        /* Modern Color Palette */
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-light: #dbeafe;
            --primary-blue-dark: #1e40af;
            --success-green: #10b981;
            --success-green-light: #d1fae5;
            --danger-red: #ef4444;
            --danger-red-light: #fee2e2;
            --warning-yellow: #f59e0b;
            --warning-yellow-light: #fef3c7;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        /* Enhanced Card Styling */
        .report-card {
            background: linear-gradient(135deg, white 0%, #f8fafc 100%);
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .dark .report-card {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            border-color: #374151;
        }

        /* Enhanced Table Styling */
        .modern-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .modern-table tr:nth-child(odd) {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        }

        .modern-table tr:nth-child(even) {
            background: white;
        }

        .modern-table tr:hover {
            background: linear-gradient(135deg, var(--primary-blue-light) 0%, #bfdbfe 100%);
            transform: scale(1.001);
            transition: all 0.2s ease;
        }

        .dark .modern-table tr:nth-child(odd) {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
        }

        .dark .modern-table tr:nth-child(even) {
            background: #1f2937;
        }

        .dark .modern-table tr:hover {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        }

        /* Header Styling */
        .section-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            position: relative;
            overflow: hidden;
        }

        .section-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }

        /* Summary Card Enhancement */
        .summary-card {
            background: linear-gradient(135deg, var(--success-green) 0%, #059669 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
        }

        .summary-card.deficit {
            background: linear-gradient(135deg, var(--danger-red) 0%, #dc2626 100%);
        }

        .summary-card.warning {
            background: linear-gradient(135deg, var(--warning-yellow) 0%, #d97706 100%);
        }

        /* Number formatting */
        .currency {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-variant-numeric: tabular-nums;
        }

        /* Animations */
        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }
        .fade-in:nth-child(3) { animation-delay: 0.3s; }
        .fade-in:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-positive {
            background: var(--success-green-light);
            color: var(--success-green);
        }

        .status-negative {
            background: var(--danger-red-light);
            color: var(--danger-red);
        }

        .status-warning {
            background: var(--warning-yellow-light);
            color: var(--warning-yellow);
        }

        .status-neutral {
            background: var(--primary-blue-light);
            color: var(--primary-blue);
        }

        /* Progress Bars */
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 9999px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            border-radius: 9999px;
            transition: width 1s ease-in-out;
            position: relative;
        }

        .progress-fill.positive {
            background: linear-gradient(90deg, var(--success-green) 0%, #059669 100%);
        }

        .progress-fill.negative {
            background: linear-gradient(90deg, var(--danger-red) 0%, #dc2626 100%);
        }

        /* Enhanced Icons */
        .icon-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            font-size: 1.5rem;
        }

        /* Responsive Grid */
        .responsive-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        /* Filter Form Enhancement */
        .filter-form {
            background: linear-gradient(135deg, white 0%, #f8fafc 100%);
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .dark .filter-form {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            border-color: #374151;
        }
    </style>

    <!-- Filter Form -->
    <div class="filter-form fade-in">
        <div class="flex items-center mb-4">
            <div class="icon-container mr-4" style="background: var(--primary-blue);">
                <span class="text-white">📅</span>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Filter Periode Laporan</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Pilih rentang tanggal untuk melihat laporan perubahan dana</p>
            </div>
        </div>
        
        <form wire:submit.prevent="generateReport">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Mulai</label>
                    <input type="date" wire:model="startDate" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Akhir</label>
                    <input type="date" wire:model="endDate" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <x-filament::button type="submit" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Generate Laporan Perubahan Dana
                </x-filament::button>
                
                @if(!empty($reportData))
                <x-filament::button wire:click="downloadPDF" color="success" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download PDF
                </x-filament::button>
                @endif
            </div>
        </form>
    </div>

    <!-- Main Content -->
    <div class="space-y-8">
        @php
            // Filter out summary data from main report loop
            $filteredReportData = collect($reportData)->filter(function($data, $key) {
                return $key !== 'summary' && is_array($data) && isset($data['title']);
            });
            
            // Use totals from summary data (already excludes "Penyaluran Langsung")
            $totalPenerimaan = $summaryData['total_penerimaan'] ?? 0;
            $totalPenyaluran = $summaryData['total_penyaluran'] ?? 0;
            $totalSaldoAkhir = $summaryData['total_saldo_akhir'] ?? 0;
            
            // Note: These totals are calculated from DanaService and already exclude "Penyaluran Langsung"
            // This ensures consistency with RingkasanStatistikUtama widget
        @endphp
        
        @if($filteredReportData->count() > 0)
        
        <!-- Quick Summary Cards -->
        <div class="responsive-grid fade-in">
            <div class="report-card">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="icon-container mr-4" style="background: var(--success-green);">
                                <span class="text-white">💰</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Penerimaan</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white currency">
                                Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
                            </p>
                            <div class="progress-bar mt-2">
                                <div class="progress-fill positive" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="report-card">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="icon-container mr-4" style="background: var(--primary-blue);">
                                <span class="text-white">📤</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Penyaluran</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white currency">
                                Rp {{ number_format($totalPenyaluran, 0, ',', '.') }}
                            </p>
                            @php
                                $penyaluranPercentage = $totalPenerimaan > 0 ? ($totalPenyaluran / $totalPenerimaan) * 100 : 0;
                            @endphp
                            <div class="progress-bar mt-2">
                                <div class="progress-fill" style="width: {{ min($penyaluranPercentage, 100) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ number_format($penyaluranPercentage, 1) }}% dari penerimaan</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="report-card">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="icon-container mr-4" style="background: {{ $totalSaldoAkhir >= 0 ? 'var(--success-green)' : 'var(--danger-red)' }};">
                                <span class="text-white">{{ $totalSaldoAkhir >= 0 ? '🏦' : '⚠️' }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sisa Saldo</p>
                            <p class="text-2xl font-bold {{ $totalSaldoAkhir >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} currency">
                                Rp {{ number_format(abs($totalSaldoAkhir), 0, ',', '.') }}
                            </p>
                            <span class="status-indicator {{ $totalSaldoAkhir >= 0 ? 'status-positive' : 'status-negative' }}">
                                {{ $totalSaldoAkhir >= 0 ? 'Surplus' : 'Defisit' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Reports by Fund Type -->
        @foreach ($filteredReportData as $fundKey => $data)
            <div class="report-card fade-in">
                <!-- Header -->
                <div class="section-header">
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center">
                            <div class="icon-container mr-4">
                                <span>{{ chr(64 + $loop->iteration) }}</span>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold">{{ strtoupper($data['title']) }}</h2>
                                <p class="text-blue-100 text-sm">
                                    Periode: {{ Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-blue-100 text-sm">Saldo Akhir</p>
                            <p class="text-2xl font-bold currency">
                                Rp {{ number_format($data['saldo_akhir'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    {{-- Special handling for "Penyaluran Langsung" --}}
                    @if(isset($data['is_penyaluran_langsung']) && $data['is_penyaluran_langsung'])
                        <!-- Penyaluran Langsung Special Section -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6 mb-6">
                            <div class="flex items-center mb-4">
                                <div class="icon-container mr-3" style="background: var(--primary-blue);">
                                    <span class="text-white">ℹ️</span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-blue-800 dark:text-blue-200">Informasi Penyaluran Langsung</h4>
                                    <p class="text-sm text-blue-600 dark:text-blue-400">{{ $data['keterangan'] ?? 'Donasi langsung disalurkan oleh donatur' }}</p>
                                </div>
                            </div>
                            
                            @if(isset($data['total_donasi_terdokumentasi']) && $data['total_donasi_terdokumentasi'] > 0)
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-700 dark:text-gray-300">Total Donasi Terdokumentasi:</span>
                                        <span class="font-bold text-blue-600 dark:text-blue-400">
                                            Rp {{ number_format($data['total_donasi_terdokumentasi'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="modern-table">
                        <table class="w-full">
                            <tbody>
                                {{-- Conditional display based on fund type --}}
                                @if(!isset($data['is_penyaluran_langsung']) || !$data['is_penyaluran_langsung'])
                                    <!-- Saldo Awal (hanya untuk dana yang dikelola) -->
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                            💼 Saldo Awal
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold currency text-blue-600 dark:text-blue-400">
                                            Rp {{ number_format($data['saldo_awal'] ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif

                                <!-- Penerimaan Dana -->
                                <tr class="bg-green-50 dark:bg-green-900/20">
                                    <td class="px-4 py-4 font-bold text-lg text-green-800 dark:text-green-200">
                                        @if(isset($data['is_penyaluran_langsung']) && $data['is_penyaluran_langsung'])
                                            📋 DOKUMENTASI DONASI
                                        @else
                                            📈 1. PENERIMAAN DANA
                                        @endif
                                    </td>
                                    <td class="px-4 py-4"></td>
                                </tr>

                                <!-- Rincian Penerimaan -->
                                @if(isset($data['rincian_penerimaan']) && count($data['rincian_penerimaan']) > 0)
                                    @foreach($data['rincian_penerimaan'] as $jenis => $jumlah)
                                        <tr>
                                            <td class="px-4 py-2 pl-8 text-gray-700 dark:text-gray-300">
                                                <span class="inline-flex items-center">
                                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                                    {{ $jenis }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-right currency font-semibold text-green-600 dark:text-green-400">
                                                Rp {{ number_format($jumlah, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    
                                    <!-- Subtotal Penerimaan -->
                                    <tr class="border-t-2 border-green-200 dark:border-green-700">
                                        <td class="px-4 py-3 pl-8 font-semibold text-green-800 dark:text-green-200">
                                            @if(isset($data['is_penyaluran_langsung']) && $data['is_penyaluran_langsung'])
                                                Total Donasi Terdokumentasi
                                            @else
                                                Total Penerimaan
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-lg currency text-green-600 dark:text-green-400">
                                            @if(isset($data['is_penyaluran_langsung']) && $data['is_penyaluran_langsung'])
                                                Rp {{ number_format($data['total_donasi_terdokumentasi'] ?? 0, 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format($data['penerimaan'], 0, ',', '.') }}
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Bagian Amil (jika ada) - TIDAK untuk Penyaluran Langsung -->
                                    @if(!isset($data['is_penyaluran_langsung']) || !$data['is_penyaluran_langsung'])
                                        @if(isset($data['bagian_amil']) && $data['bagian_amil'] > 0 && strtolower($data['title']) !== 'hak amil')
                                            <tr>
                                                <td class="px-4 py-2 pl-8 text-orange-700 dark:text-orange-300">
                                                    @php
                                                        // Ambil persentase hak amil dari sumber dana
                                                        $persentaseHakAmil = 12; // default
                                                        if (isset($data['sumber_dana_id'])) {
                                                            $sumberDana = \App\Models\SumberDanaPenyaluran::find($data['sumber_dana_id']);
                                                            if ($sumberDana) {
                                                                $persentaseHakAmil = $sumberDana->persentase_hak_amil;
                                                            }
                                                        }
                                                    @endphp
                                                    <span class="inline-flex items-center">
                                                        <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                                                        Dikurangi: Bagian Amil ({{ number_format($persentaseHakAmil, 1) }}%)
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-right currency font-semibold text-orange-600 dark:text-orange-400">
                                                    (Rp {{ number_format($data['bagian_amil'], 0, ',', '.') }})
                                                </td>
                                            </tr>
                                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                                <td class="px-4 py-3 pl-8 font-semibold text-gray-800 dark:text-gray-200">
                                                    Penerimaan Bersih
                                                </td>
                                                <td class="px-4 py-3 text-right font-bold currency text-blue-600 dark:text-blue-400">
                                                    Rp {{ number_format(($data['penerimaan'] ?? 0) - ($data['bagian_amil'] ?? 0), 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endif
                                @else
                                    <tr>
                                        <td class="px-4 py-3 pl-8 text-gray-500 dark:text-gray-400 italic">
                                            Tidak ada penerimaan dalam periode ini
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold currency text-green-600 dark:text-green-400">
                                            Rp {{ number_format($data['penerimaan'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif

                                <!-- Penyaluran Dana -->
                                <tr class="bg-red-50 dark:bg-red-900/20">
                                    <td class="px-4 py-4 font-bold text-lg text-red-800 dark:text-red-200">
                                        📤 2. PENYALURAN DANA
                                    </td>
                                    <td class="px-4 py-4"></td>
                                </tr>

                                <!-- Penyaluran berdasarkan Asnaf -->
                                @if(isset($data['rincian_penyaluran_asnaf']) && count($data['rincian_penyaluran_asnaf']) > 0)
                                    <tr class="bg-blue-50 dark:bg-blue-900/20">
                                        <td class="px-4 py-3 pl-8 font-semibold text-blue-800 dark:text-blue-200">
                                            👥 2.1 Penyaluran berdasarkan Asnaf
                                        </td>
                                        <td class="px-4 py-3"></td>
                                    </tr>
                                    @foreach($data['rincian_penyaluran_asnaf'] as $asnaf => $jumlah)
                                        <tr>
                                            <td class="px-4 py-2 pl-12 text-gray-700 dark:text-gray-300">
                                                <span class="inline-flex items-center">
                                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                                    {{ $asnaf }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-right currency font-semibold text-red-600 dark:text-red-400">
                                                Rp {{ number_format($jumlah, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                <!-- Penyaluran berdasarkan Bidang Program -->
                                @if(isset($data['rincian_penyaluran_bidang']) && count($data['rincian_penyaluran_bidang']) > 0)
                                    <tr class="bg-purple-50 dark:bg-purple-900/20">
                                        <td class="px-4 py-3 pl-8 font-semibold text-purple-800 dark:text-purple-200">
                                            📋 2.2 Penyaluran berdasarkan Bidang Program
                                        </td>
                                        <td class="px-4 py-3"></td>
                                    </tr>
                                    @foreach($data['rincian_penyaluran_bidang'] as $bidang => $jumlah)
                                        <tr>
                                            <td class="px-4 py-2 pl-12 text-gray-700 dark:text-gray-300">
                                                <span class="inline-flex items-center">
                                                    <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                                                    {{ $bidang }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-right currency font-semibold text-purple-600 dark:text-purple-400">
                                                Rp {{ number_format($jumlah, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                <!-- Total Penyaluran -->
                                <tr class="border-t-2 border-red-200 dark:border-red-700">
                                    <td class="px-4 py-3 pl-8 font-semibold text-red-800 dark:text-red-200">
                                        Total Penyaluran
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-lg currency text-red-600 dark:text-red-400">
                                        Rp {{ number_format($data['penyaluran'] ?? 0, 0, ',', '.') }}
                                    </td>
                                </tr>

                                <!-- Saldo Akhir -->
                                <tr class="border-t-4 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                                    <td class="px-4 py-4 font-bold text-xl text-gray-900 dark:text-white">
                                        @if(isset($data['is_penyaluran_langsung']) && $data['is_penyaluran_langsung'])
                                            📊 STATUS PENYALURAN
                                        @else
                                            🏦 SALDO AKHIR
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex items-center justify-end">
                                            @if(isset($data['is_penyaluran_langsung']) && $data['is_penyaluran_langsung'])
                                                <span class="text-2xl font-bold currency text-blue-600 dark:text-blue-400">
                                                    BALANCED
                                                </span>
                                                <span class="ml-2 status-indicator status-neutral">
                                                    Direct Distribution
                                                </span>
                                            @else
                                                <span class="text-2xl font-bold currency {{ ($data['saldo_akhir'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    Rp {{ number_format(abs($data['saldo_akhir'] ?? 0), 0, ',', '.') }}
                                                </span>
                                                <span class="ml-2 status-indicator {{ ($data['saldo_akhir'] ?? 0) >= 0 ? 'status-positive' : 'status-negative' }}">
                                                    {{ ($data['saldo_akhir'] ?? 0) >= 0 ? 'Surplus' : 'Defisit' }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Fund Health Indicator -->
                    @if(isset($data['is_penyaluran_langsung']) && $data['is_penyaluran_langsung'])
                        <!-- Special indicator for Penyaluran Langsung -->
                        <div class="mt-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="icon-container mr-3" style="background: var(--primary-blue);">
                                        <span class="text-white">🔄</span>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-blue-800 dark:text-blue-200">
                                            Status Penyaluran Langsung
                                        </h4>
                                        <p class="text-sm text-blue-600 dark:text-blue-400">
                                            Dana tidak masuk ke kas organisasi - disalurkan langsung oleh donatur
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Mode Distribusi</p>
                                    <p class="text-lg font-bold text-blue-600">Direct Transfer</p>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                            ✅ No Impact on Cash Flow
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Regular fund health indicator -->
                        <div class="mt-6 p-4 rounded-lg {{ ($data['saldo_akhir'] ?? 0) >= 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="icon-container mr-3" style="background: {{ ($data['saldo_akhir'] ?? 0) >= 0 ? 'var(--success-green)' : 'var(--danger-red)' }};">
                                        <span class="text-white">{{ ($data['saldo_akhir'] ?? 0) >= 0 ? '✅' : '⚠️' }}</span>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold {{ ($data['saldo_akhir'] ?? 0) >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                            Status Kesehatan Dana
                                        </h4>
                                        <p class="text-sm {{ ($data['saldo_akhir'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ ($data['saldo_akhir'] ?? 0) >= 0 ? 'Dana dalam kondisi sehat dengan saldo positif' : 'Perlu perhatian: Saldo defisit' }}
                                        </p>
                                    </div>
                                </div>
                                @php
                                    $utilizationRate = ($data['penerimaan'] ?? 0) > 0 ? (($data['penyaluran'] ?? 0) / ($data['penerimaan'] ?? 0)) * 100 : 0;
                                @endphp
                                <div class="text-right">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Tingkat Penyaluran</p>
                                    <p class="text-lg font-bold {{ $utilizationRate >= 80 ? 'text-green-600' : ($utilizationRate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ number_format($utilizationRate, 1) }}%
                                    </p>
                                    <div class="progress-bar mt-1" style="width: 100px;">
                                        <div class="progress-fill {{ $utilizationRate >= 80 ? 'positive' : ($utilizationRate >= 50 ? '' : 'negative') }}" 
                                             style="width: {{ min($utilizationRate, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- Grand Summary Section -->
        <div class="report-card fade-in">
            <div class="section-header">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center">
                        <div class="icon-container mr-4">
                            <span>📊</span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold">RINGKASAN KESELURUHAN</h2>
                            <p class="text-blue-100 text-sm">Total seluruh sumber dana periode ini</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-blue-100 text-sm">Status Keseluruhan</p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white">
                            {{ $totalSaldoAkhir >= 0 ? '✅ Sehat' : '⚠️ Perlu Perhatian' }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Penerimaan -->
                    <div class="text-center p-6 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <div class="icon-container mx-auto mb-4" style="background: var(--success-green);">
                            <span class="text-white">💰</span>
                        </div>
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200 mb-2">Total Penerimaan</h3>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400 currency">
                            Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
                        </p>
                        <div class="progress-bar mt-3">
                            <div class="progress-fill positive" style="width: 100%"></div>
                        </div>
                    </div>

                    <!-- Total Penyaluran -->
                    <div class="text-center p-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <div class="icon-container mx-auto mb-4" style="background: var(--primary-blue);">
                            <span class="text-white">📤</span>
                        </div>
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Total Penyaluran</h3>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 currency">
                            Rp {{ number_format($totalPenyaluran, 0, ',', '.') }}
                        </p>
                        @php
                            $overallPercentage = $totalPenerimaan > 0 ? ($totalPenyaluran / $totalPenerimaan) * 100 : 0;
                        @endphp
                        <div class="progress-bar mt-3">
                            <div class="progress-fill" style="width: {{ min($overallPercentage, 100) }}%"></div>
                        </div>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                            {{ number_format($overallPercentage, 1) }}% dari penerimaan
                        </p>
                    </div>

                    <!-- Sisa Saldo -->
                    <div class="text-center p-6 {{ $totalSaldoAkhir >= 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }} rounded-xl">
                        <div class="icon-container mx-auto mb-4" style="background: {{ $totalSaldoAkhir >= 0 ? 'var(--success-green)' : 'var(--danger-red)' }};">
                            <span class="text-white">{{ $totalSaldoAkhir >= 0 ? '🏦' : '⚠️' }}</span>
                        </div>
                        <h3 class="text-sm font-medium {{ $totalSaldoAkhir >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }} mb-2">
                            Sisa Saldo
                        </h3>
                        <p class="text-3xl font-bold {{ $totalSaldoAkhir >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} currency">
                            Rp {{ number_format(abs($totalSaldoAkhir), 0, ',', '.') }}
                        </p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mt-3 {{ $totalSaldoAkhir >= 0 ? 'status-positive' : 'status-negative' }}">
                            {{ $totalSaldoAkhir >= 0 ? 'SURPLUS' : 'DEFISIT' }}
                        </span>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Efisiensi Penyaluran</h4>
                        <p class="text-xl font-bold {{ $overallPercentage >= 80 ? 'text-green-600' : ($overallPercentage >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($overallPercentage, 1) }}%
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Jumlah Sumber Dana</h4>
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $filteredReportData->count() }}
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Rata-rata Saldo</h4>
                        <p class="text-xl font-bold text-gray-900 dark:text-white currency">
                            Rp {{ $filteredReportData->count() > 0 ? number_format($totalSaldoAkhir / $filteredReportData->count(), 0, ',', '.') : '0' }}
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">Periode Laporan</h4>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ Carbon\Carbon::parse($startDate)->diffInDays(Carbon\Carbon::parse($endDate)) + 1 }} Hari
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @else
        <!-- Empty State -->
        <div class="report-card fade-in">
            <div class="p-12 text-center">
                <div class="icon-container mx-auto mb-6" style="background: var(--gray-100); width: 5rem; height: 5rem; font-size: 2.5rem;">
                    <span class="text-gray-400">📊</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Belum Ada Data</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    Silakan pilih periode tanggal dan klik "Generate Laporan Perubahan Dana" untuk melihat data laporan.
                </p>
                <div class="inline-flex items-center px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Gunakan filter di atas untuk memulai
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- JavaScript for Enhanced Interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate elements on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observe all fade-in elements
            document.querySelectorAll('.fade-in').forEach(el => {
                observer.observe(el);
            });
            
            // Animate progress bars
            setTimeout(() => {
                document.querySelectorAll('.progress-fill').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 100);
                });
            }, 500);
            
            // Add hover effects to report cards
            document.querySelectorAll('.report-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Format currency on load
            document.querySelectorAll('.currency').forEach(el => {
                const text = el.textContent;
                if (text.includes('Rp')) {
                    el.style.fontFamily = '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif';
                    el.style.fontVariantNumeric = 'tabular-nums';
                }
            });
        });
    </script>
</x-filament-panels::page>
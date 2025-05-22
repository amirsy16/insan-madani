<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-xl font-bold tracking-tight">
                    Tren Donasi {{ $this->timeRange === '1_year' ? '(1 Tahun Terakhir)' : '(6 Bulan Terakhir)' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Menampilkan total donasi (uang dan nilai barang) terverifikasi per bulan
                </p>
            </div>
            
            <div class="flex space-x-2">
                <select
                    wire:model.live="jenisDonasi"
                    wire:change="$refresh"
                    class="text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                >
                    <option value="">Semua Jenis Donasi</option>
                    @foreach ($this->jenisDonasiOptions as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
                
                <select
                    wire:model.live="timeRange"
                    wire:change="$refresh"
                    class="text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                >
                    @foreach ($this->timeRangeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div
            x-data="{
                chartData: {{ json_encode($this->getChartData()) }},
                chartOptions: {{ json_encode($this->getChartOptions()) }},
                chartType: {{ json_encode($this->getChartType()) }},
                chart: null,
                
                init() {
                    this.initChart();
                    
                    // Re-render chart when Livewire component is updated
                    this.$watch('chartData', () => {
                        this.updateChart();
                    });
                },
                
                initChart() {
                    // Pastikan Chart.js tersedia
                    if (typeof Chart === 'undefined') {
                        // Jika Chart.js belum dimuat, muat dari CDN
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                        script.onload = () => this.createChart();
                        document.head.appendChild(script);
                    } else {
                        this.createChart();
                    }
                },
                
                createChart() {
                    // Parse function strings in options
                    this.parseOptionFunctions(this.chartOptions);
                    
                    // Destroy existing chart if it exists
                    if (this.chart) {
                        this.chart.destroy();
                    }
                    
                    // Create new chart
                    this.chart = new Chart(
                        this.$refs.canvas,
                        {
                            type: this.chartType,
                            data: this.chartData,
                            options: this.chartOptions
                        }
                    );
                },
                
                updateChart() {
                    if (this.chart) {
                        this.chart.data = this.chartData;
                        this.chart.update();
                    }
                },
                
                parseOptionFunctions(obj) {
                    for (const key in obj) {
                        if (typeof obj[key] === 'string' && obj[key].includes('function(')) {
                            obj[key] = new Function('return ' + obj[key])();
                        } else if (typeof obj[key] === 'object' && obj[key] !== null) {
                            this.parseOptionFunctions(obj[key]);
                        }
                    }
                }
            }"
            wire:key="{{ $this->timeRange }}-{{ $this->jenisDonasi }}"
        >
            <div class="h-80">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>




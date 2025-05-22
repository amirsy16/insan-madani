<x-filament-panels::page>
    {{-- Section untuk Filter --}}
    <div class="mb-6">
        <form wire:submit.prevent="submitFilters" class="p-4 bg-white rounded-xl shadow dark:bg-gray-800">
            {{ $this->form }} {{-- Ini akan merender form filter yang didefinisikan di getFormSchema() --}}
        </form>
    </div>

    {{-- Section untuk Ringkasan Total --}}
    <div class="mb-6 p-4 bg-white rounded-xl shadow dark:bg-gray-800">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Ringkasan Pemasukan (Berdasarkan Filter)</h3>
        <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="overflow-hidden rounded-lg bg-gray-50 dark:bg-gray-700 px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Pemasukan (Uang)</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Rp {{ number_format($this->totalPemasukan, 0, ',', '.') }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-gray-50 dark:bg-gray-700 px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Perkiraan Nilai Barang</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Rp {{ number_format($this->totalNilaiBarang, 0, ',', '.') }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-primary-50 dark:bg-primary-800 px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-primary-700 dark:text-primary-300">Grand Total Pemasukan</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-primary-900 dark:text-primary-100">
                    Rp {{ number_format($this->grandTotalPemasukan, 0, ',', '.') }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Section untuk Tabel Data --}}
    <div>
        {{ $this->table }} {{-- Ini akan merender tabel yang didefinisikan di method table() --}}
    </div>
</x-filament-panels::page>
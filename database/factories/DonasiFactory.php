<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donasi>
 */
class DonasiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donatur_id' => \App\Models\Donatur::factory(),
            'jenis_donasi_id' => \App\Models\JenisDonasi::factory(),
            'jumlah' => $this->faker->numberBetween(50000, 5000000),
            'tanggal_donasi' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'nomor_transaksi_unik' => 'TRX' . strtoupper(uniqid()),
            'status_konfirmasi' => $this->faker->randomElement(['pending', 'verified', 'rejected']),
            'atas_nama_hamba_allah' => $this->faker->boolean(10), // 10% chance anonim
            'catatan_donatur' => $this->faker->optional()->sentence,
            'dicatat_oleh_user_id' => \App\Models\User::factory(),
        ];
    }
}

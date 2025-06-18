<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JenisDonasi>
 */
class JenisDonasiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => $this->faker->randomElement(['Infaq Umum', 'Zakat Fitrah', 'Zakat Mal', 'Sedekah', 'Wakaf']),
            'deskripsi' => $this->faker->sentence,
            'apakah_barang' => $this->faker->boolean(20), // 20% chance untuk barang
            'membutuhkan_keterangan_tambahan' => $this->faker->boolean(30),
            'aktif' => true,
        ];
    }
}

<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Fundraiser;
use App\Models\User; // Jika Anda ingin mengaitkan dengan user
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class FundraiserSeeder extends Seeder {
    public function run(): void {
          DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Fundraiser::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $faker = Faker::create('id_ID');
        // Ambil beberapa user admin untuk dijadikan fundraiser (opsional)
            Fundraiser::create([
                'nama_fundraiser' => $faker->name,
                'nomor_hp' => $faker->unique()->phoneNumber,
                'alamat' => $faker->address,
                'aktif' => $faker->boolean(80), // 80% aktif
            ]);
        }
    }

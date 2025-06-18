<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Donatur;
use App\Models\Pekerjaan;
use App\Models\Province;
use App\Models\Regency; // Ganti City dengan Regency
use App\Models\District;
use App\Models\Village;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DonaturSeeder extends Seeder {
    public function run(): void {
        // Nonaktifkan foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Donatur::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $faker = Faker::create('id_ID');
        
        // Get all pekerjaan IDs
        $pekerjaanIds = Pekerjaan::pluck('id')->all();
        
        // Get region IDs if available
        $provinceIds = [];
        $regencyIds = []; // Ganti cityIds dengan regencyIds
        $districtIds = [];
        $villageIds = [];
        
        if (Schema::hasTable('provinces')) {
            $provinceIds = Province::pluck('id')->all();
        }
        
        if (Schema::hasTable('regencies')) { // Ganti cities dengan regencies
            $regencyIds = Regency::pluck('id')->all(); // Ganti City dengan Regency
        }
        
        if (Schema::hasTable('districts')) {
            $districtIds = District::pluck('id')->all();
        }
        
        if (Schema::hasTable('villages')) {
            $villageIds = Village::pluck('id')->all();
        }
        
        // Buat 20 donatur contoh
        for ($i = 0; $i < 20; $i++) {
            $gender = $faker->randomElement(['male', 'female']);
            $name = $gender === 'male' ? $faker->firstNameMale : $faker->firstNameFemale;
            $name .= ' ' . $faker->lastName;
            
            // Select random regions if available
            $provinceId = !empty($provinceIds) ? $faker->randomElement($provinceIds) : null;
            $regencyId = !empty($regencyIds) ? $faker->randomElement($regencyIds) : null; // Ganti cityId dengan regencyId
            $districtId = !empty($districtIds) ? $faker->randomElement($districtIds) : null;
            $villageId = !empty($villageIds) ? $faker->randomElement($villageIds) : null;
            
            // Create donatur with address details
            $donatur = new Donatur([
                'gender' => $gender,
                'nama' => $name,
                'alamat_detail' => $faker->streetAddress,
                'province_id' => $provinceId,
                'city_id' => $regencyId, // Tetap gunakan city_id di database, tapi isi dengan regencyId
                'district_id' => $districtId,
                'village_id' => $villageId,
                'nomor_hp' => $faker->unique()->phoneNumber,
                'pekerjaan_id' => $faker->randomElement($pekerjaanIds),
            ]);
            
            // Generate full address
            $donatur->alamat_lengkap = $donatur->generateFullAddress();
            $donatur->save();
        }
        
        // Donatur "Hamba Allah" untuk donasi anonim umum
        Donatur::create([
            'gender' => 'male',
            'nama' => 'Hamba Allah',
            'alamat_detail' => '-',
            'alamat_lengkap' => '-',
            'nomor_hp' => '0000000000',
            'pekerjaan_id' => null,
        ]);
    }
}



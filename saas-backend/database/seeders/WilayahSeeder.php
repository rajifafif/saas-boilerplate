<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\District;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Kota;
use App\Models\Province;
use App\Models\Provinsi;
use App\Models\Village;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provinceChunks = [];
        $cityChunks = [];
        $districtChunks = [];
        $villageChunks = [];

        $time = now()->format('Y-m-d H:i:s');
        $rowCount=0;
        Provinsi::truncate();
        Kota::truncate();
        Kecamatan::truncate();
        Kelurahan::truncate();

        ini_set('memory_limit', '512M');

        if (($handle = fopen(resource_path("files/wilayah.txt"), "r")) !== FALSE) {
            $firstline = true;
            while (($file = fgetcsv($handle, 0, "\n")) !== FALSE) {
                if (!(str_contains($file[0], "),") || str_contains($file[0], ");"))) {
                    continue;
                }

                $file[0] = str_replace("'),", '', $file[0]);
                $file[0] = str_replace("');", '', $file[0]);
                $file[0] = str_replace("('", '', $file[0]);
                $file[0] = str_replace("','", ';', $file[0]);

                $row = explode(';', $file[0]);

                $code = $row[0];
                $code = str_replace('.', '', $code);
                $name = $row[1];

                if (strlen($code) == 2) {
                    // Province
                    $provinceChunks[] = [
                        'id' => $code,
                        'country_code' => 'ID',
                        'name' => ucwords(strtolower($name)),
                        'created_at' => $time,
                        'updated_at' => $time
                    ];
                }

                if (strlen($code) == 4) {
                    // City
                    $cityChunks[] = [
                        'provinsi_id' => substr($code, 0, 2),
                        'id' => $code,
                        'name' => ucwords(strtolower($name)),
                        'created_at' => $time,
                        'updated_at' => $time
                    ];
                }

                if (strlen($code) == 6) {
                    // District
                    $districtChunks[] = [
                        'provinsi_id' => substr($code, 0, 2),
                        'kota_id' => substr($code, 0, 4),
                        'id' => $code,
                        'name' => $name,
                        'created_at' => $time,
                        'updated_at' => $time
                    ];
                }

                if (strlen($code) == 10) {
                    // Village
                    $villageChunks[] = [
                        'provinsi_id' => substr($code, 0, 2),
                        'kota_id' => substr($code, 0, 4),
                        'kecamatan_id' => substr($code, 0, 6),
                        'id' => $code,
                        'name' => $name,
                        'created_at' => $time,
                        'updated_at' => $time
                    ];
                }
                $rowCount++;


                if ($rowCount == 100) {
                    Provinsi::insert($provinceChunks);
                    Kota::insert($cityChunks);
                    Kecamatan::insert($districtChunks);
                    Kelurahan::insert($villageChunks);

                    // Reset Chunks
                    $provinceChunks = [];
                    $cityChunks = [];
                    $districtChunks = [];
                    $villageChunks = [];


                    $rowCount = 0;
                }
            }

            fclose($handle);
        }

        if ($rowCount > 0) {
            Provinsi::insert($provinceChunks);
            Kota::insert($cityChunks);
            Kecamatan::insert($districtChunks);
            Kelurahan::insert($villageChunks);

            $provinceChunks = [];
            $cityChunks = [];
            $districtChunks = [];
            $villageChunks = [];
        }

        $this->call([
            PostalCodeSeeder::class,
        ]);
    }
}

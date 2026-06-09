<?php

namespace Database\Seeders;

use App\Models\KodePos;
use App\Models\PostalCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostalCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $postals = [];

        $time = now()->format('Y-m-d H:i:s');
        $rowCount=0;
        KodePos::truncate();

        ini_set('memory_limit', '512M');

        if (($handle = fopen(resource_path("files/wilayah_kodepos.sql"), "r")) !== FALSE) {
            $firstline = true;
            while (($file = fgetcsv($handle, 0, "\n")) !== FALSE) {
                if (!(str_contains($file[0], "'),") || str_contains($file[0], "');"))) {
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

                $postals[] = [
                    'kelurahan_id' => $code,
                    'code' => $name,
                    'created_at' => $time,
                    'updated_at' => $time
                ];

                $rowCount++;


                if ($rowCount == 1000) {
                    KodePos::insert($postals);
                    // Reset Chunks
                    $postals = [];
                    $rowCount = 0;
                }
            }

            fclose($handle);
        }

        if ($rowCount > 0) {
            KodePos::insert($postals);

            $postals = [];
        }
    }
}

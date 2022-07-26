<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CardIssuerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\CardsIssuer::truncate();

        $csvFile = fopen(base_path("database/data/card_issuer.csv"), "r");

        $firstline = true;
        while (($data = fgetcsv($csvFile, 2000, ";")) !== FALSE) {
            if (!$firstline) {
                \App\CardsIssuer::create([
                    "name" => trim(rtrim($data['0'], ";"))
                ]);
            }
            $firstline = false;
        }

        fclose($csvFile);
    }
}

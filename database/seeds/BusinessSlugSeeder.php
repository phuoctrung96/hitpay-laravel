<?php

use Illuminate\Database\Seeder;

use App\Business;

class BusinessSlugSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $businesses = Business::whereNull('slug')->get();

        foreach ($businesses as $business) {
            $business->slug = generate_unique_slug($business->name);

            $business->update();
        }
    }
}
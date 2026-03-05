<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- ALGERIA ---
        // Hierarchy: country -> wilaya -> daira -> commune
        $algeria = Region::create([
            'name' => 'Algeria',
            'type' => 'country',
            'depth' => 0,
            'code' => 'DZ'
        ]);

        $algiersWilaya = Region::create([
            'name' => 'Alger',
            'type' => 'wilaya',
            'parent_id' => $algeria->id,
            'depth' => 1,
            'code' => '16'
        ]);

        $sidiMhamedDaira = Region::create([
            'name' => 'Sidi M\'Hamed',
            'type' => 'daira',
            'parent_id' => $algiersWilaya->id,
            'depth' => 2
        ]);

        Region::create([
            'name' => 'Alger Centre',
            'type' => 'commune',
            'parent_id' => $sidiMhamedDaira->id,
            'depth' => 3
        ]);

        // --- FRANCE ---
        // Hierarchy: country -> région -> département -> arrondissement -> commune
        $france = Region::create([
            'name' => 'France',
            'type' => 'country',
            'depth' => 0,
            'code' => 'FR'
        ]);

        $ileDeFrance = Region::create([
            'name' => 'Île-de-France',
            'type' => 'région',
            'parent_id' => $france->id,
            'depth' => 1
        ]);

        $parisDept = Region::create([
            'name' => 'Paris',
            'type' => 'département',
            'parent_id' => $ileDeFrance->id,
            'depth' => 2,
            'code' => '75'
        ]);

        $parisArr = Region::create([
            'name' => 'Paris',
            'type' => 'arrondissement',
            'parent_id' => $parisDept->id,
            'depth' => 3
        ]);

        Region::create([
            'name' => 'Paris 1er Arrondissement',
            'type' => 'commune',
            'parent_id' => $parisArr->id,
            'depth' => 4
        ]);

        // --- USA ---
        // Hierarchy: country -> state -> county -> city (simplification of common US div)
        $usa = Region::create([
            'name' => 'USA',
            'type' => 'country',
            'depth' => 0,
            'code' => 'US'
        ]);

        $california = Region::create([
            'name' => 'California',
            'type' => 'state',
            'parent_id' => $usa->id,
            'depth' => 1,
            'code' => 'CA'
        ]);

        $losAngelesCounty = Region::create([
            'name' => 'Los Angeles County',
            'type' => 'county',
            'parent_id' => $california->id,
            'depth' => 2
        ]);

        Region::create([
            'name' => 'Los Angeles',
            'type' => 'city',
            'parent_id' => $losAngelesCounty->id,
            'depth' => 3
        ]);
    }
}

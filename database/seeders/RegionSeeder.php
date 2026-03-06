<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        // --- ALGERIA ---
        $algeria = Region::create(['name' => 'Algeria', 'type' => 'country', 'depth' => 0, 'code' => 'DZ']);

        $wilayas = [
            ['name' => 'Adrar', 'code' => '01', 'dairas' => [
                ['name' => 'Adrar', 'communes' => ['Adrar', 'Bouda', 'Ouled Ahmed Tammi']],
                ['name' => 'Fenoughil', 'communes' => ['Fenoughil', 'Tamest', 'Tami']],
            ]],
            ['name' => 'Chlef', 'code' => '02', 'dairas' => [
                ['name' => 'Chlef', 'communes' => ['Chlef', 'Sendlous', 'Oum Drou']],
                ['name' => 'Ténès', 'communes' => ['Ténès', 'Sidi Abderrahmane', 'Sidi Akkacha']],
            ]],
            ['name' => 'Béjaïa', 'code' => '06', 'dairas' => [
                ['name' => 'Béjaïa', 'communes' => ['Béjaïa', 'Oued Ghir']],
                ['name' => 'Amizour', 'communes' => ['Amizour', 'Béni Djellil', 'Semaoun', 'Ferraoun']],
            ]],
            ['name' => 'Blida', 'code' => '09', 'dairas' => [
                ['name' => 'Blida', 'communes' => ['Blida', 'Bouarfa']],
                ['name' => 'Boufarik', 'communes' => ['Boufarik', 'Soumaa', 'Guerrouaou']],
            ]],
            ['name' => 'Tizi Ouzou', 'code' => '15', 'dairas' => [
                ['name' => 'Tizi Ouzou', 'communes' => ['Tizi Ouzou']],
                ['name' => 'Azazga', 'communes' => ['Azazga', 'Freha', 'Ifigha', 'Yakouren', 'Zekri']],
            ]],
            ['name' => 'Alger', 'code' => '16', 'dairas' => [
                ['name' => 'Sidi M\'Hamed', 'communes' => ['Alger Centre', 'Sidi M\'Hamed', 'Mohamed Belouizdad', 'El Madania']],
                ['name' => 'Bab El Oued', 'communes' => ['Bab El Oued', 'Casbah', 'Bologhine', 'Oued Koriche', 'Rais Hamidou']],
                ['name' => 'Bouzareah', 'communes' => ['Bouzareah', 'Beni Messous', 'Cheraga', 'Dely Ibrahim']],
            ]],
            ['name' => 'Sétif', 'code' => '19', 'dairas' => [
                ['name' => 'Sétif', 'communes' => ['Sétif']],
                ['name' => 'El Eulma', 'communes' => ['El Eulma', 'Belaa', 'Guelta Zerka']],
            ]],
            ['name' => 'Oran', 'code' => '31', 'dairas' => [
                ['name' => 'Oran', 'communes' => ['Oran']],
                ['name' => 'Bir El Djir', 'communes' => ['Bir El Djir', 'Hassi Bounif', 'Hassi Ben Okba']],
            ]],
            ['name' => 'Constantine', 'code' => '25', 'dairas' => [
                ['name' => 'Constantine', 'communes' => ['Constantine']],
                ['name' => 'El Khroub', 'communes' => ['El Khroub', 'Ouled Rahmoune', 'Ain Smara']],
            ]],
            ['name' => 'Annaba', 'code' => '23', 'dairas' => [
                ['name' => 'Annaba', 'communes' => ['Annaba', 'Seraïdi']],
                ['name' => 'El Hadjar', 'communes' => ['El Hadjar', 'Sidi Amar']],
            ]],
        ];

        foreach ($wilayas as $wData) {
            $wilaya = Region::create([
                'name' => $wData['name'],
                'type' => 'wilaya',
                'parent_id' => $algeria->id,
                'depth' => 1,
                'code' => $wData['code']
            ]);
            foreach ($wData['dairas'] as $dData) {
                $daira = Region::create([
                    'name' => $dData['name'],
                    'type' => 'daira',
                    'parent_id' => $wilaya->id,
                    'depth' => 2
                ]);
                foreach ($dData['communes'] as $cName) {
                    Region::create([
                        'name' => $cName,
                        'type' => 'commune',
                        'parent_id' => $daira->id,
                        'depth' => 3
                    ]);
                }
            }
        }

        // --- FRANCE ---
        $france = Region::create(['name' => 'France', 'type' => 'country', 'depth' => 0, 'code' => 'FR']);

        $regionsFr = [
            ['name' => 'Île-de-France', 'depts' => [
                ['name' => 'Paris', 'code' => '75', 'communes' => ['Paris 1er', 'Paris 8e', 'Paris 16e']],
                ['name' => 'Hauts-de-Seine', 'code' => '92', 'communes' => ['Nanterre', 'Boulogne-Billancourt', 'Courbevoie']],
            ]],
            ['name' => 'Auvergne-Rhône-Alpes', 'depts' => [
                ['name' => 'Rhône', 'code' => '69', 'communes' => ['Lyon', 'Villeurbanne', 'Vénissieux']],
                ['name' => 'Isère', 'code' => '38', 'communes' => ['Grenoble', 'Vienne', 'Bourgoin-Jallieu']],
            ]],
            ['name' => 'Provence-Alpes-Côte d\'Azur', 'depts' => [
                ['name' => 'Bouches-du-Rhône', 'code' => '13', 'communes' => ['Marseille', 'Aix-en-Provence', 'Arles']],
                ['name' => 'Alpes-Maritimes', 'code' => '06', 'communes' => ['Nice', 'Cannes', 'Antibes']],
            ]],
            ['name' => 'Nouvelle-Aquitaine', 'depts' => [
                ['name' => 'Gironde', 'code' => '33', 'communes' => ['Bordeaux', 'Mérignac', 'Pessac']],
            ]],
            ['name' => 'Occitanie', 'depts' => [
                ['name' => 'Haute-Garonne', 'code' => '31', 'communes' => ['Toulouse', 'Colomiers', 'Tournefeuille']],
            ]],
            ['name' => 'Bretagne', 'depts' => [
                ['name' => 'Ille-et-Vilaine', 'code' => '35', 'communes' => ['Rennes', 'Saint-Malo']],
            ]],
            ['name' => 'Grand Est', 'depts' => [
                ['name' => 'Bas-Rhin', 'code' => '67', 'communes' => ['Strasbourg', 'Haguenau']],
            ]],
            ['name' => 'Hauts-de-France', 'depts' => [
                ['name' => 'Nord', 'code' => '59', 'communes' => ['Lille', 'Roubaix', 'Tourcoing']],
            ]],
            ['name' => 'Pays de la Loire', 'depts' => [
                ['name' => 'Loire-Atlantique', 'code' => '44', 'communes' => ['Nantes', 'Saint-Nazaire']],
            ]],
            ['name' => 'Normandie', 'depts' => [
                ['name' => 'Seine-Maritime', 'code' => '76', 'communes' => ['Rouen', 'Le Havre']],
            ]],
        ];

        foreach ($regionsFr as $rData) {
            $reg = Region::create(['name' => $rData['name'], 'type' => 'région', 'parent_id' => $france->id, 'depth' => 1]);
            foreach ($rData['depts'] as $dData) {
                $dept = Region::create(['name' => $dData['name'], 'type' => 'département', 'parent_id' => $reg->id, 'depth' => 2, 'code' => $dData['code']]);
                foreach ($dData['communes'] as $cName) {
                    Region::create(['name' => $cName, 'type' => 'commune', 'parent_id' => $dept->id, 'depth' => 3]);
                }
            }
        }

        // --- USA ---
        $usa = Region::create(['name' => 'USA', 'type' => 'country', 'depth' => 0, 'code' => 'US']);

        $states = [
            ['name' => 'California', 'code' => 'CA', 'cities' => ['Los Angeles', 'San Francisco', 'San Diego']],
            ['name' => 'New York', 'code' => 'NY', 'cities' => ['New York City', 'Buffalo', 'Rochester']],
            ['name' => 'Texas', 'code' => 'TX', 'cities' => ['Houston', 'Austin', 'Dallas', 'San Antonio']],
            ['name' => 'Florida', 'code' => 'FL', 'cities' => ['Miami', 'Orlando', 'Tampa', 'Jacksonville']],
            ['name' => 'Illinois', 'code' => 'IL', 'cities' => ['Chicago', 'Aurora', 'Naperville']],
            ['name' => 'Pennsylvania', 'code' => 'PA', 'cities' => ['Philadelphia', 'Pittsburgh', 'Allentown']],
            ['name' => 'Georgia', 'code' => 'GA', 'cities' => ['Atlanta', 'Augusta', 'Columbus']],
            ['name' => 'Washington', 'code' => 'WA', 'cities' => ['Seattle', 'Spokane', 'Tacoma']],
            ['name' => 'Massachusetts', 'code' => 'MA', 'cities' => ['Boston', 'Worcester', 'Springfield']],
            ['name' => 'Arizona', 'code' => 'AZ', 'cities' => ['Phoenix', 'Tucson', 'Mesa']],
        ];

        foreach ($states as $sData) {
            $state = Region::create(['name' => $sData['name'], 'type' => 'state', 'parent_id' => $usa->id, 'depth' => 1, 'code' => $sData['code']]);
            foreach ($sData['cities'] as $cityName) {
                Region::create(['name' => $cityName, 'type' => 'city', 'parent_id' => $state->id, 'depth' => 2]);
            }
        }
    }
}

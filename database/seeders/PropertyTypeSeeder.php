<?php

namespace Database\Seeders;

use App\Models\PropertyType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['title' => 'appartement', 'order' => 1],
            ['title' => 'villa', 'order' => 2],
            ['title' => 'terrain', 'order' => 3],
            ['title' => 'maison', 'order' => 4],
        ];

        foreach ($types as $type) {
            PropertyType::create($type);
        }
    }
}

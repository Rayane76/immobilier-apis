<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = [
            [
                'title' => 'nombre de pièces',
                'type' => 'integer',
                'min_value' => 1,
                'is_filterable' => true,
                'property_title_label' => 'pièces'
            ],
            [
                'title' => 'surface',
                'type' => 'decimal',
                'min_value' => 10,
                'is_filterable' => true,
                'property_title_label' => 'm²'
            ],
            [
                'title' => 'Parking sous sol',
                'type' => 'boolean',
                'is_filterable' => false,
            ],
            [
                'title' => 'Etage',
                'type' => 'integer',
                'min_value' => 0,
                'is_filterable' => false,
            ],
            [
                'title' => 'nombre des étages',
                'type' => 'integer',
                'min_value' => 1,
                'is_filterable' => false,
                'property_title_label' => 'étages'
            ],
            [
                'title' => 'type de terrain',
                'type' => 'string',
                'options' => ['agricole', 'constructible', 'industriel'],
                'is_filterable' => false,
            ],
            [
                'title' => 'Jardin',
                'type' => 'boolean',
                'is_filterable' => false,
            ],
        ];

        foreach ($attributes as $attribute) {
            Attribute::create($attribute);
        }
    }
}

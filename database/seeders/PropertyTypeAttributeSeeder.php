<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\PropertyType;
use App\Models\PropertyTypeAttribute;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertyTypeAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get types
        $appartement = PropertyType::where('title', 'appartement')->first();
        $villa = PropertyType::where('title', 'villa')->first();
        $terrain = PropertyType::where('title', 'terrain')->first();
        $maison = PropertyType::where('title', 'maison')->first();

        // Get attributes
        $nbPieces = Attribute::where('title', 'nombre de pièces')->first();
        $surface = Attribute::where('title', 'surface')->first();
        $parking = Attribute::where('title', 'Parking sous sol')->first();
        $etage = Attribute::where('title', 'Etage')->first();
        $nbEtages = Attribute::where('title', 'nombre des étages')->first();
        $typeTerrain = Attribute::where('title', 'type de terrain')->first();
        $jardin = Attribute::where('title', 'Jardin')->first();

        // link appartement with surface, nombre de pieces, Etage, Parking sous sol
        // make them required except parking sous sol and make nombre de pieces as is_used_for title
        $this->link($appartement->id, $surface->id, true, 1);
        $this->link($appartement->id, $nbPieces->id, true, 2, true);
        $this->link($appartement->id, $etage->id, true, 3);
        $this->link($appartement->id, $parking->id, false, 4);

        // link villa with nombre de pieces, jardin, surface, nombre des etages
        // make surface and nombre des etages required and make nombre des etages as is_used_for_title
        $this->link($villa->id, $nbPieces->id, false, 1);
        $this->link($villa->id, $jardin->id, false, 2);
        $this->link($villa->id, $surface->id, true, 3);
        $this->link($villa->id, $nbEtages->id, true, 4, true);

        // link terrain with surface, type de terrain
        // make them required and make surface as is_used_for_title
        $this->link($terrain->id, $surface->id, true, 1, true);
        $this->link($terrain->id, $typeTerrain->id, true, 2);

        // link maison with nombre de pieces, surface, jardin
        // make surface and nombre de pieces required and make nombre de pieces as is_used_for_title
        $this->link($maison->id, $nbPieces->id, true, 1, true);
        $this->link($maison->id, $surface->id, true, 2);
        $this->link($maison->id, $jardin->id, false, 3);
    }

    private function link($typeId, $attrId, $required, $order, $isUsedForTitle = false)
    {
        PropertyTypeAttribute::create([
            'property_type_id' => $typeId,
            'attribute_id' => $attrId,
            'is_required' => $required,
            'order' => $order,
            'is_used_for_title' => $isUsedForTitle,
        ]);
    }
}

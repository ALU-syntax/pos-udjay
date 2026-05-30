<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RawMaterialCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $categories = [
            [
                'name' => 'Bahan Kering & Staple',
                'notes' => 'Bahan pokok kering seperti beras, gula, tepung, dan bahan kering utama.',
            ],
            [
                'name' => 'Bumbu, Rempah & Seasoning',
                'notes' => 'Bumbu, rempah, seasoning, dan bahan penambah rasa.',
            ],
            [
                'name' => 'Sauce, Condiment & Selai',
                'notes' => 'Sauce, dressing, condiment, selai, jam, dan topping sejenis.',
            ],
            [
                'name' => 'Coffee, Tea & Beverage',
                'notes' => 'Kopi, teh, syrup, powder minuman, juice, dan bahan beverage.',
            ],
            [
                'name' => 'Dairy & Cream',
                'notes' => 'Susu, cream, keju, butter, dan produk dairy.',
            ],
            [
                'name' => 'Sayur & Buah',
                'notes' => 'Sayuran, buah, herbs segar, dan garnish segar.',
            ],
            [
                'name' => 'Protein, Meat & Seafood',
                'notes' => 'Daging, ayam, ikan, seafood, telur, dan protein utama.',
            ],
            [
                'name' => 'Bakery & Pastry',
                'notes' => 'Produk bakery, pastry, cake, roti, dan dessert.',
            ],
            [
                'name' => 'Frozen & Ready-to-Cook',
                'notes' => 'Produk frozen atau siap masak.',
            ],
            [
                'name' => 'Packaging & Takeaway',
                'notes' => 'Kemasan, cup, box, plastik, paper bag, sedotan, dan perlengkapan takeaway.',
            ],
            [
                'name' => 'Cleaning & Sanitation',
                'notes' => 'Bahan kebersihan, sanitasi, tissue, trashbag, sabun, dan alat kebersihan.',
            ],
            [
                'name' => 'Perlengkapan Operasional',
                'notes' => 'Perlengkapan operasional non-food/non-packaging.',
            ],
            [
                'name' => 'Gas & Utility',
                'notes' => 'Gas, utility, dan kebutuhan operasional energi.',
            ],
        ];

        foreach ($categories as $category) {
            $exists = DB::table('raw_material_categories')
                ->where('name', $category['name'])
                ->exists();

            $values = [
                'is_active' => true,
                'notes' => $category['notes'],
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if (! $exists) {
                $values['created_at'] = $now;
            }

            DB::table('raw_material_categories')->updateOrInsert(
                ['name' => $category['name']],
                $values
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RawMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->call(RawMaterialCategorySeeder::class);

            $requiredSatuans = [
                [
                    'name' => 'Pieces',
                    'symbol' => 'pcs',
                ],
                [
                    'name' => 'Kilogram',
                    'symbol' => 'kg',
                ],
                [
                    'name' => 'Pack',
                    'symbol' => 'pack',
                ],
                [
                    'name' => 'Botol',
                    'symbol' => 'botol',
                ],
                [
                    'name' => 'Kaleng',
                    'symbol' => 'kaleng',
                ],
                [
                    'name' => 'Jerigen',
                    'symbol' => 'jerigen',
                ],
                [
                    'name' => 'Dus',
                    'symbol' => 'dus',
                ],
                [
                    'name' => 'Sak',
                    'symbol' => 'sak',
                ],
                [
                    'name' => 'Roll',
                    'symbol' => 'roll',
                ],
                [
                    'name' => 'Tabung',
                    'symbol' => 'tabung',
                ],
                [
                    'name' => 'Loyang',
                    'symbol' => 'loyang',
                ],
                [
                    'name' => 'Whole',
                    'symbol' => 'whole',
                ],
            ];

            foreach ($requiredSatuans as $satuan) {
                $this->upsertSatuan($satuan['name'], $satuan['symbol']);
            }

            foreach (['Dry', 'Chilled', 'Frozen', 'Other'] as $storageType) {
                $this->upsertStorageType($storageType);
            }

            $categoryIds = DB::table('raw_material_categories')
                ->pluck('id', 'name')
                ->toArray();

            $unitIds = DB::table('satuans')
                ->pluck('id', 'name')
                ->toArray();

            $storageTypeIds = DB::table('raw_storage_types')
                ->select('id', 'name')
                ->get()
                ->mapWithKeys(fn ($row) => [strtolower($row->name) => $row->id])
                ->toArray();

            $items = [
                [
                    'code' => '8991389247013',
                    'name' => 'Amplop',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001567',
                    'name' => 'Bawang Bombay',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001574',
                    'name' => 'Bawang Goreng',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000843',
                    'name' => 'Beans Filter',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000364',
                    'name' => 'Beans Filter (Spesial)',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000829',
                    'name' => 'Beans Modern',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001604',
                    'name' => 'Beras',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Sak',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000904',
                    'name' => 'Biji Wijen',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8994286130587',
                    'name' => 'Black Tea',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000935',
                    'name' => 'Blushing Berry Tea',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001451',
                    'name' => 'Botolan Series',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001468',
                    'name' => 'Box Takeaway (besar)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002201',
                    'name' => 'Box Takeaway (Kecil)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '300177',
                    'name' => 'Caramel Sauce',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000928',
                    'name' => 'Choco Cookies Tea',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000997',
                    'name' => 'Chocolate Powder',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000959',
                    'name' => 'Cinnamon Powder',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000720',
                    'name' => 'syrup cinnamon',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001543',
                    'name' => 'Clink Pembersih Kaca',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001161',
                    'name' => 'Cookies',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000782',
                    'name' => 'Cuka',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001413',
                    'name' => 'Cup Takeaway Dingin',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001420',
                    'name' => 'Cup Takeaway Panas',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8612535141',
                    'name' => 'Denali Butterscotch Syrup',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Botol',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '8691123450609',
                    'name' => 'FO Caramel syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8612535112',
                    'name' => 'Denali Cranberry Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8612535108',
                    'name' => 'Denali Lychee Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8612535126',
                    'name' => 'Denali Mojito Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8612535109',
                    'name' => 'Denali Peach Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8612535119',
                    'name' => 'Denali Ron Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8612535101',
                    'name' => 'Denali Vanilla Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000867',
                    'name' => 'Dry Lemon',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000881',
                    'name' => 'Dry Sunkist',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000973',
                    'name' => 'Earl Grey',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000812',
                    'name' => 'Fizzy Summer Tea',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000775',
                    'name' => 'Garam',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3180290056899',
                    'name' => 'Giffard Bitter Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8612535129',
                    'name' => 'Denali Green Apple Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '(90)MD251113021900(91)250827',
                    'name' => 'Gula Aren',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000751',
                    'name' => 'Gula Halus',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000768',
                    'name' => 'Gula Pasir',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8997240690004',
                    'name' => 'Handglove',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001000',
                    'name' => 'Beans classic',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001659',
                    'name' => 'Jahe',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000000669',
                    'name' => 'Kacang Tanah',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '6922130105117',
                    'name' => 'Kaldu Jamur/Totole',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8886020001140',
                    'name' => 'Kamper',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001550',
                    'name' => 'Karbol',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '78895128789',
                    'name' => 'Kecap Asin',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '711844110519',
                    'name' => 'Kecap Manis',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001475',
                    'name' => 'Kertas Kentang',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001482',
                    'name' => 'Kertas Pastry',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8994023000111',
                    'name' => 'Keju Meg',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001192',
                    'name' => 'Kertas Thermal (BAR)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001208',
                    'name' => 'Kertas Thermal (KITCHEN)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8997206772195',
                    'name' => 'Kewpie Caesar',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000799',
                    'name' => 'Lada Hitam',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000805',
                    'name' => 'Lada Putih',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001215',
                    'name' => 'Lilin',
                    'category' => 'Perlengkapan Operasional',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '6949774273293',
                    'name' => 'Lychee Buah',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Kaleng',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '8997012032018',
                    'name' => 'Madu',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002195',
                    'name' => 'Matcha Signature',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001680',
                    'name' => 'Mentega',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000000676',
                    'name' => 'Minyak Goreng',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8997034020017',
                    'name' => 'Minyak Wijen',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000737',
                    'name' => 'Nori Bubuk',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8997240600010',
                    'name' => 'Oat Milk',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8004275001313',
                    'name' => 'Olive Oil',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001222',
                    'name' => 'Paper Bag Pastry',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001246',
                    'name' => 'Paper Filter',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000744',
                    'name' => 'Paprika Powder',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000683',
                    'name' => 'Parsley Bubuk',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001406',
                    'name' => 'Pengharum Ruangan (Botol)',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001390',
                    'name' => 'Pengharum Ruangan (Gantung)',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001499',
                    'name' => 'Plastik Prapatan',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001253',
                    'name' => 'Plastik Takeaway (Kitchen)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001260',
                    'name' => 'Plastik Takeaway BAR (Besar)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001277',
                    'name' => 'Plastik Takeaway BAR (Kecil)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8993560033729',
                    'name' => 'Harpic',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '18999510785547',
                    'name' => 'Pristine',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Dus',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8612491119',
                    'name' => 'Red Velvet Powder',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001505',
                    'name' => 'Sabun Cuci Piring',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001512',
                    'name' => 'Sabun Cuci Tangan',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001529',
                    'name' => 'Sabun Detergent',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001536',
                    'name' => 'Sabun Lantai',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8991188943062',
                    'name' => 'Sasa',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '89686401028',
                    'name' => 'Sauce Bangkok',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8992845858927',
                    'name' => 'Sauce Keju',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '78895300048',
                    'name' => 'Sauce Tiram',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8997024460298',
                    'name' => 'Sauce Tomat',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Jerigen',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '8997024460243',
                    'name' => 'Sauce Cabai',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001284',
                    'name' => 'Sedotan Merah',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001291',
                    'name' => 'Sendok Takeaway',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8992696426481',
                    'name' => 'SKM',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001307',
                    'name' => 'Solatip',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '1120000001940',
                    'name' => 'Spons Busa',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '1120000001957',
                    'name' => 'Spons Kawat',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '1120000001964',
                    'name' => 'Gas Torch',
                    'category' => 'Gas & Utility',
                    'base_unit' => 'Botol',
                    'storage' => 'other',
                ],
                [
                    'code' => '1120000001971',
                    'name' => 'Sedotan Hot',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8997034000477',
                    'name' => 'Strawberry Jam',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8998888150967',
                    'name' => 'Syrup Sunkist',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8888900700006',
                    'name' => 'Sauce Bolognice',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8992845859467',
                    'name' => 'Sauce Mentai 500gram',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8992845859269',
                    'name' => 'Sauce Mentai 1kg',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000690',
                    'name' => 'Telur',
                    'category' => 'Protein, Meat & Seafood',
                    'base_unit' => 'Kilogram',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001321',
                    'name' => 'Tempat Saos Takeaway',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8714700521025',
                    'name' => 'Tepung Maizena',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000706',
                    'name' => 'Tepung Roti',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8992736990163',
                    'name' => 'Tepung Serbaguna',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8993093664605',
                    'name' => 'Tepung Tapioka',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000000713',
                    'name' => 'Terasi',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8993053435511',
                    'name' => 'Tissue Multifold',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '414000000227',
                    'name' => 'Napkin Uddjaya',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001437',
                    'name' => 'Tissue Roll',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001444',
                    'name' => 'Tissue Roll Minyak (Kitchen)',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8993053175073',
                    'name' => 'Tissue Wajah',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8992761111670',
                    'name' => 'Tonic Water',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001338',
                    'name' => 'Trashbag Sampah (Besar)',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001345',
                    'name' => 'Trashbag Sampah (Kecil)',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001352',
                    'name' => 'Trashbag Toilet',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001369',
                    'name' => 'Tusukan Garnish',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001048',
                    'name' => 'UHT',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Dus',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001376',
                    'name' => 'Wrapping Bar',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Roll',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002188',
                    'name' => 'Wrapping Kitchen',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Roll',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001581',
                    'name' => 'Bawang Merah',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001598',
                    'name' => 'Bawang Putih',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '8999898970477',
                    'name' => 'Brookfarm Whipping Cream',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000000850',
                    'name' => 'Buah Lemon',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001611',
                    'name' => 'Cabe Jablay',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001628',
                    'name' => 'Cabe Keriting',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '49800224537',
                    'name' => 'Creamer',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pieces',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001758',
                    'name' => 'Daun Basil',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001635',
                    'name' => 'Daun Bawang',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001642',
                    'name' => 'Daun Jeruk',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001802',
                    'name' => 'Daun Mint',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001116',
                    'name' => 'Donuts Reguler',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pieces',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '8993351129938',
                    'name' => 'Fresh Milk',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pieces',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001789',
                    'name' => 'Gas 12',
                    'category' => 'Gas & Utility',
                    'base_unit' => 'Tabung',
                    'storage' => 'other',
                ],
                [
                    'code' => '2120000001666',
                    'name' => 'Jeruk Limo',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001017',
                    'name' => 'Juice Apple',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001024',
                    'name' => 'Juice Lemon',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Botol',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001031',
                    'name' => 'Juice Lime',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001765',
                    'name' => 'Paprika Hijau',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001796',
                    'name' => 'Parsley Daun',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '8997206774328',
                    'name' => 'Saos Nanban',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001697',
                    'name' => 'Selada',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001819',
                    'name' => 'Sereh',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001710',
                    'name' => 'Tomat',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001727',
                    'name' => 'Tomat Cherry',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '8992994110112',
                    'name' => 'Yakult',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001178',
                    'name' => 'Brownies',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000012',
                    'name' => 'Almond Chocolate',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000005',
                    'name' => 'Blueberry Apple',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000043',
                    'name' => 'Butter Croissant',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pieces',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000029',
                    'name' => 'Choco Cinnamon',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000050',
                    'name' => 'Danish Cream Cheese',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pieces',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001147',
                    'name' => 'Cheese Cake Matcha',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Loyang',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001154',
                    'name' => 'Cheese Cake Original',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Loyang',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001734',
                    'name' => 'Kol Ungu',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001185',
                    'name' => 'Makaroni panggang',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001130',
                    'name' => 'Strawberry Cheese Cake',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Loyang',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001741',
                    'name' => 'Wortel',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001826',
                    'name' => 'Daging Sapi Slice',
                    'category' => 'Protein, Meat & Seafood',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '8999898275015',
                    'name' => 'Eskrim',
                    'category' => 'Frozen & Ready-to-Cook',
                    'base_unit' => 'Jerigen',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2120000001833',
                    'name' => 'Ikan Dori',
                    'category' => 'Protein, Meat & Seafood',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '8993492101022',
                    'name' => 'Jagung Manis',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001932',
                    'name' => 'Kentang',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2120000001840',
                    'name' => 'Kulit Pangsit',
                    'category' => 'Frozen & Ready-to-Cook',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2120000001857',
                    'name' => 'Kulit Tortilla',
                    'category' => 'Frozen & Ready-to-Cook',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2120000001925',
                    'name' => 'Paha Ayam Fillet',
                    'category' => 'Protein, Meat & Seafood',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2120000001864',
                    'name' => 'Potato Wedges',
                    'category' => 'Frozen & Ready-to-Cook',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2120000001871',
                    'name' => 'Risol',
                    'category' => 'Frozen & Ready-to-Cook',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2120000001888',
                    'name' => 'Roti Tawar',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2120000001895',
                    'name' => 'Saikoro',
                    'category' => 'Protein, Meat & Seafood',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2120000001901',
                    'name' => 'Udang',
                    'category' => 'Protein, Meat & Seafood',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2120000001918',
                    'name' => 'Daging Giling',
                    'category' => 'Protein, Meat & Seafood',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '4140000002096',
                    'name' => 'Pengharum Ruangan (Toilet)',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002102',
                    'name' => 'Tray Cup',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002119',
                    'name' => 'Chocolate Sauce',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002171',
                    'name' => 'Churros',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '5010652998476',
                    'name' => 'Millac Gold',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pieces',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '4140000002256',
                    'name' => 'Gula Nira',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002263',
                    'name' => 'Solatip Uddjaya',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002287',
                    'name' => 'Kertas cup takeaway',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002294',
                    'name' => 'Tas Spunbond Uddjaya',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002300',
                    'name' => 'Plastik Polos',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002317',
                    'name' => 'Plastik Prapatan Besar',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002324',
                    'name' => 'Ziplock Es Batu',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002331',
                    'name' => 'Ziplock Creamcheese Botolan',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4140000002348',
                    'name' => 'Ziplock Creamcheese',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '300085',
                    'name' => 'Selai Peanut Butter',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '8995952001033',
                    'name' => 'Selai Srikaya',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '9300698001473',
                    'name' => 'Selai Nutella',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2220000000529',
                    'name' => 'Daging Giling 1kg',
                    'category' => 'Protein, Meat & Seafood',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2220000000536',
                    'name' => 'Sosis Solo',
                    'category' => 'Frozen & Ready-to-Cook',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '2220000000543',
                    'name' => 'Tahu Walik',
                    'category' => 'Frozen & Ready-to-Cook',
                    'base_unit' => 'Pack',
                    'storage' => 'frozen',
                ],
                [
                    'code' => '3330000000388',
                    'name' => 'Nona Manis',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000395',
                    'name' => 'Bolu Pisang',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8991001301031',
                    'name' => 'Ceres',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '2220000000550',
                    'name' => 'Mayonaise Kewpie',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8997206774311',
                    'name' => 'Tartar Kewpie',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8992845858866',
                    'name' => 'Sauce keju prima',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3052910056339',
                    'name' => 'Monin Markisa syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8998888150455',
                    'name' => 'sunquik lemon',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Botol',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '2220000000581',
                    'name' => 'coldbrew',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Jerigen',
                    'storage' => 'dry',
                ],
                [
                    'code' => '8997220504994',
                    'name' => 'Creamer Multibev',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3052911267437',
                    'name' => 'Monin maple syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000512',
                    'name' => 'Handglove latex',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000418',
                    'name' => 'Beans Kopi Susu',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Kilogram',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000529',
                    'name' => 'Gelang Kohit Markisa',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000536',
                    'name' => 'Gelang Kohit apple',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000543',
                    'name' => 'Gelang Kohit lychee',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000550',
                    'name' => 'Gelang Kohit Lemon',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000567',
                    'name' => 'Gelang Kohit Sunkist',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000425',
                    'name' => 'Beans Filter (Exotic)',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000432',
                    'name' => 'Davinci Caramel Sauce',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000449',
                    'name' => 'Cashew Milk',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000456',
                    'name' => 'Drip Pistachio Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000463',
                    'name' => 'Nutmeg (Pala)',
                    'category' => 'Bumbu, Rempah & Seasoning',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000470',
                    'name' => 'Drip Yuzu Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000487',
                    'name' => 'Strawberry Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000494',
                    'name' => 'Brownies Cheesecake',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Loyang',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000500',
                    'name' => 'Bacang',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000517',
                    'name' => 'Romaine lettuce',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000524',
                    'name' => 'Smoked Beef',
                    'category' => 'Protein, Meat & Seafood',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000531',
                    'name' => 'Butter ancor',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000548',
                    'name' => 'BBQ Sauce',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000579',
                    'name' => 'Red Cheddar',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000593',
                    'name' => 'Roti Sourdough',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Whole',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000609',
                    'name' => 'Richotta Cheese',
                    'category' => 'Dairy & Cream',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000616',
                    'name' => 'Strawberry Buah',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000623',
                    'name' => 'Blueberry buah',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000630',
                    'name' => 'Ubi Cilembu',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Kilogram',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '3330000000654',
                    'name' => 'Paperbag Budiman',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000661',
                    'name' => 'Kertas Kentang Budiman',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000685',
                    'name' => 'Curry Sauce',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000692',
                    'name' => 'Trashbag Knockbox',
                    'category' => 'Cleaning & Sanitation',
                    'base_unit' => 'Pieces',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000708',
                    'name' => 'Fo Vanilla syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000715',
                    'name' => 'Plastik Takeaway single (Budiman)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000722',
                    'name' => 'Plastik Takeaway double (Budiman)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000739',
                    'name' => 'Sedotan ice hitam (Budiman)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000746',
                    'name' => 'Plastik Takeaway Kitchen (Budiman)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000753',
                    'name' => 'Box Takeaway makanan (Budiman)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000760',
                    'name' => 'Matcha Powder chemistry',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000777',
                    'name' => 'Drip Orange Syrup',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000604',
                    'name' => 'Cup Ice Reguler (RSCM)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000611',
                    'name' => 'Cup Ice Large (RSCM)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000628',
                    'name' => 'lemon Tea Powder',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '4440000000635',
                    'name' => 'Peach Tea',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000642',
                    'name' => 'Lychee Black Tea',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000659',
                    'name' => 'Dark chocolate',
                    'category' => 'Coffee, Tea & Beverage',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000666',
                    'name' => 'Salted Caramel Sauce',
                    'category' => 'Sauce, Condiment & Selai',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000673',
                    'name' => 'Vanilla Essence',
                    'category' => 'Bahan Kering & Staple',
                    'base_unit' => 'Botol',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000680',
                    'name' => 'cup ice takeaway (budiman)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '4440000000697',
                    'name' => 'Cup Hot Takeaway (budiman)',
                    'category' => 'Packaging & Takeaway',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
                [
                    'code' => '3330000000784',
                    'name' => 'Buah Sunkist',
                    'category' => 'Sayur & Buah',
                    'base_unit' => 'Pack',
                    'storage' => 'chilled',
                ],
                [
                    'code' => '8993039112504',
                    'name' => 'regal',
                    'category' => 'Bakery & Pastry',
                    'base_unit' => 'Pack',
                    'storage' => 'dry',
                ],
            ];

            $now = now();

            foreach ($items as $item) {
                if (! isset($categoryIds[$item['category']])) {
                    throw new \RuntimeException("Raw material category not found: {$item['category']}");
                }

                if (! isset($unitIds[$item['base_unit']])) {
                    throw new \RuntimeException("Satuan not found: {$item['base_unit']}");
                }

                if (! isset($storageTypeIds[$item['storage']])) {
                    throw new \RuntimeException("Raw storage type not found: {$item['storage']}");
                }

                $exists = DB::table('raw_materials')
                    ->where('code', $item['code'])
                    ->exists();

                $values = [
                    'name' => $item['name'],
                    'raw_material_category_id' => $categoryIds[$item['category']],
                    'base_unit_id' => $unitIds[$item['base_unit']],
                    'storage_type_id' => $storageTypeIds[$item['storage']],
                    'is_active' => true,
                    'notes' => 'Imported from Master Data Bahan Baku Uddjaya.xlsx. Category/storage inferred from item name.',
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];

                if (! $exists) {
                    $values['created_at'] = $now;
                }

                DB::table('raw_materials')->updateOrInsert(
                    ['code' => $item['code']],
                    $values
                );
            }
        });
    }

    private function upsertSatuan(string $name, ?string $symbol = null): int
    {
        $now = now();

        $exists = DB::table('satuans')
            ->where('name', $name)
            ->exists();

        $values = [
            'is_active' => true,
            'updated_at' => $now,
        ];

        if (! $exists && Schema::hasColumn('satuans', 'created_at')) {
            $values['created_at'] = $now;
        }

        if (Schema::hasColumn('satuans', 'symbol')) {
            $values['symbol'] = $symbol;
        }

        DB::table('satuans')->updateOrInsert(
            ['name' => $name],
            $values
        );

        return (int) DB::table('satuans')
            ->where('name', $name)
            ->value('id');
    }

    private function upsertStorageType(string $name): int
    {
        if (! Schema::hasTable('raw_storage_types')) {
            throw new \RuntimeException('Table raw_storage_types does not exist. Please run its migration first.');
        }

        $now = now();

        $exists = DB::table('raw_storage_types')
            ->where('name', $name)
            ->exists();

        $values = [];

        if (Schema::hasColumn('raw_storage_types', 'is_active')) {
            $values['is_active'] = true;
        }

        if (Schema::hasColumn('raw_storage_types', 'notes')) {
            $values['notes'] = 'Auto-created by RawMaterialSeeder.';
        }

        if (Schema::hasColumn('raw_storage_types', 'updated_at')) {
            $values['updated_at'] = $now;
        }

        if (! $exists && Schema::hasColumn('raw_storage_types', 'created_at')) {
            $values['created_at'] = $now;
        }

        if (Schema::hasColumn('raw_storage_types', 'deleted_at')) {
            $values['deleted_at'] = null;
        }

        DB::table('raw_storage_types')->updateOrInsert(
            ['name' => $name],
            $values
        );

        return (int) DB::table('raw_storage_types')
            ->where('name', $name)
            ->value('id');
    }
}

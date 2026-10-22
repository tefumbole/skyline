<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedRestaurantAndBarMenu extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('products')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $restaurantId = $this->category('Restaurant', null, 'food', 'restaurant', $now);
        $barId = $this->category('Bar', null, 'drinks', 'bar', $now);
        $complementsId = $this->category('Complements', $restaurantId, 'food', null, $now);

        $bar = [
            'Whisky' => $this->bottles([
                ['Johnnie Walker Black Label', 45000],
                ['Johnnie Walker Green Label', 70000],
                ['Johnnie Walker Gold Label', 70000],
                ['Johnnie Walker 18 Years', 95000],
                ['Singleton', 40000],
                ['Glenfiddich 15 Years', 85000],
                ['Glenmorangie 10 Years', 60000],
                ["Grant's 12 Years", 35000],
                ['Chivas Regal 12 Years', 45000],
                ['J&B', 30000],
                ["Ballantine's Finest", 35000],
                ['Monkey Shoulder', 50000],
                ['Gentleman Jack', 60000],
                ['Jameson', 35000],
            ]),
            'Cognac' => $this->bottles([
                ['Hennessy VS', 60000],
                ['Hennessy Cognac', 70000],
                ['Rémy Martin 1738', 90000],
                ['Rémy Martin VSOP', 90000],
                ['Martell', 50000],
                ['Martell Blue Swift', 80000],
            ]),
            'Gin, Vodka and Spirits' => $this->bottles([
                ['Absolut Vodka', 30000],
                ['Ciroc Blue', 45000],
                ['Bombay Dry Gin', 30000],
                ["Gordon's Gin", 30000],
                ['Martini', 25000],
                ['Campari', 25000],
            ]),
            'White Wines' => [
                ['Carillonade White', 15000],
                ['Carillonade Sauvignon', 15000],
                ['Fiorelli Blanco', 15000],
                ['Louis Eschenauer White', 15000],
                ['Bordeaux White', 15000],
                ['Mont Bazillac', 15000],
            ],
            'Sweet Wines' => [
                ['Four Cousins Sweet Red', 15000],
                ['Four Cousins Sweet White', 15000],
                ['Four Cousins Sweet Rose', 15000],
            ],
            'Red Wines' => [
                ['Sand Stone', 15000],
                ['Louis Eschenauer Red', 15000],
                ['Bordeaux Red', 15000],
                ['Carillonade Merlot', 15000],
                ['Carillonade Bordeaux', 15000],
                ['19 Crimes', 25000],
                ['Apothic Red', 25000],
                ['Cabernet Sauvignon', 15000],
            ],
            'Premium Red Wines' => [
                ['Ecocentric', 30000],
                ['Bornac Haut Medoc', 30000],
                ["Chateaux D'arcin Haut Medoc", 35000],
                ['Bolota Dorada', 35000],
                ['Giscours', 35000],
                ['Chateau Ferrande Red', 35000],
                ['Appassimento', 35000],
                ['Campo Viejo Rioja', 35000],
                ['Bollato', 40000],
                ['Cooper and Thief', 45000],
                ['Baron de Brane', 50000],
                ['Chateaux Haut-Badge', 70000],
            ],
            'Sparkling Wines' => [
                ['Martini Asti Moscato', 25000],
                ['Bella Bolle Moscato', 25000],
                ['Ruinart Blancs de Blancs', 100000],
            ],
            'Aperitifs' => [
                ['Martini Bianco Glass', 4000, 'Glass'],
                ['Martini Bianco Bottle', 20000, 'Bottle'],
                ['Martini Rosso Glass', 4000, 'Glass'],
                ['Martini Rosso Bottle', 20000, 'Bottle'],
                ['Campari Glass', 4000, 'Glass'],
                ['Campari Bottle', 20000, 'Bottle'],
                ['Ricard Glass', 4000, 'Glass'],
                ['Ricard Bottle', 20000, 'Bottle'],
                ['Pastis Glass', 4000, 'Glass'],
                ['Pastis Bottle', 20000, 'Bottle'],
                ['Vermouth Glass', 4000, 'Glass'],
                ['Vermouth Bottle', 20000, 'Bottle'],
            ],
            'Cocktails' => $this->priced([
                'Blue Paradise', 'Melone Paradise', 'Twilight Tide', 'Mojito', 'Pineapple Kiss',
                'Neon Dream', 'Sexy Mango Margarita', 'Sex on the Beach', 'Negroni', 'Tom Collins',
                'Martini Cocktail', 'Cuba Libre', 'Long Island Tea', 'Pina Colada', 'Salty Dog',
                'Tequila Sunrise', 'Blue Lagoon', 'Rainbow', 'Green Screwdriver', 'Mai Tai',
            ], 5000),
            'Mocktails' => $this->priced([
                'Virgin Mojito', 'Virgin Pina Colada', 'Melone Paradise Mocktail', 'Sunrise',
                'Pineapple Lemonade', 'Grape Galaxy',
            ], 4000),
            'Natural Juices' => [
                ['Pineapple Juice 500ml', 2000, '500ml'],
                ['Pineapple Juice 1L', 5000, '1L'],
                ['Orange Juice 500ml', 2000, '500ml'],
                ['Orange Juice 1L', 5000, '1L'],
                ['Watermelon Juice 500ml', 2000, '500ml'],
                ['Watermelon Juice 1L', 5000, '1L'],
                ['Carrot Juice 500ml', 2000, '500ml'],
                ['Carrot Juice 1L', 5000, '1L'],
                ['Fruit Punch 500ml', 2000, '500ml'],
                ['Fruit Punch 1L', 5000, '1L'],
            ],
            'Smoothies' => $this->priced([
                'Green Detox', 'Mango Smoothie', 'Banana Smoothie', 'Chocolate Banana',
                'Pineapple Banana', 'Watermelon Smoothie',
            ], 3000),
            'Local Beers' => $this->priced([
                '33 Export', 'Castel 65', 'Mutzig', 'Beaufort', 'Beaufort Light',
                'Castel Milk Stout', 'Chill', 'Doppel',
            ], 2000),
            'Guinness' => [
                ['Guinness 33cl', 1500, '33cl'],
                ['Guinness 65cl', 2500, '65cl'],
                ['Guinness Smooth 33cl', 1500, '33cl'],
                ['Guinness Smooth 65cl', 2500, '65cl'],
            ],
            'International Beers' => [
                ['Heineken', 1500],
                ['Bavaria', 1500],
            ],
            'Malt and RTDs' => [
                ['Malta Guinness', 1500],
                ['Smirnoff Ice', 1500],
            ],
            'Energy Drinks' => [
                ['Booster Cola', 2000],
                ['Booster Gin', 2000],
            ],
            'Draft Beer' => [
                ['Draft Beer 50cl', 2000, '50cl'],
                ['Draft Beer 1L', 3000, '1L'],
            ],
            'Tango' => [
                ['Tango 50cl', 2000, '50cl'],
                ['Tango 1L', 3000, '1L'],
            ],
        ];

        foreach ($bar as $name => $items) {
            $categoryId = $this->category($name, $barId, 'drinks', null, $now);
            foreach ($items as $item) {
                $this->product($item[0], $item[1], $categoryId, isset($item[2]) ? $item[2] : null, $now);
            }
        }

        $dishes = [
            ['Prawns 7,000', 7000],
            ['Prawns 10,000', 10000],
            ['Snails', 6000],
            ['Chicken Salad', 6000],
            ['Garden Salad', 3000],
            ['Ekwang', 5000],
            ['Kwacoco and Mbanga Soup', 5000],
            ['Oxtail Sauce', 5000],
            ['Grilled Steak / Steak', 5000],
            ['Traditional Egusi Soup', 5000],
            ['Garden Egg Sauce', 5000],
            ['Coconut Rice and Grilled Turkey/Chicken', 5500],
            ['Smoked Pork (variants)', 5000],
            ['Dj Poulet / Poulet Dj 5,000', 5000],
            ['Dj Poulet / Poulet Dj 8,000', 8000],
            ['Dj Poulet / Poulet Dj 15,000', 15000],
            ['Dj Poulet / Poulet Dj 23,000', 23000],
            ['Eru', 5000],
            ['Liver Sauce', 5000],
            ['Sauteed Spiced Goat / Spiced Goat', 5000],
            ['Okongohbong', 5000],
            ['White Cream Sauce', 5500],
            ['Country Bitter Leaf and Khati Khati', 5000],
            ['Dynamite Shrimps', 5000],
            ['Nangtare', 5000],
            ['Stir Fry Beef with Rice', 5000],
            ['Steak and Baked Potatoes', 5000],
            ['Grilled Fish and Miondo 7,000', 7000],
            ['Grilled Fish and Miondo 10,000', 10000],
            ['Pizza Small', 10000],
            ['Pizza Large', 20000],
            ['Tortilla Wraps', 3000],
            ['Garden Eggs and Green', 5000],
        ];
        foreach ($dishes as $item) {
            $this->product($item[0], $item[1], $restaurantId, null, $now);
        }
        foreach (['White Steamed Rice', 'Garri', 'Green Bananas', 'Yams', 'Corn Fufu'] as $name) {
            $this->product($name, 0, $complementsId, 'Price was not printed on the menu', $now);
        }
    }

    public function down()
    {
        if (Schema::hasTable('products')) {
            $ids = DB::table('products')->where('code', 'like', 'MNU-%')->pluck('id');
            if ($ids->isNotEmpty() && Schema::hasTable('product_warehouse')) {
                DB::table('product_warehouse')->whereIn('product_id', $ids)->delete();
            }
            DB::table('products')->where('code', 'like', 'MNU-%')->delete();
        }
    }

    protected function bottles(array $items)
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [$item[0], $item[1], 'Bottle'];
        }

        return $rows;
    }

    protected function priced(array $names, $price)
    {
        $rows = [];
        foreach ($names as $name) {
            $rows[] = [$name, $price];
        }

        return $rows;
    }

    protected function category($name, $parentId, $department, $service, $now)
    {
        $query = DB::table('categories')->where('name', $name);
        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }
        $existing = $query->first();
        if ($existing) {
            return $existing->id;
        }
        $row = [
            'name' => $name,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('categories', 'parent_id')) {
            $row['parent_id'] = $parentId;
        }
        if (Schema::hasColumn('categories', 'department')) {
            $row['department'] = $department;
        }
        if ($service && Schema::hasColumn('categories', 'service')) {
            $row['service'] = $service;
        }
        if (Schema::hasColumn('categories', 'image')) {
            $row['image'] = null;
        }
        if (Schema::hasColumn('categories', 'cloud_tenant_id') && Schema::hasTable('cloud_tenants')) {
            $row['cloud_tenant_id'] = DB::table('cloud_tenants')->orderBy('id')->value('id');
        }

        return DB::table('categories')->insertGetId($row);
    }

    protected function product($name, $price, $categoryId, $details, $now)
    {
        $code = 'MNU-'.substr(strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $name)), 0, 24);
        if ($code === 'MNU-' || DB::table('products')->where('code', $code)->exists()) {
            $code = 'MNU-'.substr(md5($name), 0, 12);
        }
        if (DB::table('products')->where('code', $code)->exists()) {
            return;
        }
        $unitId = $this->unitId($now);
        if (! $unitId) {
            return;
        }
        $row = [
            'name' => $name,
            'code' => $code,
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'purchase_unit_id' => $unitId,
            'sale_unit_id' => $unitId,
            'cost' => (string) $price,
            'price' => (string) $price,
            'qty' => 200,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        foreach ([
            'product_details' => $details,
            'alert_quantity' => 5,
            'promotion' => 0,
            'featured' => 0,
            'is_variant' => 0,
            'is_batch' => 0,
            'is_diffPrice' => 0,
            'requires_quantity' => 1,
            'tax_method' => 1,
        ] as $column => $value) {
            if (Schema::hasColumn('products', $column)) {
                $row[$column] = $value;
            }
        }
        if (Schema::hasColumn('products', 'cloud_tenant_id') && Schema::hasTable('cloud_tenants')) {
            $row['cloud_tenant_id'] = DB::table('cloud_tenants')->orderBy('id')->value('id');
        }
        $id = DB::table('products')->insertGetId($row);
        $this->stock($id, $price, $now);
    }

    protected function unitId($now)
    {
        if (! Schema::hasTable('units')) {
            return null;
        }
        $id = DB::table('units')->orderBy('id')->value('id');
        if ($id) {
            return $id;
        }
        $row = [
            'unit_code' => 'pc',
            'unit_name' => 'Piece',
            'base_unit' => null,
            'operator' => '*',
            'operation_value' => 1,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        foreach (array_keys($row) as $column) {
            if (! Schema::hasColumn('units', $column)) {
                unset($row[$column]);
            }
        }

        return DB::table('units')->insertGetId($row);
    }

    protected function stock($productId, $price, $now)
    {
        if (! Schema::hasTable('product_warehouse') || ! Schema::hasTable('warehouses')) {
            return;
        }
        $warehouseId = DB::table('warehouses')->orderBy('id')->value('id');
        if (! $warehouseId) {
            return;
        }
        $row = [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'qty' => 200,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('product_warehouse', 'price')) {
            $row['price'] = $price;
        }
        DB::table('product_warehouse')->insert($row);
    }
}

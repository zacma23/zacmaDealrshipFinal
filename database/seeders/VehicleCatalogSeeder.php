<?php

namespace Database\Seeders;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VehicleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Toyota' => [
                ['name' => 'Corolla', 'body_type' => 'Sedan'],
                ['name' => 'Camry', 'body_type' => 'Sedan'],
                ['name' => 'Land Cruiser', 'body_type' => 'SUV'],
                ['name' => 'Land Cruiser Prado', 'body_type' => 'SUV'],
                ['name' => 'Fortuner', 'body_type' => 'SUV'],
                ['name' => 'Hilux', 'body_type' => 'Pickup'],
                ['name' => 'RAV4', 'body_type' => 'SUV'],
                ['name' => 'Vitz', 'body_type' => 'Hatchback'],
                ['name' => 'Yaris', 'body_type' => 'Hatchback'],
                ['name' => 'Rush', 'body_type' => 'SUV'],
                ['name' => 'Avanza', 'body_type' => 'Minivan'],
                ['name' => 'HiAce', 'body_type' => 'Van'],
                ['name' => 'Coaster', 'body_type' => 'Bus'],
                ['name' => 'Sequoia', 'body_type' => 'SUV'],
                ['name' => '4Runner', 'body_type' => 'SUV'],
            ],
            'Mercedes-Benz' => [
                ['name' => 'C-Class', 'body_type' => 'Sedan'],
                ['name' => 'E-Class', 'body_type' => 'Sedan'],
                ['name' => 'S-Class', 'body_type' => 'Sedan'],
                ['name' => 'GLE', 'body_type' => 'SUV'],
                ['name' => 'GLS', 'body_type' => 'SUV'],
                ['name' => 'GLC', 'body_type' => 'SUV'],
                ['name' => 'A-Class', 'body_type' => 'Hatchback'],
                ['name' => 'Sprinter', 'body_type' => 'Van'],
                ['name' => 'Vito', 'body_type' => 'Van'],
                ['name' => 'AMG GT', 'body_type' => 'Coupe'],
            ],
            'BMW' => [
                ['name' => '3 Series', 'body_type' => 'Sedan'],
                ['name' => '5 Series', 'body_type' => 'Sedan'],
                ['name' => '7 Series', 'body_type' => 'Sedan'],
                ['name' => 'X1', 'body_type' => 'SUV'],
                ['name' => 'X3', 'body_type' => 'SUV'],
                ['name' => 'X5', 'body_type' => 'SUV'],
                ['name' => 'X6', 'body_type' => 'SUV'],
                ['name' => '1 Series', 'body_type' => 'Hatchback'],
                ['name' => 'M3', 'body_type' => 'Sedan'],
                ['name' => 'M5', 'body_type' => 'Sedan'],
            ],
            'Hyundai' => [
                ['name' => 'Elantra', 'body_type' => 'Sedan'],
                ['name' => 'Sonata', 'body_type' => 'Sedan'],
                ['name' => 'Tucson', 'body_type' => 'SUV'],
                ['name' => 'Santa Fe', 'body_type' => 'SUV'],
                ['name' => 'Creta', 'body_type' => 'SUV'],
                ['name' => 'i10', 'body_type' => 'Hatchback'],
                ['name' => 'i20', 'body_type' => 'Hatchback'],
                ['name' => 'Accent', 'body_type' => 'Sedan'],
                ['name' => 'Palisade', 'body_type' => 'SUV'],
                ['name' => 'H-1', 'body_type' => 'Van'],
            ],
            'Kia' => [
                ['name' => 'Sportage', 'body_type' => 'SUV'],
                ['name' => 'Sorento', 'body_type' => 'SUV'],
                ['name' => 'Cerato', 'body_type' => 'Sedan'],
                ['name' => 'Picanto', 'body_type' => 'Hatchback'],
                ['name' => 'Rio', 'body_type' => 'Sedan'],
                ['name' => 'Telluride', 'body_type' => 'SUV'],
                ['name' => 'Carnival', 'body_type' => 'Minivan'],
                ['name' => 'Stinger', 'body_type' => 'Sedan'],
            ],
            'Nissan' => [
                ['name' => 'Navara', 'body_type' => 'Pickup'],
                ['name' => 'Patrol', 'body_type' => 'SUV'],
                ['name' => 'X-Trail', 'body_type' => 'SUV'],
                ['name' => 'Murano', 'body_type' => 'SUV'],
                ['name' => 'Altima', 'body_type' => 'Sedan'],
                ['name' => 'Sunny', 'body_type' => 'Sedan'],
                ['name' => 'Juke', 'body_type' => 'SUV'],
                ['name' => 'Urvan', 'body_type' => 'Van'],
                ['name' => 'Titan', 'body_type' => 'Pickup'],
            ],
            'Honda' => [
                ['name' => 'Accord', 'body_type' => 'Sedan'],
                ['name' => 'Civic', 'body_type' => 'Sedan'],
                ['name' => 'CR-V', 'body_type' => 'SUV'],
                ['name' => 'Pilot', 'body_type' => 'SUV'],
                ['name' => 'HR-V', 'body_type' => 'SUV'],
                ['name' => 'Jazz', 'body_type' => 'Hatchback'],
                ['name' => 'Odyssey', 'body_type' => 'Minivan'],
            ],
            'Ford' => [
                ['name' => 'Ranger', 'body_type' => 'Pickup'],
                ['name' => 'Everest', 'body_type' => 'SUV'],
                ['name' => 'Explorer', 'body_type' => 'SUV'],
                ['name' => 'F-150', 'body_type' => 'Pickup'],
                ['name' => 'Transit', 'body_type' => 'Van'],
                ['name' => 'Fusion', 'body_type' => 'Sedan'],
            ],
            'Audi' => [
                ['name' => 'A4', 'body_type' => 'Sedan'],
                ['name' => 'A6', 'body_type' => 'Sedan'],
                ['name' => 'A8', 'body_type' => 'Sedan'],
                ['name' => 'Q3', 'body_type' => 'SUV'],
                ['name' => 'Q5', 'body_type' => 'SUV'],
                ['name' => 'Q7', 'body_type' => 'SUV'],
                ['name' => 'Q8', 'body_type' => 'SUV'],
            ],
            'Volkswagen' => [
                ['name' => 'Golf', 'body_type' => 'Hatchback'],
                ['name' => 'Passat', 'body_type' => 'Sedan'],
                ['name' => 'Tiguan', 'body_type' => 'SUV'],
                ['name' => 'Touareg', 'body_type' => 'SUV'],
                ['name' => 'Polo', 'body_type' => 'Hatchback'],
                ['name' => 'Caddy', 'body_type' => 'Van'],
                ['name' => 'Transporter', 'body_type' => 'Van'],
            ],
            'Lexus' => [
                ['name' => 'LX570', 'body_type' => 'SUV'],
                ['name' => 'GX460', 'body_type' => 'SUV'],
                ['name' => 'RX350', 'body_type' => 'SUV'],
                ['name' => 'ES300h', 'body_type' => 'Sedan'],
                ['name' => 'LS500', 'body_type' => 'Sedan'],
                ['name' => 'NX300', 'body_type' => 'SUV'],
            ],
            'Land Rover' => [
                ['name' => 'Defender', 'body_type' => 'SUV'],
                ['name' => 'Discovery', 'body_type' => 'SUV'],
                ['name' => 'Range Rover', 'body_type' => 'SUV'],
                ['name' => 'Range Rover Sport', 'body_type' => 'SUV'],
                ['name' => 'Freelander', 'body_type' => 'SUV'],
            ],
            'Mitsubishi' => [
                ['name' => 'L200', 'body_type' => 'Pickup'],
                ['name' => 'Pajero', 'body_type' => 'SUV'],
                ['name' => 'Outlander', 'body_type' => 'SUV'],
                ['name' => 'ASX', 'body_type' => 'SUV'],
                ['name' => 'Eclipse Cross', 'body_type' => 'SUV'],
                ['name' => 'Rosa', 'body_type' => 'Bus'],
            ],
            'Isuzu' => [
                ['name' => 'D-Max', 'body_type' => 'Pickup'],
                ['name' => 'MU-X', 'body_type' => 'SUV'],
                ['name' => 'Forward', 'body_type' => 'Truck'],
                ['name' => 'FRR', 'body_type' => 'Truck'],
            ],
            'Mazda' => [
                ['name' => 'CX-5', 'body_type' => 'SUV'],
                ['name' => 'CX-9', 'body_type' => 'SUV'],
                ['name' => 'Mazda3', 'body_type' => 'Sedan'],
                ['name' => 'BT-50', 'body_type' => 'Pickup'],
            ],
            'Subaru' => [
                ['name' => 'Forester', 'body_type' => 'SUV'],
                ['name' => 'Outback', 'body_type' => 'Wagon'],
                ['name' => 'XV', 'body_type' => 'SUV'],
                ['name' => 'Impreza', 'body_type' => 'Sedan'],
            ],
            'Jeep' => [
                ['name' => 'Wrangler', 'body_type' => 'SUV'],
                ['name' => 'Grand Cherokee', 'body_type' => 'SUV'],
                ['name' => 'Cherokee', 'body_type' => 'SUV'],
                ['name' => 'Compass', 'body_type' => 'SUV'],
            ],
            'Chevrolet' => [
                ['name' => 'Tahoe', 'body_type' => 'SUV'],
                ['name' => 'Suburban', 'body_type' => 'SUV'],
                ['name' => 'Blazer', 'body_type' => 'SUV'],
                ['name' => 'Silverado', 'body_type' => 'Pickup'],
                ['name' => 'Cruze', 'body_type' => 'Sedan'],
            ],
            'Tesla' => [
                ['name' => 'Model 3', 'body_type' => 'Sedan'],
                ['name' => 'Model S', 'body_type' => 'Sedan'],
                ['name' => 'Model X', 'body_type' => 'SUV'],
                ['name' => 'Model Y', 'body_type' => 'SUV'],
            ],
            'Suzuki' => [
                ['name' => 'Swift', 'body_type' => 'Hatchback'],
                ['name' => 'Vitara', 'body_type' => 'SUV'],
                ['name' => 'Jimny', 'body_type' => 'SUV'],
                ['name' => 'Alto', 'body_type' => 'Hatchback'],
            ],
            'Volvo' => [
                ['name' => 'XC90', 'body_type' => 'SUV'],
                ['name' => 'XC60', 'body_type' => 'SUV'],
                ['name' => 'S90', 'body_type' => 'Sedan'],
                ['name' => 'FH', 'body_type' => 'Truck'],
            ],
            'Porsche' => [
                ['name' => 'Cayenne', 'body_type' => 'SUV'],
                ['name' => 'Macan', 'body_type' => 'SUV'],
                ['name' => '911', 'body_type' => 'Coupe'],
                ['name' => 'Panamera', 'body_type' => 'Sedan'],
            ],
            'BYD' => [
                ['name' => 'Han', 'body_type' => 'Sedan'],
                ['name' => 'Tang', 'body_type' => 'SUV'],
                ['name' => 'Song Plus', 'body_type' => 'SUV'],
                ['name' => 'Atto 3', 'body_type' => 'SUV'],
            ],
            'Geely' => [
                ['name' => 'Coolray', 'body_type' => 'SUV'],
                ['name' => 'Emgrand', 'body_type' => 'Sedan'],
                ['name' => 'Atlas Pro', 'body_type' => 'SUV'],
            ],
            'MG' => [
                ['name' => 'ZS', 'body_type' => 'SUV'],
                ['name' => 'HS', 'body_type' => 'SUV'],
                ['name' => 'RX5', 'body_type' => 'SUV'],
            ],
            'Peugeot' => [
                ['name' => '308', 'body_type' => 'Hatchback'],
                ['name' => '508', 'body_type' => 'Sedan'],
                ['name' => '3008', 'body_type' => 'SUV'],
                ['name' => 'Partner', 'body_type' => 'Van'],
            ],
            'Renault' => [
                ['name' => 'Duster', 'body_type' => 'SUV'],
                ['name' => 'Logan', 'body_type' => 'Sedan'],
                ['name' => 'Master', 'body_type' => 'Van'],
            ],
            'Fiat' => [
                ['name' => 'Doblo', 'body_type' => 'Van'],
                ['name' => 'Ducato', 'body_type' => 'Van'],
                ['name' => 'Fullback', 'body_type' => 'Pickup'],
            ],
        ];

        $sort = 0;
        foreach ($catalog as $brandName => $models) {
            // Skip if brand already exists
            $brand = VehicleBrand::firstOrCreate(
                ['name' => $brandName],
                [
                    'slug'       => Str::slug($brandName),
                    'is_active'  => true,
                    'sort_order' => $sort++,
                ]
            );

            foreach ($models as $modelData) {
                $slug = Str::slug($modelData['name']);
                if (!VehicleModel::where('brand_id', $brand->id)->where('slug', $slug)->exists()) {
                    VehicleModel::create([
                        'brand_id'  => $brand->id,
                        'name'      => $modelData['name'],
                        'slug'      => $slug,
                        'body_type' => $modelData['body_type'],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}

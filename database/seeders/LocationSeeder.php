<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates 16 locations across 9 Indian states with statutory configuration.
     */
    public function run(): void
    {
        $locations = [
            // Delhi - 2 locations
            [
                'name' => 'Delhi - Head Office',
                'address' => 'Plot No. 123, Business District',
                'city' => 'New Delhi',
                'state' => 'delhi',
                'pin_code' => '110001',
                'gis_lat' => 28.6139,
                'gis_lng' => 77.2090,
                'is_active' => true,
            ],
            [
                'name' => 'Delhi - South Branch',
                'address' => 'Plot No. 456, Tech Park',
                'city' => 'New Delhi',
                'state' => 'delhi',
                'pin_code' => '110016',
                'gis_lat' => 28.5244,
                'gis_lng' => 77.1855,
                'is_active' => true,
            ],

            // Haryana - 2 locations
            [
                'name' => 'Gurgaon - Main Office',
                'address' => 'Plot No. 789, Corporate Avenue',
                'city' => 'Gurgaon',
                'state' => 'haryana',
                'pin_code' => '122001',
                'gis_lat' => 28.4595,
                'gis_lng' => 77.0266,
                'is_active' => true,
            ],
            [
                'name' => 'Faridabad - Branch',
                'address' => 'Plot No. 321, Industrial Area',
                'city' => 'Faridabad',
                'state' => 'haryana',
                'pin_code' => '121001',
                'gis_lat' => 28.4089,
                'gis_lng' => 77.3178,
                'is_active' => true,
            ],

            // Maharashtra - 2 locations
            [
                'name' => 'Mumbai - Regional HQ',
                'address' => 'Plot No. 555, Business Bay',
                'city' => 'Mumbai',
                'state' => 'maharashtra',
                'pin_code' => '400001',
                'gis_lat' => 19.0760,
                'gis_lng' => 72.8777,
                'is_active' => true,
            ],
            [
                'name' => 'Pune - Branch',
                'address' => 'Plot No. 888, Tech Hub',
                'city' => 'Pune',
                'state' => 'maharashtra',
                'pin_code' => '411001',
                'gis_lat' => 18.5204,
                'gis_lng' => 73.8567,
                'is_active' => true,
            ],

            // Karnataka - 2 locations
            [
                'name' => 'Bangalore - South India HQ',
                'address' => 'Plot No. 999, IT Park',
                'city' => 'Bangalore',
                'state' => 'karnataka',
                'pin_code' => '560001',
                'gis_lat' => 12.9716,
                'gis_lng' => 77.5946,
                'is_active' => true,
            ],
            [
                'name' => 'Mysore - Branch',
                'address' => 'Plot No. 111, Commerce Zone',
                'city' => 'Mysore',
                'state' => 'karnataka',
                'pin_code' => '570001',
                'gis_lat' => 12.2958,
                'gis_lng' => 76.6394,
                'is_active' => true,
            ],

            // Uttar Pradesh - 2 locations
            [
                'name' => 'Noida - North India Branch',
                'address' => 'Plot No. 222, Business Park',
                'city' => 'Noida',
                'state' => 'uttar_pradesh',
                'pin_code' => '201301',
                'gis_lat' => 28.5355,
                'gis_lng' => 77.3910,
                'is_active' => true,
            ],
            [
                'name' => 'Lucknow - Branch',
                'address' => 'Plot No. 333, Commerce Center',
                'city' => 'Lucknow',
                'state' => 'uttar_pradesh',
                'pin_code' => '226001',
                'gis_lat' => 26.8467,
                'gis_lng' => 80.9462,
                'is_active' => true,
            ],

            // Gujarat - 1 location
            [
                'name' => 'Ahmedabad - Branch',
                'address' => 'Plot No. 444, Business District',
                'city' => 'Ahmedabad',
                'state' => 'gujarat',
                'pin_code' => '380001',
                'gis_lat' => 23.0225,
                'gis_lng' => 72.5714,
                'is_active' => true,
            ],

            // West Bengal - 1 location
            [
                'name' => 'Kolkata - East India Branch',
                'address' => 'Plot No. 555, Commerce Hub',
                'city' => 'Kolkata',
                'state' => 'west_bengal',
                'pin_code' => '700001',
                'gis_lat' => 22.5726,
                'gis_lng' => 88.3639,
                'is_active' => true,
            ],

            // Jharkhand - 1 location
            [
                'name' => 'Ranchi - Branch',
                'address' => 'Plot No. 666, Business Zone',
                'city' => 'Ranchi',
                'state' => 'jharkhand',
                'pin_code' => '834001',
                'gis_lat' => 23.3441,
                'gis_lng' => 85.3096,
                'is_active' => true,
            ],

            // Goa - 1 location
            [
                'name' => 'Panaji - Branch',
                'address' => 'Plot No. 777, Commerce Park',
                'city' => 'Panaji',
                'state' => 'goa',
                'pin_code' => '403001',
                'gis_lat' => 15.4909,
                'gis_lng' => 73.8278,
                'is_active' => true,
            ],
        ];

        foreach ($locations as $locationData) {
            Location::firstOrCreate(
                ['name' => $locationData['name']],
                $locationData
            );
        }
    }
}

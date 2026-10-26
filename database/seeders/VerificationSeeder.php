<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

class VerificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or retrieve the 'Verification' service
        $service = Service::firstOrCreate(
            ['name' => 'Verification'],
            [
                'description' => 'Identity Verification Services',
                'is_active' => true,
            ]
        );

        // 2. Define verification fields (BVN & NIN verification and slip variations)
        $fields = [
            // BVN Fields
            [
                'name' => 'Bvn verification',
                'code' => '600',
                'base_price' => 70.00,
            ],
            [
                'name' => 'standard slip',
                'code' => '601',
                'base_price' => 50.00,
            ],
            [
                'name' => 'preminum slip',
                'code' => '602',
                'base_price' => 100.00,
            ],
            [
                'name' => 'plastic slip',
                'code' => '603',
                'base_price' => 150.00,
            ],

            // NIN Demo Fields
            [
                'name' => 'Demo Verification',
                'code' => 'V100',
                'base_price' => 100.00,
            ],
            [
                'name' => 'Free Slip',
                'code' => 'V101',
                'base_price' => 0.00,
            ],
            [
                'name' => 'Regular Slip',
                'code' => 'V102',
                'base_price' => 100.00,
            ],
            [
                'name' => 'standard slip',
                'code' => '611',
                'base_price' => 100.00,
            ],
            [
                'name' => 'preminum slip',
                'code' => '612',
                'base_price' => 150.00,
            ],

            // NIN Phone Fields
            [
                'name' => 'Phone NIN Verification',
                'code' => 'V105',
                'base_price' => 100.00,
            ],

            // NIN Verification Fields
            [
                'name' => 'Verify NIN',
                'code' => '610',
                'base_price' => 80.00,
            ],
            [
                'name' => '1Vnin slip',
                'code' => '616',
                'base_price' => 100.00,
            ],
        ];

        // 3. User roles to generate default service prices
        $roles = ['personal', 'agent', 'partner', 'business', 'business_agent', 'regional_manager', 'coordinator', 'business_sales_manager', 'support', 'super_admin'];

        foreach ($fields as $fieldData) {
            // Create or update the ServiceField by code
            $field = ServiceField::updateOrCreate(
                ['field_code' => $fieldData['code']],
                [
                    'service_id' => $service->id,
                    'field_name' => $fieldData['name'],
                    'base_price' => $fieldData['base_price'],
                    'is_active' => true,
                ]
            );

            // Seed default prices for each role (equal to base price)
            foreach ($roles as $role) {
                ServicePrice::updateOrCreate(
                    [
                        'service_fields_id' => $field->id,
                        'user_type' => $role,
                        'user_id' => null,
                    ],
                    [
                        'service_id' => $service->id,
                        'price' => $fieldData['base_price'],
                        'commission' => 0.00,
                    ]
                );
            }
        }
    }
}

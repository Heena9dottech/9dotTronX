<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LevelPlan;

class LevelPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            ['name' => '200 TRX', 'price' => 200, 'level_number' => 1, 'description' => 'Entry level plan - 200 TRX'],
            ['name' => '400 TRX', 'price' => 400, 'level_number' => 2, 'description' => 'Level 2 plan - 400 TRX'],
            ['name' => '800 TRX', 'price' => 800, 'level_number' => 3, 'description' => 'Level 3 plan - 800 TRX'],
            ['name' => '1600 TRX', 'price' => 1600, 'level_number' => 4, 'description' => 'Level 4 plan - 1,600 TRX'],
            ['name' => '3200 TRX', 'price' => 3200, 'level_number' => 5, 'description' => 'Level 5 plan - 3,200 TRX'],
            ['name' => '6400 TRX', 'price' => 6400, 'level_number' => 6, 'description' => 'Level 6 plan - 6,400 TRX'],
            ['name' => '12800 TRX', 'price' => 12800, 'level_number' => 7, 'description' => 'Level 7 plan - 12,800 TRX'],
            ['name' => '25000 TRX', 'price' => 25000, 'level_number' => 8, 'description' => 'Level 8 plan - 25,000 TRX'],
            ['name' => '50000 TRX', 'price' => 50000, 'level_number' => 9, 'description' => 'Level 9 plan - 50,000 TRX'],
            ['name' => '100000 TRX', 'price' => 100000, 'level_number' => 10, 'description' => 'Level 10 plan - 100,000 TRX'],
            ['name' => '200000 TRX', 'price' => 200000, 'level_number' => 11, 'description' => 'Level 11 plan - 200,000 TRX'],
            ['name' => '400000 TRX', 'price' => 400000, 'level_number' => 12, 'description' => 'Level 12 plan - 400,000 TRX'],
            ['name' => '800000 TRX', 'price' => 800000, 'level_number' => 13, 'description' => 'Level 13 plan - 800,000 TRX'],
            ['name' => '1500000 TRX', 'price' => 1500000, 'level_number' => 14, 'description' => 'Level 14 plan - 1,500,000 TRX'],
            ['name' => '2000000 TRX', 'price' => 2000000, 'level_number' => 15, 'description' => 'Level 15 plan - 2,000,000 TRX'],
        ];

        foreach ($plans as $plan) {
            LevelPlan::create($plan);
        }

        $this->command->info('Level plans seeded successfully!');
    }
}

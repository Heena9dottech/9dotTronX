<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ReferralRelationship;
use App\Models\UserSlot;
use App\Models\IncomeDistribution;
use App\Models\LevelPlan;
use App\Services\IncomeService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class MLMUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure admin user exists
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin123'),
                'sponsor_id' => null,
                'tree_round_count' => 0
            ]
        );

        // Ensure level plans exist, if not create them
        if (LevelPlan::count() == 0) {
            $this->command->info('No level plans found, creating them...');
            $this->call(LevelPlanSeeder::class);
        }
        
        // Get level plans
        $levelPlans = LevelPlan::all()->keyBy('level_number');
        
        if ($levelPlans->isEmpty()) {
            $this->command->error('No level plans available!');
            return;
        }
        
        // Create 50 users with proper MLM structure
        $users = [];
        $referralRelationships = [];
        $userSlots = [];
        $incomeDistributions = [];

        // Create users with realistic data
        for ($i = 1; $i <= 50; $i++) {
            $sponsorId = $i > 1 ? rand(1, min($i - 1, 10)) : $admin->id; // First 10 users can sponsor others
            
            $user = User::firstOrCreate(
                ['username' => "user{$i}"],
                [
                    'name' => "User {$i}",
                    'email' => "user{$i}@example.com",
                    'password' => Hash::make('password123'),
                    'sponsor_id' => $sponsorId,
                    'tree_round_count' => 0
                ]
            );
            
            $users[] = $user;
        }

        // Create referral relationships and user slots for each user
        foreach ($users as $index => $user) {
            // Randomly assign 1-3 level plans per user
            $userLevelCount = rand(1, 3);
            $availableLevels = $levelPlans->keys()->toArray();
            $maxLevels = min($userLevelCount, count($availableLevels));
            
            if ($maxLevels > 0) {
                $selectedLevels = array_rand($availableLevels, $maxLevels);
                
                if (!is_array($selectedLevels)) {
                    $selectedLevels = [$selectedLevels];
                }
            } else {
                $selectedLevels = [1]; // Default to level 1 if no levels available
            }

            foreach ($selectedLevels as $levelNumber) {
                $levelPlan = $levelPlans->get($levelNumber);
                
                if (!$levelPlan) {
                    continue; // Skip if level plan not found
                }
                
                // Check if user already has a slot for this level
                $existingSlot = UserSlot::where('user_id', $user->id)
                    ->where('level_plans_id', $levelPlan->id)
                    ->first();
                
                if ($existingSlot) {
                    continue; // Skip if slot already exists
                }
                
                // Create user slot
                $userSlot = UserSlot::create([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'level_plans_id' => $levelPlan->id,
                    'referral_relationship_id' => null,
                    'tree_member_ids' => []
                ]);

                // Determine upline structure
                $uplineId = $user->sponsor_id;
                $upline1 = $uplineId;
                $upline2 = null;
                $upline3 = null;
                $upline4 = null;

                // Calculate upline chain
                if ($uplineId && $uplineId != $admin->id) {
                    $upline1User = User::find($uplineId);
                    if ($upline1User && $upline1User->sponsor_id) {
                        $upline2 = $upline1User->sponsor_id;
                        
                        $upline2User = User::find($upline2);
                        if ($upline2User && $upline2User->sponsor_id) {
                            $upline3 = $upline2User->sponsor_id;
                            
                            $upline3User = User::find($upline3);
                            if ($upline3User && $upline3User->sponsor_id) {
                                $upline4 = $upline3User->sponsor_id;
                            }
                        }
                    }
                }

                // Create referral relationship
                $referralRelationship = ReferralRelationship::create([
                    'user_id' => $user->id,
                    'user_username' => $user->username,
                    'sponsor_id' => $user->sponsor_id,
                    'sponsor_username' => $user->sponsor_id ? User::find($user->sponsor_id)->username : 'admin',
                    'upline_id' => $uplineId,
                    'upline_username' => $uplineId ? User::find($uplineId)->username : 'admin',
                    'upline1' => $upline1,
                    'upline2' => $upline2,
                    'upline3' => $upline3,
                    'upline4' => $upline4,
                    'position' => 'L', // Default position (L or R)
                    'tree_owner_id' => $admin->id, // Admin as tree owner
                    'tree_owner_username' => 'admin',
                    'tree_round' => 1,
                    'is_spillover_slot' => false,
                    'level_number' => $levelPlan->level_number,
                    'slot_price' => $levelPlan->price,
                    'level_id' => $levelPlan->id,
                    'user_slots_id' => $userSlot->id,
                    'main_upline_id' => $uplineId
                ]);

                // Update user slot with referral relationship ID
                $userSlot->update(['referral_relationship_id' => $referralRelationship->id]);

                // Create income distributions
                $this->createIncomeDistributions($referralRelationship, $levelPlan, $admin);
            }
        }

        // Update tree member IDs for all user slots
        $this->updateTreeMemberIds($users, $admin);

        $this->command->info('✅ MLM Users Seeder completed successfully!');
        $this->command->info("Created 50 users with proper MLM tree structure");
        $this->command->info("Created referral relationships, user slots, and income distributions");
    }

    /**
     * Create income distributions for a referral relationship
     */
    private function createIncomeDistributions($referralRelationship, $levelPlan, $admin)
    {
        $percentages = [
            'upline1' => 5.00,
            'upline2' => 10.00,
            'upline3' => 20.00,
            'upline4' => 25.00,
            'sponsor' => 40.00,
        ];

        $totalDistributed = 0;

        // Distribute to uplines
        for ($level = 1; $level <= 4; $level++) {
            $uplineColumn = "upline{$level}";
            $uplineId = $referralRelationship->$uplineColumn;

            if ($uplineId) {
                $percentage = $percentages["upline{$level}"];
                $amount = ($levelPlan->price * $percentage) / 100;

                IncomeDistribution::create([
                    'user_id' => $referralRelationship->user_id,
                    'level_plan_id' => $levelPlan->id,
                    'recipient_id' => $uplineId,
                    'level' => "upline{$level}",
                    'percentage' => $percentage,
                    'amount' => $amount,
                    'level_plan_price' => $levelPlan->price,
                    'description' => "Upline {$level} distribution for user {$referralRelationship->user_username}",
                    'status' => 'completed'
                ]);

                $totalDistributed += $amount;
            }
        }

        // Distribute to sponsor
        if ($referralRelationship->sponsor_id) {
            $sponsorPercentage = $percentages['sponsor'];
            $sponsorAmount = ($levelPlan->price * $sponsorPercentage) / 100;

            IncomeDistribution::create([
                'user_id' => $referralRelationship->user_id,
                'level_plan_id' => $levelPlan->id,
                'recipient_id' => $referralRelationship->sponsor_id,
                'level' => 'sponsor',
                'percentage' => $sponsorPercentage,
                'amount' => $sponsorAmount,
                'level_plan_price' => $levelPlan->price,
                'description' => "Sponsor distribution for user {$referralRelationship->user_username}",
                'status' => 'completed'
            ]);

            $totalDistributed += $sponsorAmount;
        }

        // Calculate remaining amount for admin
        $remainingAmount = $levelPlan->price - $totalDistributed;

        if ($remainingAmount > 0) {
            $adminPercentage = ($remainingAmount / $levelPlan->price) * 100;

            IncomeDistribution::create([
                'user_id' => $referralRelationship->user_id,
                'level_plan_id' => $levelPlan->id,
                'recipient_id' => null, // null for admin
                'level' => 'admin',
                'percentage' => $adminPercentage,
                'amount' => $remainingAmount,
                'level_plan_price' => $levelPlan->price,
                'description' => "Admin distribution for user {$referralRelationship->user_username} (remaining amount)",
                'status' => 'completed'
            ]);
        }
    }

    /**
     * Update tree member IDs for all user slots
     */
    private function updateTreeMemberIds($users, $admin)
    {
        foreach ($users as $user) {
            $userSlots = UserSlot::where('user_id', $user->id)->get();
            
            foreach ($userSlots as $userSlot) {
                $treeMembers = [];
                
                // Get all users in this user's downline for each level
                for ($level = 1; $level <= 4; $level++) {
                    $levelMembers = [];
                    
                    // Find users who have this user as upline at this level
                    $downlineUsers = ReferralRelationship::where("upline{$level}", $user->id)
                        ->with('user')
                        ->get();
                    
                    foreach ($downlineUsers as $downline) {
                        if ($downline->user && $downline->user->id != $user->id) {
                            $levelMembers[] = $downline->user->username;
                        }
                    }
                    
                    $treeMembers["level_{$level}"] = $levelMembers;
                }
                
                $userSlot->update(['tree_member_ids' => $treeMembers]);
            }
        }
    }
}

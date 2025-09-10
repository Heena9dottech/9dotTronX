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

class StructuredMLMTreeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure admin user exists (only admin has email and password)
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin123'),
                'sponsor_id' => null,
                'tree_round_count' => 0,
                'wallet_address' => $this->generateTRC20Address()
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

        // Create structured MLM tree with 2-3 users per level
        $this->createStructuredTree($admin, $levelPlans);

        $this->command->info('✅ Structured MLM Tree Seeder completed successfully!');
        $this->command->info("Created structured MLM tree with proper sponsor hierarchy");
        $this->command->info("Only first user has admin as sponsor, all others have user sponsors");
        $this->command->info("Created referral relationships, user slots, and income distributions");
    }

    /**
     * Create a structured MLM tree
     */
    private function createStructuredTree($admin, $levelPlans)
    {
        $users = [];
        $userCounter = 1;

        // Level 1: Only 1 user under admin (first user gets admin as sponsor)
        $level1Users = $this->createUsersForLevel($admin, $levelPlans, 1, 1, $userCounter, $users);
        $userCounter += 1;

        // Level 2: 2-3 users under the Level 1 user
        $level2Users = [];
        foreach ($level1Users as $level1User) {
            $level2Count = rand(2, 3);
            $newUsers = $this->createUsersForLevel($level1User, $levelPlans, 2, $level2Count, $userCounter, $users);
            $level2Users = array_merge($level2Users, $newUsers);
            $userCounter += $level2Count;
        }

        // Level 3: 2-3 users under each Level 2 user
        $level3Users = [];
        foreach ($level2Users as $level2User) {
            $level3Count = rand(2, 3);
            $newUsers = $this->createUsersForLevel($level2User, $levelPlans, 3, $level3Count, $userCounter, $users);
            $level3Users = array_merge($level3Users, $newUsers);
            $userCounter += $level3Count;
        }

        // Level 4: 2-3 users under each Level 3 user
        $level4Users = [];
        foreach ($level3Users as $level3User) {
            $level4Count = rand(2, 3);
            $newUsers = $this->createUsersForLevel($level3User, $levelPlans, 4, $level4Count, $userCounter, $users);
            $level4Users = array_merge($level4Users, $newUsers);
            $userCounter += $level4Count;
        }

        // Update tree member IDs for all users
        $this->updateTreeMemberIds($users, $admin);

        $this->command->info("Created structured tree with:");
        $this->command->info("- Level 1: " . count($level1Users) . " users (user1 under admin)");
        $this->command->info("- Level 2: " . count($level2Users) . " users");
        $this->command->info("- Level 3: " . count($level3Users) . " users");
        $this->command->info("- Level 4: " . count($level4Users) . " users");
        $this->command->info("- Total: " . count($users) . " users");
    }

    /**
     * Create users for a specific level
     */
    private function createUsersForLevel($sponsor, $levelPlans, $level, $count, $startCounter, &$allUsers)
    {
        $levelUsers = [];
        
        for ($i = 0; $i < $count; $i++) {
            $userNumber = $startCounter + $i;
            
            // Determine sponsor: only first user gets admin as sponsor, others get previous users
            $actualSponsorId = null;
            if ($level == 1 && $userNumber == 1) {
                // First user gets admin as sponsor
                $actualSponsorId = $sponsor->id;
            } elseif ($level == 1 && $userNumber > 1) {
                // Other level 1 users get the first user as sponsor
                $firstUser = User::where('username', 'user1')->first();
                $actualSponsorId = $firstUser ? $firstUser->id : $sponsor->id;
            } else {
                // All other levels use the provided sponsor
                $actualSponsorId = $sponsor->id;
            }
            
            // Create user (no email/password for regular users, only wallet address)
            $user = User::firstOrCreate(
                ['username' => "user{$userNumber}"],
                [
                    'name' => "User {$userNumber}",
                    'sponsor_id' => $actualSponsorId,
                    'tree_round_count' => 0,
                    'wallet_address' => $this->generateTRC20Address()
                ]
            );
            
            $levelUsers[] = $user;
            $allUsers[] = $user;

            // Create only ONE referral relationship per user (not per level plan)
            // First, determine upline structure using main_upline_id logic
            $uplineId = $user->sponsor_id;
            $mainUplineId = null;
            $upline1 = null;
            $upline2 = null;
            $upline3 = null;
            $upline4 = null;

            // Find the main_upline_id (referral_relationship id of the sponsor)
            if ($uplineId) {
                $sponsorRelationship = ReferralRelationship::where('user_id', $uplineId)->first();
                if ($sponsorRelationship) {
                    $mainUplineId = $sponsorRelationship->id;
                }
            }

            // Calculate upline chain using main_upline_id
            if ($mainUplineId) {
                // UPLINE1: Find the referral_relationship with id = main_upline_id, get its user_id
                $upline1Record = ReferralRelationship::find($mainUplineId);
                if ($upline1Record) {
                    $upline1 = $upline1Record->user_id;
                    
                    // UPLINE2: Get main_upline_id from upline1 record, find that record, get its user_id
                    if ($upline1Record->main_upline_id) {
                        $upline2Record = ReferralRelationship::find($upline1Record->main_upline_id);
                        if ($upline2Record) {
                            $upline2 = $upline2Record->user_id;
                            
                            // UPLINE3: Get main_upline_id from upline2 record, find that record, get its user_id
                            if ($upline2Record->main_upline_id) {
                                $upline3Record = ReferralRelationship::find($upline2Record->main_upline_id);
                                if ($upline3Record) {
                                    $upline3 = $upline3Record->user_id;
                                    
                                    // UPLINE4: Get main_upline_id from upline3 record, find that record, get its user_id
                                    if ($upline3Record->main_upline_id) {
                                        $upline4Record = ReferralRelationship::find($upline3Record->main_upline_id);
                                        if ($upline4Record) {
                                            $upline4 = $upline4Record->user_id;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Get sponsor username
            $sponsorUsername = 'admin';
            if ($user->sponsor_id) {
                $sponsorUser = User::find($user->sponsor_id);
                $sponsorUsername = $sponsorUser ? $sponsorUser->username : 'admin';
            }

            // Create ONE referral relationship per user
            $referralRelationship = ReferralRelationship::create([
                'user_id' => $user->id,
                'user_username' => $user->username,
                'sponsor_id' => $user->sponsor_id,
                'sponsor_username' => $sponsorUsername,
                'upline_id' => $uplineId,
                'upline_username' => $uplineId ? User::find($uplineId)->username : 'admin',
                'upline1' => $upline1,
                'upline2' => $upline2,
                'upline3' => $upline3,
                'upline4' => $upline4,
                'position' => $i % 2 == 0 ? 'L' : 'R', // Alternate between L and R
                'tree_owner_id' => $user->sponsor_id ?: $sponsor->id, // Use actual sponsor as tree owner
                'tree_owner_username' => $sponsorUsername,
                'tree_round' => 1,
                'is_spillover_slot' => false,
                'level_number' => 1, // Default to level 1 for referral relationship
                'slot_price' => 0, // Will be updated when user buys slots
                'level_id' => null, // Will be updated when user buys slots
                'user_slots_id' => null, // Will be updated when user buys slots
                'main_upline_id' => $mainUplineId
            ]);

            // Assign 1-2 random level plans to each user (create user slots only)
            $userLevelCount = rand(1, 2);
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
                    'referral_relationship_id' => $referralRelationship->id,
                    'tree_member_ids' => []
                ]);

                // Create income distributions for this level plan
                $this->createIncomeDistributions($referralRelationship, $levelPlan, $sponsor);
            }
        }
        
        return $levelUsers;
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

    /**
     * Generate a TRC20 wallet address (Tron blockchain)
     * This generates a valid Tron address format
     */
    private function generateTRC20Address()
    {
        // Tron addresses start with 'T' and are 34 characters long
        // This is a simplified generator for demo purposes
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $address = 'T';
        
        for ($i = 0; $i < 33; $i++) {
            $address .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $address;
    }
}

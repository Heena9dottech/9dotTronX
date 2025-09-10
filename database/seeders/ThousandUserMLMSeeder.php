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

class ThousandUserMLMSeeder extends Seeder
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

        // Create 1000 users with proper binary tree structure
        $this->create1000UsersWithTreeRepetition($admin, $levelPlans);

        $this->command->info('✅ Thousand User MLM Seeder completed successfully!');
        $this->command->info("Created 1000 users with proper sponsor hierarchy and tree repetition");
        $this->command->info("When trees are full, new trees are created under existing users");
        $this->command->info("Created referral relationships, user slots, and income distributions");
    }

    /**
     * Create 1000 users with proper binary tree structure
     */
    private function create1000UsersWithTreeRepetition($admin, $levelPlans)
    {
        $users = [];
        $userCounter = 1;
        $targetUsers = 1000; // 1000 users target
        
        // Create first binary tree under admin (31 users max: 1+2+4+8+16)
        $this->command->info("Creating main binary tree under admin...");
        $mainTreeUsers = $this->createBinaryTree($admin, $levelPlans, $userCounter, $users, $targetUsers);
        $userCounter += count($mainTreeUsers);
        
        // Create additional trees under existing users to reach 1000 total
        $treeNumber = 2;
        $maxTrees = 200; // Increased limit for 1000 users
        
        while ($userCounter <= $targetUsers) {
            // Find a user with space for new referrals
            $nextTreeRoot = $this->findUserWithSpace($users, $admin);
            if (!$nextTreeRoot) {
                $this->command->warn("No more space for new trees, stopping at {$userCounter} users");
                break;
            }
            
            $this->command->info("Creating Tree #{$treeNumber} under user: {$nextTreeRoot->username}");
            
            // Create a tree under this user (vary size based on remaining users needed)
            $remainingUsers = $targetUsers - $userCounter + 1;
            $treeUsers = $this->createAdaptiveTree($nextTreeRoot, $levelPlans, $userCounter, $users, $targetUsers, $remainingUsers);
            $userCounter += count($treeUsers);
            $treeNumber++;
            
            // Safety check
            if ($treeNumber > $maxTrees) {
                $this->command->warn("Reached maximum tree limit ({$maxTrees}), stopping at {$userCounter} users");
                break;
            }
        }

        // Update tree member IDs for all users
        $this->updateTreeMemberIds($users, $admin);

        $this->command->info("Created {$treeNumber} trees with total of " . count($users) . " users");
        $this->command->info("Target was {$targetUsers} users");
    }

    /**
     * Create a complete binary tree (31 users: 1+2+4+8+16)
     */
    private function createBinaryTree($rootUser, $levelPlans, $startCounter, &$allUsers, $targetUsers)
    {
        $treeUsers = [];
        $userCounter = $startCounter;
        
        // Level 1: 1 user under root (left side)
        if ($userCounter <= $targetUsers) {
            $level1Users = $this->createUsersForLevel($rootUser, $levelPlans, 1, 1, $userCounter, $allUsers, 'L');
            $treeUsers = array_merge($treeUsers, $level1Users);
            $userCounter += 1;
        }

        // Level 2: 2 users under Level 1 user (left and right)
        if ($userCounter <= $targetUsers && !empty($level1Users)) {
            $level2Users = [];
            foreach ($level1Users as $index => $level1User) {
                if ($userCounter > $targetUsers) break;
                
                // Create left child
                $leftUsers = $this->createUsersForLevel($level1User, $levelPlans, 2, 1, $userCounter, $allUsers, 'L');
                $level2Users = array_merge($level2Users, $leftUsers);
                $userCounter += 1;
                
                if ($userCounter > $targetUsers) break;
                
                // Create right child
                $rightUsers = $this->createUsersForLevel($level1User, $levelPlans, 2, 1, $userCounter, $allUsers, 'R');
                $level2Users = array_merge($level2Users, $rightUsers);
                $userCounter += 1;
            }
            $treeUsers = array_merge($treeUsers, $level2Users);
        }

        // Level 3: 2 users under each Level 2 user (4 total)
        if ($userCounter <= $targetUsers && !empty($level2Users)) {
            $level3Users = [];
            foreach ($level2Users as $level2User) {
                if ($userCounter > $targetUsers) break;
                
                // Create left child
                $leftUsers = $this->createUsersForLevel($level2User, $levelPlans, 3, 1, $userCounter, $allUsers, 'L');
                $level3Users = array_merge($level3Users, $leftUsers);
                $userCounter += 1;
                
                if ($userCounter > $targetUsers) break;
                
                // Create right child
                $rightUsers = $this->createUsersForLevel($level2User, $levelPlans, 3, 1, $userCounter, $allUsers, 'R');
                $level3Users = array_merge($level3Users, $rightUsers);
                $userCounter += 1;
            }
            $treeUsers = array_merge($treeUsers, $level3Users);
        }

        // Level 4: 2 users under each Level 3 user (8 total)
        if ($userCounter <= $targetUsers && !empty($level3Users)) {
            $level4Users = [];
            foreach ($level3Users as $level3User) {
                if ($userCounter > $targetUsers) break;
                
                // Create left child
                $leftUsers = $this->createUsersForLevel($level3User, $levelPlans, 4, 1, $userCounter, $allUsers, 'L');
                $level4Users = array_merge($level4Users, $leftUsers);
                $userCounter += 1;
                
                if ($userCounter > $targetUsers) break;
                
                // Create right child
                $rightUsers = $this->createUsersForLevel($level3User, $levelPlans, 4, 1, $userCounter, $allUsers, 'R');
                $level4Users = array_merge($level4Users, $rightUsers);
                $userCounter += 1;
            }
            $treeUsers = array_merge($treeUsers, $level4Users);
        }

        // Level 5: 2 users under each Level 4 user (16 total) - but use level 4 for upline
        if ($userCounter <= $targetUsers && !empty($level4Users)) {
            $level5Users = [];
            foreach ($level4Users as $level4User) {
                if ($userCounter > $targetUsers) break;
                
                // Create left child (use level 4 for upline since we only have upline1-4)
                $leftUsers = $this->createUsersForLevel($level4User, $levelPlans, 4, 1, $userCounter, $allUsers, 'L');
                $level5Users = array_merge($level5Users, $leftUsers);
                $userCounter += 1;
                
                if ($userCounter > $targetUsers) break;
                
                // Create right child
                $rightUsers = $this->createUsersForLevel($level4User, $levelPlans, 4, 1, $userCounter, $allUsers, 'R');
                $level5Users = array_merge($level5Users, $rightUsers);
                $userCounter += 1;
            }
            $treeUsers = array_merge($treeUsers, $level5Users);
        }

        return $treeUsers;
    }

    /**
     * Create an adaptive tree under an existing user
     */
    private function createAdaptiveTree($rootUser, $levelPlans, $startCounter, &$allUsers, $targetUsers, $remainingUsers)
    {
        $treeUsers = [];
        $userCounter = $startCounter;
        
        // Determine tree size based on remaining users needed
        if ($remainingUsers >= 15) {
            // Create a full binary tree (up to 15 users: 1+2+4+8)
            return $this->createSmallBinaryTree($rootUser, $levelPlans, $userCounter, $allUsers, $targetUsers);
        } else {
            // Create a simple tree with remaining users
            $userCount = min($remainingUsers, 8);
            
            for ($i = 0; $i < $userCount; $i++) {
                if ($userCounter > $targetUsers) break;
                
                $position = $i % 2 == 0 ? 'L' : 'R';
                $newUsers = $this->createUsersForLevel($rootUser, $levelPlans, 1, 1, $userCounter, $allUsers, $position);
                $treeUsers = array_merge($treeUsers, $newUsers);
                $userCounter += 1;
            }
        }

        return $treeUsers;
    }

    /**
     * Create a smaller binary tree (15 users: 1+2+4+8)
     */
    private function createSmallBinaryTree($rootUser, $levelPlans, $startCounter, &$allUsers, $targetUsers)
    {
        $treeUsers = [];
        $userCounter = $startCounter;
        
        // Level 1: 1 user under root
        if ($userCounter <= $targetUsers) {
            $level1Users = $this->createUsersForLevel($rootUser, $levelPlans, 1, 1, $userCounter, $allUsers, 'L');
            $treeUsers = array_merge($treeUsers, $level1Users);
            $userCounter += 1;
        }

        // Level 2: 2 users under Level 1 user
        if ($userCounter <= $targetUsers && !empty($level1Users)) {
            $level2Users = [];
            foreach ($level1Users as $level1User) {
                if ($userCounter > $targetUsers) break;
                
                // Create left child
                $leftUsers = $this->createUsersForLevel($level1User, $levelPlans, 2, 1, $userCounter, $allUsers, 'L');
                $level2Users = array_merge($level2Users, $leftUsers);
                $userCounter += 1;
                
                if ($userCounter > $targetUsers) break;
                
                // Create right child
                $rightUsers = $this->createUsersForLevel($level1User, $levelPlans, 2, 1, $userCounter, $allUsers, 'R');
                $level2Users = array_merge($level2Users, $rightUsers);
                $userCounter += 1;
            }
            $treeUsers = array_merge($treeUsers, $level2Users);
        }

        // Level 3: 2 users under each Level 2 user (4 total)
        if ($userCounter <= $targetUsers && !empty($level2Users)) {
            $level3Users = [];
            foreach ($level2Users as $level2User) {
                if ($userCounter > $targetUsers) break;
                
                // Create left child
                $leftUsers = $this->createUsersForLevel($level2User, $levelPlans, 3, 1, $userCounter, $allUsers, 'L');
                $level3Users = array_merge($level3Users, $leftUsers);
                $userCounter += 1;
                
                if ($userCounter > $targetUsers) break;
                
                // Create right child
                $rightUsers = $this->createUsersForLevel($level2User, $levelPlans, 3, 1, $userCounter, $allUsers, 'R');
                $level3Users = array_merge($level3Users, $rightUsers);
                $userCounter += 1;
            }
            $treeUsers = array_merge($treeUsers, $level3Users);
        }

        // Level 4: 2 users under each Level 3 user (8 total)
        if ($userCounter <= $targetUsers && !empty($level3Users)) {
            $level4Users = [];
            foreach ($level3Users as $level3User) {
                if ($userCounter > $targetUsers) break;
                
                // Create left child
                $leftUsers = $this->createUsersForLevel($level3User, $levelPlans, 4, 1, $userCounter, $allUsers, 'L');
                $level4Users = array_merge($level4Users, $leftUsers);
                $userCounter += 1;
                
                if ($userCounter > $targetUsers) break;
                
                // Create right child
                $rightUsers = $this->createUsersForLevel($level3User, $levelPlans, 4, 1, $userCounter, $allUsers, 'R');
                $level4Users = array_merge($level4Users, $rightUsers);
                $userCounter += 1;
            }
            $treeUsers = array_merge($treeUsers, $level4Users);
        }

        return $treeUsers;
    }

    /**
     * Find a user with space for new referrals
     */
    private function findUserWithSpace($users, $admin)
    {
        if (empty($users)) {
            return $admin;
        }

        // Count referrals for each user
        $referralCounts = [];
        foreach ($users as $user) {
            $referralCounts[$user->id] = User::where('sponsor_id', $user->id)->count();
        }

        // Find users with less than 2 referrals (can add more)
        $candidates = [];
        foreach ($referralCounts as $userId => $count) {
            if ($count < 2) {
                $candidates[] = $userId;
            }
        }

        if (empty($candidates)) {
            // If no users have space, find users with less than 4 referrals
            $candidates = [];
            foreach ($referralCounts as $userId => $count) {
                if ($count < 4) {
                    $candidates[] = $userId;
                }
            }
        }

        if (empty($candidates)) {
            // If still no candidates, find user with minimum referrals
            $minReferrals = min($referralCounts);
            $candidates = array_keys($referralCounts, $minReferrals);
        }

        if (empty($candidates)) {
            // Last resort: return admin
            return $admin;
        }
        
        // Return random candidate
        $randomKey = array_rand($candidates);
        $userId = $candidates[$randomKey];
        
        return User::find($userId) ?: $admin;
    }

    /**
     * Create users for a specific level
     */
    private function createUsersForLevel($sponsor, $levelPlans, $level, $count, $startCounter, &$allUsers, $position = 'L')
    {
        $levelUsers = [];
        
        for ($i = 0; $i < $count; $i++) {
            $userNumber = $startCounter + $i;
            
            // Determine sponsor: use the provided sponsor for all users
            $actualSponsorId = $sponsor->id;
            
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
                'position' => $position, // Use provided position
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

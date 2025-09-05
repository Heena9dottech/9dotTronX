<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ReferralRelationship;
use App\Models\LevelPlan;
use App\Models\UserSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ApiBuySlotController extends Controller
{
    /**
     * User Add function
     * API endpoint: POST /api/useradd
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function useradd(Request $request)
    {
        try {
   
            $username = $request->username;
            $sponsorId = $request->sponsor_name;

            $sponsor = User::where('username', $request->sponsor_name)->first();

            $user = User::create([
                'username' => $request->username,
                'email' => $request->username . '@mlm.com', // auto-generate email
                'password' => Hash::make('123456'),
                'sponsor_id' => $sponsor->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "User {$username} created successfully",
                'data' => [
                    'username' => $username,
                    'sponsor_id' => $sponsorId
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('API Error creating user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buy Slot function
     * API endpoint: POST /api/buyslot
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function buyslot(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'username' => 'required|exists:users,username',
                'level_id' => 'required|exists:level_plans,id'
            ]);
            
            $username = $request->username;
            $levelId = $request->level_id;

            // Find the user by username and level plan
            $user = User::where('username', $username)->first();
            $levelPlan = LevelPlan::find($levelId);

            // Get sponsor from user table
            $sponsor = User::find($user->sponsor_id);

            DB::transaction(function () use ($user, $levelPlan, $sponsor) {
                // Check if user has completed 30 people (2+4+8+16=30)
                $memberCounts = $this->calculateMemberCountBySlots($user->id, 4);
                $totalMembers = array_sum($memberCounts);
                
                // Validate that user is not trying to replace an existing slot
                $this->validateUserSlotPurchase($user->id);
                
                if ($totalMembers >= 30) {
                    // User is a tree owner - place in their own tree's first empty slot
                    $this->addUserToOwnTree($user, $levelPlan);
                    Log::info("✅ TREE OWNER SLOT PURCHASED: User {$user->username} (ID: {$user->id}) with {$totalMembers} members bought slot in their own tree with level {$levelPlan->level_number}");
                } else {
                    // Normal user - place under sponsor
                    $this->addUserToTreeWithLevel($sponsor, $user, $levelPlan);
                    Log::info("✅ SLOT PURCHASED: User {$user->username} (ID: {$user->id}) bought slot with level {$levelPlan->level_number} under sponsor {$sponsor->username} (ID: {$sponsor->id})");
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Slot purchased successfully for user {$username}",
                'data' => [
                    'username' => $username,
                    'user_id' => $user->id,
                    'level_id' => $levelId,
                    'sponsor_id' => $sponsor->id,
                    'sponsor_username' => $sponsor->username
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('API Error buying slot: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error buying slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate member count by levels using left/right slots
     * Level 1: 2 slots (left, right)
     * Level 2: 4 slots (2x2)
     * Level 3: 8 slots (2x4)
     * Level 4: 16 slots (2x8)
     * Total: 2+4+8+16 = 30 members
     * 
     * @param int $userId
     * @param int $maxLevels
     * @return array
     */
    private function calculateMemberCountBySlots($userId, $maxLevels = 4)
    {
        $levelCounts = [];
        $currentLevel = [$userId]; // Start with the user's ID
        $level = 1;

        while ($level <= $maxLevels && !empty($currentLevel)) {
            $nextLevel = [];
            $levelCount = 0;

            foreach ($currentLevel as $currentUserId) {
                // Get direct children (referrals) of current user
                $children = ReferralRelationship::where('upline_id', $currentUserId)
                    ->get();

                foreach ($children as $child) {
                    $nextLevel[] = $child->user_id;
                    $levelCount++;
                }
            }

            $levelCounts[$level] = $levelCount;
            $currentLevel = $nextLevel;
            $level++;
        }

        // Fill remaining levels with 0 if we didn't reach max levels
        for ($i = $level; $i <= $maxLevels; $i++) {
            $levelCounts[$i] = 0;
        }

        return $levelCounts;
    }

    /**
     * Add user to their own tree (for tree owners with 30+ members)
     * Places user in first empty slot of their own tree
     * Slots are immutable once assigned
     */
    private function addUserToOwnTree(User $user, $levelPlan)
    {
        // Find first empty slot in user's own tree
        $placement = $this->findFirstEmptySlotInOwnTree($user->id);
        
        // Validate that slot is not already occupied (double-check for immutability)
        $this->validateSlotNotReplaced($placement['upline_id'], $placement['position'], $user->id);
        
        // Create the referral relationship entry
        $newUserEntry = [
            'user_id' => $user->id,
            'user_username' => $user->username,
            'sponsor_id' => $user->sponsor_id,
            'sponsor_username' => User::find($user->sponsor_id)->username,
            'upline_id' => $placement['upline_id'],
            'upline_username' => $placement['upline_username'],
            'position' => $placement['position'],
            'level_number' => $levelPlan->level_number,
            'slot_price' => $levelPlan->price,
            'level_id' => $levelPlan->id
        ];
        
        ReferralRelationship::create($newUserEntry);
        
        Log::info("✅ USER ADDED TO OWN TREE: {$user->username} (ID: {$user->id}) placed under {$placement['upline_username']} (ID: {$placement['upline_id']}) Position: {$placement['position']} in their own tree - SLOT PERMANENTLY ASSIGNED");
    }

    /**
     * Find first empty slot in user's own tree
     * Uses BFS to find first available left-right slot
     */
    private function findFirstEmptySlotInOwnTree($userId)
    {
        // Start with the user's own slots first
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception("User not found: {$userId}");
        }
        
        // Check user's left slot first
        if ($this->isSlotAvailable($userId, 'L')) {
            return [
                'upline_id' => $userId,
                'upline_username' => $user->username,
                'position' => 'L'
            ];
        }
        
        // Check user's right slot second
        if ($this->isSlotAvailable($userId, 'R')) {
            return [
                'upline_id' => $userId,
                'upline_username' => $user->username,
                'position' => 'R'
            ];
        }
        
        // If user's slots are full, use BFS to find next available slot
        $queue = [$userId];
        $visited = [];
        
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            
            if (in_array($currentId, $visited)) {
                continue;
            }
            $visited[] = $currentId;
            
            // Check if this user has available slots (left-right priority)
            $availableSlot = $this->findFirstAvailableSlotForUpline($currentId);
            if ($availableSlot) {
                return $availableSlot;
            }
            
            // Both slots are filled, add children to queue for next level
            $children = ReferralRelationship::where('upline_id', $currentId)
                ->orderBy('position', 'asc') // Left first, then right
                ->get();
                
            foreach ($children as $child) {
                if (!in_array($child->user_id, $visited)) {
                    $queue[] = $child->user_id;
                }
            }
        }
        
        // Fallback: put under user (should not happen if user has space)
        return [
            'upline_id' => $userId,
            'upline_username' => $user->username,
            'position' => 'L'
        ];
    }

    /**
     * Add user to tree with level plan information
     * Same logic as ApiController::createUserWithLevel
     */
    private function addUserToTreeWithLevel(User $sponsor, User $newUser, $levelPlan)
    {
        // Log the start of adding user to tree
        Log::info("🔄 API ADDING USER WITH LEVEL: {$newUser->username} (ID: {$newUser->id}) sponsored by {$sponsor->username} (ID: {$sponsor->id}) with level {$levelPlan->level_number}");
        
        // Use DB transaction to ensure atomicity
        DB::transaction(function () use ($sponsor, $newUser, $levelPlan) {
            // Step 1: Find first empty slot in sponsor's subtree (left-right priority)
            $placement = $this->findFirstEmptySlotInSponsorSubtree($sponsor->id);
            
            // Step 2: Validate that slot is not already occupied (immutability check)
            $this->validateSlotNotReplaced($placement['upline_id'], $placement['position'], $newUser->id);
            
            // Step 3: Insert the new user entry with level plan information
            $newUserEntry = [
                'user_id' => $newUser->id,
                'user_username' => $newUser->username,
                'sponsor_id' => $sponsor->id,
                'sponsor_username' => $sponsor->username,
                'upline_id' => $placement['upline_id'],
                'upline_username' => $placement['upline_username'],
                'position' => $placement['position'],
                'level_number' => $levelPlan->level_number,
                'slot_price' => $levelPlan->price,
                'level_id' => $levelPlan->id
            ];
            
            ReferralRelationship::create($newUserEntry);
            
            // Log the successful creation of new user entry
            Log::info("✅ API USER ENTRY CREATED: {$newUser->username} (ID: {$newUser->id}) placed under {$placement['upline_username']} (ID: {$placement['upline_id']}) Position: {$placement['position']} with Level {$levelPlan->level_number} - SLOT PERMANENTLY ASSIGNED");
        });
    }


    /**
     * Find first empty slot in sponsor's subtree using BFS
     */
    private function findFirstEmptySlotInSponsorSubtree($sponsorId)
    {
        // Step 1: Check sponsor's own slots first (left, then right)
        $sponsor = User::find($sponsorId);
        if (!$sponsor) {
            throw new \Exception("Sponsor not found: {$sponsorId}");
        }
        
        // Check sponsor's left slot first
        if ($this->isSlotAvailable($sponsorId, 'L')) {
            return [
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'L'
            ];
        }
        
        // Check sponsor's right slot second
        if ($this->isSlotAvailable($sponsorId, 'R')) {
            return [
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'R'
            ];
        }
        
        // Step 2: If sponsor's slots are full, use BFS on sponsor's subtree
        $queue = [$sponsorId];
        $visited = [];
        
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            
            if (in_array($currentId, $visited)) {
                continue;
            }
            $visited[] = $currentId;
            
            // Check if this user has available slots (left-right priority)
            $availableSlot = $this->findFirstAvailableSlotForUpline($currentId);
            if ($availableSlot) {
                return $availableSlot;
            }
            
            // Both slots are filled, add children to queue for next level
            $children = ReferralRelationship::where('upline_id', $currentId)
                ->orderBy('position', 'asc') // Left first, then right
                ->get();
                
            foreach ($children as $child) {
                if (!in_array($child->user_id, $visited)) {
                    $queue[] = $child->user_id;
                }
            }
        }
        
        // Fallback: put under sponsor (should not happen if sponsor has space)
        return [
            'upline_id' => $sponsorId,
            'upline_username' => $sponsor->username,
            'position' => 'L'
        ];
    }

    /**
     * Check if a specific slot is available for a user
     * Once a slot is assigned, it cannot be changed or replaced
     */
    private function isSlotAvailable($uplineId, $position)
    {
        $existingUser = ReferralRelationship::where('upline_id', $uplineId)
            ->where('position', $position)
            ->first();
        
        // Slot is only available if it's completely empty
        // Once assigned, slot is permanently fixed and cannot be replaced
        return !$existingUser; // Return true if slot is empty
    }

    /**
     * Validate that user is not trying to replace an existing slot
     * Slots are immutable once assigned
     */
    private function validateSlotNotReplaced($uplineId, $position, $userId)
    {
        $existingSlot = ReferralRelationship::where('upline_id', $uplineId)
            ->where('position', $position)
            ->first();
        
        if ($existingSlot) {
            throw new \Exception("Slot is already occupied and cannot be replaced. Position {$position} under user ID {$uplineId} is permanently assigned to user {$existingSlot->user_username} (ID: {$existingSlot->user_id}). Slots are immutable once assigned.");
        }
        
        return true;
    }

    /**
     * Validate that user can purchase a slot
     * Ensures slots are immutable and cannot be replaced
     */
    private function validateUserSlotPurchase($userId)
    {
        // Check if user already has any existing slots
        $existingSlots = ReferralRelationship::where('user_id', $userId)->get();
        
        if ($existingSlots->count() > 0) {
            Log::info("✅ USER SLOT VALIDATION: User ID {$userId} already has {$existingSlots->count()} existing slots. Allowing additional slot purchase as slots are immutable.");
        }
        
        // Additional validation can be added here if needed
        // For now, we allow multiple slots per user as per requirements
        
        return true;
    }

    /**
     * Find first available slot for a specific upline (left-right priority)
     */
    private function findFirstAvailableSlotForUpline($uplineId)
    {
        // Check left slot first (top priority)
        if ($this->isSlotAvailable($uplineId, 'L')) {
            $uplineUser = User::find($uplineId);
            return [
                'upline_id' => $uplineId,
                'upline_username' => $uplineUser ? $uplineUser->username : null,
                'position' => 'L'
            ];
        }
        
        // Check right slot second
        if ($this->isSlotAvailable($uplineId, 'R')) {
            $uplineUser = User::find($uplineId);
            return [
                'upline_id' => $uplineId,
                'upline_username' => $uplineUser ? $uplineUser->username : null,
                'position' => 'R'
            ];
        }
        
        // Both slots are full
        return null;
    }

}

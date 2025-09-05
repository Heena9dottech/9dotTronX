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

class ApiMLMUserController extends Controller
{

    /**
     * Create new user with MLM tree logic
     * API endpoint: POST /create-new-user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createNewUser(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'username' => 'required|unique:users,username',
                'sponsor_name' => 'required|exists:users,username',
                'level_id' => 'required|exists:level_plans,id'
            ]);

            $username = $request->username;
            $sponsorName = $request->sponsor_name;
            $levelId = $request->level_id;

            // Find the sponsor by name
            $sponsor = User::where('username', $sponsorName)->first();
            $levelPlan = LevelPlan::find($levelId);

            DB::transaction(function () use ($username, $sponsor, $levelPlan) {
                // Create the new user
                $newUser = User::create([
                    'username' => $username,
                    'email' => $username . '@mlm.com',
                    'password' => Hash::make('123456'),
                    'sponsor_id' => $sponsor->id
                ]);

                // Find first empty slot in current round
                $currentRound = $this->getCurrentRound($sponsor->id);
                $emptySlot = $this->findFirstEmptySlot($sponsor->id, $currentRound);

                // Debug information
                Log::info("Debug - Sponsor ID: {$sponsor->id}, Current Round: {$currentRound}");
                Log::info("Debug - Empty Slot Result: " . json_encode($emptySlot));

                if ($emptySlot['found']) {
                    $uplineUser = User::find($emptySlot['upline_id']);
                    $this->addUserToTree($uplineUser, $newUser, $levelPlan, $currentRound, $emptySlot['position']);
                } else {
                    // If no empty slot found, place directly under sponsor
                    Log::info("No empty slot found, placing directly under sponsor");
                    $this->addUserToTree($sponsor, $newUser, $levelPlan, $currentRound, 'L');
                }

                // Create user slot entry
                $this->createUserSlotEntry($newUser->id, $levelPlan->id);
            });

            return response()->json([
                'success' => true,
                'message' => "User {$username} created successfully under sponsor {$sponsorName}.",
                'data' => [
                    'username' => $username,
                    'sponsor_username' => $sponsorName,
                    'sponsor_id' => $sponsor->id,
                    'level_id' => $levelId
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('API Error creating new user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
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
                    ->where('is_spillover_slot', false)
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
     * Get current round for a user
     * 
     * @param int $userId
     * @return int
     */
    private function getCurrentRound($userId)
    {
        // Get all rounds where this user is either upline or user
        $rounds = ReferralRelationship::where(function($query) use ($userId) {
                $query->where('upline_id', $userId)
                      ->orWhere('user_id', $userId);
            })
            ->select('tree_round')
            ->distinct()
            ->orderBy('tree_round', 'asc')
            ->pluck('tree_round')
            ->toArray();

        if (empty($rounds)) {
            return 1; // First round
        }

        // Always use the latest round
        return max($rounds);
    }

    /**
     * Find first empty slot in sponsor's subtree
     * Level by level: 1st level (2 slots), 2nd level (4 slots), 3rd level (8 slots), etc.
     * 
     * @param int $sponsorId
     * @param int $round
     * @return array
     */
    private function findFirstEmptySlot($sponsorId, $round)
    {
        $sponsor = User::find($sponsorId);
        
        if (!$sponsor) {
            return [
                'found' => false,
                'message' => 'Sponsor not found'
            ];
        }

        // Check if sponsor has any existing relationships in this round
        $existingRelationships = ReferralRelationship::where('upline_id', $sponsorId)
            ->where('tree_round', $round)
            ->count();

        // If no existing relationships, sponsor's left slot is available
        if ($existingRelationships == 0) {
            return [
                'found' => true,
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'L',
                'level' => 1
            ];
        }

        // Level 1: Check sponsor's direct slots (left, then right)
        if ($this->isSlotAvailable($sponsorId, 'L', $round)) {
            return [
                'found' => true,
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'L',
                'level' => 1
            ];
        }

        if ($this->isSlotAvailable($sponsorId, 'R', $round)) {
            return [
                'found' => true,
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'R',
                'level' => 1
            ];
        }

        // Level 2: Check sponsor's children (4 slots total)
        $level2Children = ReferralRelationship::where('upline_id', $sponsorId)
            ->where('tree_round', $round)
            ->where('is_spillover_slot', false)
            ->orderBy('position', 'asc')
            ->get();

        foreach ($level2Children as $child) {
            // Check left slot first
            if ($this->isSlotAvailable($child->user_id, 'L', $round)) {
                return [
                    'found' => true,
                    'upline_id' => $child->user_id,
                    'upline_username' => $child->user_username,
                    'position' => 'L',
                    'level' => 2
                ];
            }
            // Check right slot second
            if ($this->isSlotAvailable($child->user_id, 'R', $round)) {
                return [
                    'found' => true,
                    'upline_id' => $child->user_id,
                    'upline_username' => $child->user_username,
                    'position' => 'R',
                    'level' => 2
                ];
            }
        }

        // Level 3: Check level 2 children's children (8 slots total)
        foreach ($level2Children as $level2Child) {
            $level3Children = ReferralRelationship::where('upline_id', $level2Child->user_id)
                ->where('tree_round', $round)
                ->where('is_spillover_slot', false)
                ->orderBy('position', 'asc')
                ->get();

            foreach ($level3Children as $child) {
                // Check left slot first
                if ($this->isSlotAvailable($child->user_id, 'L', $round)) {
                    return [
                        'found' => true,
                        'upline_id' => $child->user_id,
                        'upline_username' => $child->user_username,
                        'position' => 'L',
                        'level' => 3
                    ];
                }
                // Check right slot second
                if ($this->isSlotAvailable($child->user_id, 'R', $round)) {
                    return [
                        'found' => true,
                        'upline_id' => $child->user_id,
                        'upline_username' => $child->user_username,
                        'position' => 'R',
                        'level' => 3
                    ];
                }
            }
        }

        // Level 4: Check level 3 children's children (16 slots total)
        foreach ($level2Children as $level2Child) {
            $level3Children = ReferralRelationship::where('upline_id', $level2Child->user_id)
                ->where('tree_round', $round)
                ->where('is_spillover_slot', false)
                ->orderBy('position', 'asc')
                ->get();

            foreach ($level3Children as $level3Child) {
                $level4Children = ReferralRelationship::where('upline_id', $level3Child->user_id)
                    ->where('tree_round', $round)
                    ->where('is_spillover_slot', false)
                    ->orderBy('position', 'asc')
                    ->get();

                foreach ($level4Children as $child) {
                    // Check left slot first
                    if ($this->isSlotAvailable($child->user_id, 'L', $round)) {
                        return [
                            'found' => true,
                            'upline_id' => $child->user_id,
                            'upline_username' => $child->user_username,
                            'position' => 'L',
                            'level' => 4
                        ];
                    }
                    // Check right slot second
                    if ($this->isSlotAvailable($child->user_id, 'R', $round)) {
                        return [
                            'found' => true,
                            'upline_id' => $child->user_id,
                            'upline_username' => $child->user_username,
                            'position' => 'R',
                            'level' => 4
                        ];
                    }
                }
            }
        }

        // No empty slot found
        return [
            'found' => false,
            'message' => 'No empty slot found in sponsor\'s subtree'
        ];
    }

    /**
     * Check if a specific slot is available for a user
     */
    private function isSlotAvailable($uplineId, $position, $round = null)
    {
        $query = ReferralRelationship::where('upline_id', $uplineId)
            ->where('position', $position);

        // If round is specified, check within that round
        if ($round) {
            $query->where('tree_round', $round);
        }

        $existingUser = $query->first();

        return !$existingUser; // Return true if slot is empty
    }

    /**
     * Add user to tree with level plan information
     * 
     * @param User $uplineUser
     * @param User $newUser
     * @param LevelPlan $levelPlan
     * @param int $round
     * @param string $position
     * @return int
     */
    private function addUserToTree($uplineUser, $newUser, $levelPlan, $round, $position)
    {
        // Create the referral relationship entry
        $newUserEntry = [
            'user_id' => $newUser->id,
            'user_username' => $newUser->username,
            'sponsor_id' => $newUser->sponsor_id,
            'sponsor_username' => User::find($newUser->sponsor_id)->username,
            'upline_id' => $uplineUser->id,
            'upline_username' => $uplineUser->username,
            'position' => $position,
            'tree_owner_id' => $uplineUser->id, // Using upline as tree owner for simplicity
            'tree_owner_username' => $uplineUser->username,
            'tree_round' => $round,
            'is_spillover_slot' => false,
            'level_number' => $levelPlan->level_number,
            'slot_price' => $levelPlan->price,
            'level_id' => $levelPlan->id
        ];

        $referralRelationship = ReferralRelationship::create($newUserEntry);

        Log::info("✅ USER ADDED TO TREE: {$newUser->username} (ID: {$newUser->id}) placed under {$uplineUser->username} (ID: {$uplineUser->id}) Position: {$position}");

        return $referralRelationship->id;
    }


    /**
     * Create user slot entry
     * 
     * @param int $userId
     * @param int $levelPlanId
     * @return void
     */
    private function createUserSlotEntry($userId, $levelPlanId)
    {
        // Get the latest referral relationship for this user
        $referralRelationship = ReferralRelationship::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        if ($referralRelationship) {
            UserSlot::create([
                'user_id' => $userId,
                'level_plans_id' => $levelPlanId,
                'referral_relationship_id' => $referralRelationship->id
            ]);

            Log::info("📝 USER SLOT CREATED: User {$userId}, Level Plan {$levelPlanId}, Referral Relationship {$referralRelationship->id}");
        }
    }
}

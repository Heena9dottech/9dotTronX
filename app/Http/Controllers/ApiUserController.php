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

class ApiUserController extends Controller
{
    /**
     * Get member count for sponsor before adding new member
     * API endpoint: POST /api/sponsor-member-count
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteuser(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $userId = $request->id;
                DB::table('referral_relationships')->where('user_id', $userId)->delete();
                DB::table('users')->where('id', $userId)->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'User and related referrals deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }



    public function getSponsorMemberCount(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'username' => 'required|string',
                'sponsor_name' => 'required|string',
                'level_id' => 'required|integer'
            ]);

            $username = $request->username;
            $sponsorName = $request->sponsor_name;
            $levelId = $request->level_id;

            // Find the sponsor by name
            $sponsor = User::where('username', $sponsorName)->first();

            if (!$sponsor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sponsor not found with name: ' . $sponsorName
                ], 404);
            }

            // Get the tree owner for this sponsor
            $treeOwner = $this->findTreeOwner($sponsor);

            // Get current active round
            $currentRound = $this->getCurrentActiveRound($treeOwner->id);

            // Calculate member count for 4 levels under sponsor
            $memberCounts = $this->calculateMemberCountByLevels($sponsor->id, $treeOwner->id, $currentRound, 4);

            // Calculate total members
            $totalMembers = array_sum($memberCounts);

            // Check if sponsor has completed 30 members (needs new round)
            $needsNewRound = ($totalMembers >= 30);
            // dd($needsNewRound);
            if ($needsNewRound) {
                // Create new round for sponsor and place user in sponsor's left slot
                $newRound = $currentRound + 1;
                $this->createNewRoundForSponsor($sponsor, $treeOwner, $newRound);

                $findFirstEmptySlot = [
                    'found' => true,
                    'upline_id' => $sponsor->id,
                    'upline_username' => $sponsor->username,
                    'position' => 'L',
                    'level' => 1,
                    'is_new_round' => true,
                    'round_number' => $newRound
                ];
            } else {
                // Find first empty slot in current round
                $findFirstEmptySlot = $this->findFirstEmptySlot($sponsor->id, $treeOwner->id, $currentRound);
                $findFirstEmptySlot['is_new_round'] = false;
                $findFirstEmptySlot['round_number'] = $currentRound;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'new_member_username' => $username,
                    'sponsor_username' => $sponsorName,
                    'sponsor_id' => $sponsor->id,
                    'level_id' => $levelId,
                    'total_members_under_sponsor' => $totalMembers,
                    'needs_new_round' => $needsNewRound,
                    'first_empty_slot' => $findFirstEmptySlot
                ],
                'message' => $needsNewRound
                    ? "Sponsor {$sponsorName} completed 30 members! New round {$findFirstEmptySlot['round_number']} created. New member will be placed in sponsor's left slot."
                    : "Sponsor {$sponsorName} currently has {$totalMembers} total members across 4 levels"
            ], 200);
        } catch (\Exception $e) {
            Log::error('API Error getting sponsor member count: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error getting sponsor member count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate member count by levels using BFS (Breadth-First Search)
     * 
     * @param int $userId
     * @param int $treeOwnerId
     * @param int $round
     * @param int $maxLevels
     * @return array
     */
    private function calculateMemberCountByLevels($userId, $treeOwnerId, $round, $maxLevels = 4)
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
                    ->where('tree_owner_id', $treeOwnerId)
                    ->where('tree_round', $round)
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
     * Find the tree owner for a given user
     * 
     * @param User $user
     * @return User
     */
    private function findTreeOwner(User $user)
    {
        $relationship = ReferralRelationship::where('user_id', $user->id)->first();

        if (!$relationship || !$relationship->tree_owner_id) {
            // User is the tree owner
            return $user;
        }

        return User::find($relationship->tree_owner_id);
    }

    /**
     * Get the current active round for a tree owner
     * 
     * @param int $treeOwnerId
     * @return int
     */
    private function getCurrentActiveRound($treeOwnerId)
    {
        // Get all rounds for this tree owner
        $rounds = ReferralRelationship::where('tree_owner_id', $treeOwnerId)
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
     * Find first empty slot and position in sponsor's subtree
     * Level by level: 1st level (2 slots), 2nd level (4 slots), 3rd level (8 slots), etc.
     * 
     * @param int $sponsorId
     * @param int $treeOwnerId
     * @param int $round
     * @return array
     */
    private function findFirstEmptySlot($sponsorId, $treeOwnerId, $round)
    {
        $sponsor = User::find($sponsorId);
        // dd($sponsor);
        if (!$sponsor) {
            return [
                'found' => false,
                'message' => 'Sponsor not found'
            ];
        }

        // Level 1: Check sponsor's direct slots (left, then right)
        if ($this->isSlotAvailable($sponsorId, 'L', $treeOwnerId, $round)) {
            return [
                'found' => true,
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'L',
                'level' => 1
            ];
        }

        if ($this->isSlotAvailable($sponsorId, 'R', $treeOwnerId, $round)) {
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
            ->where('tree_owner_id', $treeOwnerId)
            ->where('tree_round', $round)
            ->where('is_spillover_slot', false)
            ->orderBy('position', 'asc')
            ->get();

        foreach ($level2Children as $child) {
            // Check left slot first
            if ($this->isSlotAvailable($child->user_id, 'L', $treeOwnerId, $round)) {
                return [
                    'found' => true,
                    'upline_id' => $child->user_id,
                    'upline_username' => $child->user_username,
                    'position' => 'L',
                    'level' => 2
                ];
            }
            // Check right slot second
            if ($this->isSlotAvailable($child->user_id, 'R', $treeOwnerId, $round)) {
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
                ->where('tree_owner_id', $treeOwnerId)
                ->where('tree_round', $round)
                ->where('is_spillover_slot', false)
                ->orderBy('position', 'asc')
                ->get();

            foreach ($level3Children as $child) {
                // Check left slot first
                if ($this->isSlotAvailable($child->user_id, 'L', $treeOwnerId, $round)) {
                    return [
                        'found' => true,
                        'upline_id' => $child->user_id,
                        'upline_username' => $child->user_username,
                        'position' => 'L',
                        'level' => 3
                    ];
                }
                // Check right slot second
                if ($this->isSlotAvailable($child->user_id, 'R', $treeOwnerId, $round)) {
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
                ->where('tree_owner_id', $treeOwnerId)
                ->where('tree_round', $round)
                ->where('is_spillover_slot', false)
                ->orderBy('position', 'asc')
                ->get();

            foreach ($level3Children as $level3Child) {
                $level4Children = ReferralRelationship::where('upline_id', $level3Child->user_id)
                    ->where('tree_owner_id', $treeOwnerId)
                    ->where('tree_round', $round)
                    ->where('is_spillover_slot', false)
                    ->orderBy('position', 'asc')
                    ->get();

                foreach ($level4Children as $child) {
                    // Check left slot first
                    if ($this->isSlotAvailable($child->user_id, 'L', $treeOwnerId, $round)) {
                        return [
                            'found' => true,
                            'upline_id' => $child->user_id,
                            'upline_username' => $child->user_username,
                            'position' => 'L',
                            'level' => 4
                        ];
                    }
                    // Check right slot second
                    if ($this->isSlotAvailable($child->user_id, 'R', $treeOwnerId, $round)) {
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
    private function isSlotAvailable($uplineId, $position, $treeOwnerId = null, $round = null)
    {
        $query = ReferralRelationship::where('upline_id', $uplineId)
            ->where('position', $position);

        // If tree owner is specified, only check within that tree
        if ($treeOwnerId) {
            $query->where('tree_owner_id', $treeOwnerId);
        }

        // If round is specified, check within that round
        if ($round) {
            $query->where('tree_round', $round);
        }

        $existingUser = $query->first();

        return !$existingUser; // Return true if slot is empty
    }

    /**
     * Find first available slot for a specific upline (left-right priority)
     */
    private function findFirstAvailableSlotForUpline($uplineId, $treeOwnerId = null, $round = null)
    {
        // Check left slot first (top priority)
        if ($this->isSlotAvailable($uplineId, 'L', $treeOwnerId, $round)) {
            $uplineUser = User::find($uplineId);
            return [
                'upline_id' => $uplineId,
                'upline_username' => $uplineUser ? $uplineUser->username : null,
                'position' => 'L'
            ];
        }

        // Check right slot second
        if ($this->isSlotAvailable($uplineId, 'R', $treeOwnerId, $round)) {
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

    /**
     * Create new round for sponsor when they complete 30 members
     * 
     * @param User $sponsor
     * @param User $treeOwner
     * @param int $newRound
     * @return void
     */
    private function createNewRoundForSponsor($sponsor, $treeOwner, $newRound)
    {
        // Update sponsor's tree_round_count
        $sponsor->increment('tree_round_count');

        // Log the new round creation
        Log::info("🎉 NEW ROUND CREATED: Sponsor {$sponsor->username} (ID: {$sponsor->id}) completed 30 members! Starting round {$newRound}");
    }

    /**
     * Create user under sponsor (31st person logic)
     * API endpoint: POST /api/create-user-under-sponsor
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUserUnderSponsor(Request $request)
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

            // Get the tree owner for this sponsor
            $treeOwner = $this->findTreeOwner($sponsor);

            // Calculate member count for 4 levels under sponsor
            $memberCounts = $this->calculateMemberCountByLevels($sponsor->id, $treeOwner->id, 1, 4);
            $totalMembers = array_sum($memberCounts);

            // Check if sponsor has completed 30 members (needs new position)
            $needsNewPosition = ($totalMembers >= 30);
            // dd($needsNewPosition);
            DB::transaction(function () use ($username, $sponsor, $levelPlan, $treeOwner, $needsNewPosition) {
                // Create the new user

                // dd($needsNewPosition);
                if ($needsNewPosition) {

                    // 31st person logic: Create sponsor entry first, then new user
                    // Step 1: Find first empty slot for sponsor
                    $emptySlot = $this->findFirstEmptySlot($sponsor->id, $treeOwner->id, 1);
                    dd($emptySlot);
                    if ($emptySlot['found']) {
                        $newUser = User::create([
                            'username' => $username,
                            'email' => $username . '@mlm.com',
                            'password' => Hash::make('123456'),
                            'sponsor_id' => $sponsor->id
                        ]);

                        $uplineUser = User::find($emptySlot['upline_id']);

                        // Step 2: Create sponsor's entry in first empty slot
                        $sponsorReferralId = $this->addSponsorToTree($sponsor, $uplineUser, $treeOwner, 1, $emptySlot['position']);

                        // Step 3: Create new user under sponsor's left side
                        $newUserReferralId = $this->addUserToTreeWithLevel($sponsor, $newUser, $levelPlan, $treeOwner, 1, 'L');

                        // Step 4: Create user_slot entries
                        $this->createUserSlotEntry($sponsor->id, $levelPlan->id, $sponsorReferralId);
                        $this->createUserSlotEntry($newUser->id, $levelPlan->id, $newUserReferralId);
                    } else {
                        dd('No empty slot found for sponsor placement');
                        throw new \Exception('No empty slot found for sponsor placement');
                    }
                } else {
                    // Normal logic: Place new user in first empty slot
                    $emptySlot = $this->findFirstEmptySlot($sponsor->id, $treeOwner->id, 1);

                    if ($emptySlot['found']) {
                        $uplineUser = User::find($emptySlot['upline_id']);
                        $referralId = $this->addUserToTreeWithLevel($uplineUser, $newUser, $levelPlan, $treeOwner, 1, $emptySlot['position']);

                        // Create user_slot entry
                        $this->createUserSlotEntry($newUser->id, $levelPlan->id, $referralId);
                    } else {
                        throw new \Exception('No empty slot found for new user');
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => $needsNewPosition
                    ? "User {$username} created as 31st member! New position created for sponsor {$sponsorName}."
                    : "User {$username} created successfully under sponsor {$sponsorName}.",
                'data' => [
                    'username' => $username,
                    'sponsor_username' => $sponsorName,
                    'sponsor_id' => $sponsor->id,
                    'level_id' => $levelId,
                    'total_members_under_sponsor' => $totalMembers,
                    'needs_new_position' => $needsNewPosition
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('API Error creating user under sponsor: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add user to tree with level plan information
     * 
     * @param User $uplineUser
     * @param User $newUser
     * @param LevelPlan $levelPlan
     * @param User $treeOwner
     * @param int $round
     * @param string $position
     * @return int
     */
    private function addUserToTreeWithLevel($uplineUser, $newUser, $levelPlan, $treeOwner, $round, $position)
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
            'tree_owner_id' => $treeOwner->id,
            'tree_owner_username' => $treeOwner->username,
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
     * Add sponsor to tree in first empty slot (for 31st person logic)
     * 
     * @param User $sponsor
     * @param User $uplineUser
     * @param User $treeOwner
     * @param int $round
     * @param string $position
     * @return int
     */
    private function addSponsorToTree($sponsor, $uplineUser, $treeOwner, $round, $position)
    {
        // Get sponsor's level plan (use level 1 as default for sponsor)
        $sponsorLevelPlan = LevelPlan::where('level_number', 1)->first();

        if (!$sponsorLevelPlan) {
            throw new \Exception('Level plan not found for sponsor');
        }

        // Create the sponsor's referral relationship entry
        $sponsorEntry = [
            'user_id' => $sponsor->id,
            'user_username' => $sponsor->username,
            'sponsor_id' => $sponsor->sponsor_id,
            'sponsor_username' => $sponsor->sponsor ? $sponsor->sponsor->username : null,
            'upline_id' => $uplineUser->id,
            'upline_username' => $uplineUser->username,
            'position' => $position,
            'tree_owner_id' => $treeOwner->id,
            'tree_owner_username' => $treeOwner->username,
            'tree_round' => $round,
            'is_spillover_slot' => false,
            'level_number' => $sponsorLevelPlan->level_number,
            'slot_price' => $sponsorLevelPlan->price,
            'level_id' => $sponsorLevelPlan->id
        ];

        $referralRelationship = ReferralRelationship::create($sponsorEntry);

        Log::info("🎯 SPONSOR ADDED TO TREE: {$sponsor->username} (ID: {$sponsor->id}) placed under {$uplineUser->username} (ID: {$uplineUser->id}) Position: {$position} - This is sponsor's new position for 31st person logic");

        return $referralRelationship->id;
    }


    /**
     * Create user slot entry
     * 
     * @param int $userId
     * @param int $levelPlanId
     * @param int $referralRelationshipId
     * @return void
     */
    private function createUserSlotEntry($userId, $levelPlanId, $referralRelationshipId)
    {
        UserSlot::create([
            'user_id' => $userId,
            'level_plans_id' => $levelPlanId,
            'referral_relationship_id' => $referralRelationshipId
        ]);

        Log::info("📝 USER SLOT CREATED: User {$userId}, Level Plan {$levelPlanId}, Referral Relationship {$referralRelationshipId}");
    }
}

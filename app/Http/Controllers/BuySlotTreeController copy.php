<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ReferralRelationship;
use App\Models\LevelPlan;
use App\Models\UserSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BuySlotTreeController extends Controller
{


    public function buyslot(Request $request)
    {
        // dd($request->all());
        try {
            // Validate the request
            $request->validate([
                'username' => 'nullable|exists:users,username',
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
                // Step 1: Create user_slots entry with referral_relationship_id as null
                $userSlot = UserSlot::create([
                    'user_id' => $user->id,
                    'username' => $user->username, // Add username for easy understanding
                    'level_plans_id' => $levelPlan->id,
                    'referral_relationship_id' => null,
                    'tree_member_ids' => [
                        'level_1' => [],
                        'level_2' => [],
                        'level_3' => [],
                        'level_4' => []
                    ]
                ]);

                Log::info("✅ USER SLOT CREATED: User {$user->username} (ID: {$user->id}) slot created with ID {$userSlot->id} and referral_relationship_id as null");

                // Check if this is the first user (admin's first referral)
                $isFirstUser = $this->isFirstUserInSystem($user->id);

                if ($isFirstUser) {
                    // First user becomes tree owner - place under admin but admin has no left/right slots
                    $this->addFirstUserToSystem($user, $levelPlan, $userSlot->id, $sponsor);
                    Log::info("✅ FIRST USER SLOT PURCHASED: User {$user->username} (ID: {$user->id}) is the first user and becomes tree owner with level {$levelPlan->level_number}");
                } else {
                    // Check if user has any existing slots (allows multiple slots)
                    $existingSlots = ReferralRelationship::where('user_id', $user->id)->count();

                    if ($existingSlots > 0) {
                        // User already has slots - place in their own tree's first empty slot
                        $this->addUserToOwnTree($user, $levelPlan, $userSlot->id);
                        Log::info("✅ MULTIPLE SLOT PURCHASED: User {$user->username} (ID: {$user->id}) bought additional slot in their own tree with level {$levelPlan->level_number}");
                    } else {
                        // New user - place under their sponsor's ACTIVE TREE (earliest non-full slot)
                        $this->addUserToTreeWithLevel($sponsor, $user, $levelPlan, $userSlot->id);
                        Log::info("✅ NEW USER SLOT PURCHASED: User {$user->username} (ID: {$user->id}) bought slot with level {$levelPlan->level_number} under sponsor {$sponsor->username} (ID: {$sponsor->id})");
                    }
                }
            });

            return redirect('buy-slot-form')->with('success', 'User created successfully! Now they can buy a level plan to join the tree.');


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
     * Check if this is the first user in the system
     * First user is the first referral of admin (sponsor_id = 1 or admin user)
     */
    private function isFirstUserInSystem($userId)
    {
        // Check if there are any existing referral relationships
        $existingRelationships = ReferralRelationship::count();

        // If no relationships exist, this is the first user
        return $existingRelationships === 0;
    }

    /**
     * Add first user to system - becomes tree owner under admin
     * Admin doesn't have left/right slots, first user is placed directly under admin
     */
    private function addFirstUserToSystem(User $user, $levelPlan, $userSlotId, User $sponsor)
    {
        // Create the referral relationship entry for first user
        // First user is placed under admin but admin has no left/right position
        $newUserEntry = [
            'user_id' => $user->id,
            'user_username' => $user->username,
            'sponsor_id' => $sponsor->id,
            'sponsor_username' => $sponsor->username,
            'upline_id' => $sponsor->id, // Admin becomes upline
            'upline_username' => $sponsor->username,
            'position' => null, // No left/right position for first user under admin
            'level_number' => $levelPlan->level_number,
            'slot_price' => $levelPlan->price,
            'level_id' => $levelPlan->id,
            'user_slots_id' => $userSlotId,
            'main_upline_id' => null // First user has no main upline
        ];

        $referralRelationship = ReferralRelationship::create($newUserEntry);

        // Update user_slots table with referral_relationship_id
        $userSlot = UserSlot::find($userSlotId);
        $userSlot->update([
            'referral_relationship_id' => $referralRelationship->id
        ]);

        // Tree owner doesn't need to store themselves in the tree structure
        // They already know it's their tree

        Log::info("✅ FIRST USER ADDED: {$user->username} (ID: {$user->id}) placed under admin {$sponsor->username} (ID: {$sponsor->id}) as tree owner - NO LEFT/RIGHT POSITION");
        Log::info("✅ USER SLOT UPDATED: User slot ID {$userSlotId} updated with referral_relationship_id {$referralRelationship->id}");
        Log::info("✅ TREE MEMBER ADDED: User {$user->username} added to their own tree at level 1");
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
     * Rebuild the tree members for a specific root (main_upline_id root relationship id)
     * This enforces per-level caps (2,4,8,16) and stores only up to those caps in user_slots.tree_member_ids
     * The root is the referral_relationship with id = $rootRelationshipId. Its user_slots_id points to the owning slot to update
     */
    private function rebuildTreeMembersForRoot(int $rootRelationshipId): void
    {
        $root = ReferralRelationship::find($rootRelationshipId);
        if (!$root) {
            return;
        }

        $slot = UserSlot::find($root->user_slots_id);
        if (!$slot) {
            return;
        }

        // Level-wise traversal constrained to this tree: main_upline_id is fixed to root id,
        // and breadth expands by upline_id user ids
        $level = 1;
        $maxPerLevel = [1 => 2, 2 => 4, 3 => 8, 4 => 16];
        $result = [
            'level_1' => [],
            'level_2' => [],
            'level_3' => [],
            'level_4' => []
        ];

        // Start from the root owner's user id
        $currentUplineUserIds = [$root->user_id];
        while ($level <= 4 && !empty($currentUplineUserIds)) {
            $nextUplineUserIds = [];

            $members = ReferralRelationship::where('main_upline_id', $rootRelationshipId)
                ->whereIn('upline_id', $currentUplineUserIds)
                ->orderBy('upline_id')
                ->orderBy('position', 'asc')
                ->get(['id', 'user_id']);

            foreach ($members as $m) {
                if (count($result['level_' . $level]) < $maxPerLevel[$level]) {
                    $result['level_' . $level][] = (int)$m->user_id;
                }
                $nextUplineUserIds[] = $m->user_id;
            }

            $currentUplineUserIds = $nextUplineUserIds;
            $level++;
        }

        // Persist on slot
        $slot->tree_member_ids = $result;
        $slot->save();
    }

    /**
     * Compute total members across 4 levels for a root (on the fly, not persisted)
     */
    private function computeFourLevelCountForRoot(int $rootRelationshipId): int
    {
        $root = ReferralRelationship::find($rootRelationshipId);
        if (!$root) {
            return 0;
        }

        $level = 1;
        $caps = [1 => 2, 2 => 4, 3 => 8, 4 => 16];
        $total = 0;
        $currentUplineUserIds = [$root->user_id];
        while ($level <= 4 && !empty($currentUplineUserIds)) {
            $members = ReferralRelationship::where('main_upline_id', $rootRelationshipId)
                ->whereIn('upline_id', $currentUplineUserIds)
                ->count();
            $total += min($members, $caps[$level]);

            // prepare next layer
            $nextUplineUserIds = ReferralRelationship::where('main_upline_id', $rootRelationshipId)
                ->whereIn('upline_id', $currentUplineUserIds)
                ->pluck('user_id')
                ->all();

            $currentUplineUserIds = $nextUplineUserIds;
            $level++;
        }

        return $total;
    }

    /**
     * Add user to their own tree (for multiple slot purchases)
     * Places user in first empty slot of their own tree
     * All members can buy multiple slots in their own tree
     */
    private function addUserToOwnTree(User $user, $levelPlan, $userSlotId)
    {
        // Find first empty slot in user's own tree
        $placement = $this->findFirstEmptySlotInOwnTree($user->id);

        // Validate that slot is not already occupied INSIDE THIS TREE (double-check for immutability)
        $this->validateSlotNotReplaced(
            $placement['upline_id'],
            $placement['position'],
            $user->id,
            $placement['upline_relationship_id'] ?? null // treat as tree root id when provided
        );

        // Determine the tree root id (main_upline_id to store). Prefer placement's root when available
        $mainUplineId = $placement['upline_relationship_id'] ?? $this->getMainUplineId($placement['upline_id']);

        // Create the referral relationship entry with user_slots_id
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
            'level_id' => $levelPlan->id,
            'user_slots_id' => $userSlotId,
            'main_upline_id' => $mainUplineId
        ];

        $referralRelationship = ReferralRelationship::create($newUserEntry);

        // Update user_slots table with referral_relationship_id
        $userSlot = UserSlot::find($userSlotId);
        $userSlot->update([
            'referral_relationship_id' => $referralRelationship->id
        ]);

        // Calculate the level based on the upline's position in the tree
        $level = $this->calculateTreeLevel($placement['upline_id'], $user->id);

        // Rebuild tree members for this root to keep per-level caps correct (2/4/8/16)
        if ($mainUplineId) {
            $this->rebuildTreeMembersForRoot($mainUplineId);
        }

        // Add this user to ALL relevant users' tree structures (legacy per-user view)
        $this->addMemberToAllRelevantTrees($user->id, $level, $placement['upline_id']);

        Log::info("✅ USER ADDED TO OWN TREE: {$user->username} (ID: {$user->id}) placed under {$placement['upline_username']} (ID: {$placement['upline_id']}) Position: {$placement['position']} in their own tree - SLOT PERMANENTLY ASSIGNED");
        Log::info("✅ USER SLOT UPDATED: User slot ID {$userSlotId} updated with referral_relationship_id {$referralRelationship->id}");
        Log::info("✅ TREE MEMBER ADDED: User {$user->username} added to tree at level {$level}");
    }


    /**
     * Get the main upline ID (referral_relationships table primary ID)
     * This is the primary ID of the upline user's referral relationship entry
     */
    private function getMainUplineId($uplineUserId)
    {
        // Find the referral relationship entry for the upline user
        // We need to get the most recent entry for this user (their latest slot)
        $uplineEntry = ReferralRelationship::where('user_id', $uplineUserId)
            ->orderBy('id', 'desc') // Get the most recent entry
            ->first();

        if ($uplineEntry) {
            return $uplineEntry->id; // Return the primary ID of the upline's entry
        }

        // If no upline entry found, return null
        return null;
    }

    /**
     * Calculate the tree level for a user based on their upline
     * Tree owner is not stored in tree structure, so levels start from 1
     * 
     * @param int $uplineUserId The user ID of the upline
     * @param int $currentUserId The user ID of the current user
     * @return int The calculated level (1-4)
     */
    private function calculateTreeLevel($uplineUserId, $currentUserId)
    {
        // If upline has no upline_id, they are the root (first user)
        $uplineEntry = ReferralRelationship::where('user_id', $uplineUserId)
            ->orderBy('id', 'desc')
            ->first();

        if ($uplineEntry && !$uplineEntry->upline_id) {
            return 1; // Direct under root is level 1
        }

        // Find the upline's level by checking their position in the tree
        $uplineEntry = ReferralRelationship::where('user_id', $uplineUserId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$uplineEntry) {
            return 1; // Default to level 1 if upline not found
        }

        // Calculate level based on upline's level + 1
        $level = $this->getUserLevelInTree($uplineUserId) + 1;

        // Ensure level doesn't exceed 4
        return min($level, 4);
    }

    /**
     * Get the level of a user in the tree
     * Tree owner is not stored in tree structure, so levels start from 0 for tree owner
     * 
     * @param int $userId The user ID
     * @return int The level (0-3, where 0 = tree owner)
     */
    private function getUserLevelInTree($userId)
    {
        // Find the user's entry
        $userEntry = ReferralRelationship::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$userEntry) {
            return 0; // Default to level 0
        }

        // Check if this is the first user (no upline_id means they are the root)
        if (!$userEntry->upline_id) {
            return 0;
        }

        // Count the levels by traversing up the tree
        $level = 0;
        $currentUplineId = $userEntry->upline_id;

        while ($currentUplineId && $level < 3) {
            $uplineEntry = ReferralRelationship::where('user_id', $currentUplineId)
                ->orderBy('id', 'desc')
                ->first();

            if (!$uplineEntry) {
                break;
            }

            $level++;
            $currentUplineId = $uplineEntry->upline_id;
        }

        return $level;
    }


    /**
     * Add a member to ALL relevant users' tree structures
     * Each user maintains their own tree structure showing their direct and indirect referrals
     * 
     * @param int $newUserId The new user ID being added
     * @param int $level The level in the main tree
     * @param int $uplineUserId The upline user ID
     */
    private function addMemberToAllRelevantTrees($newUserId, $level, $uplineUserId)
    {
        // 1. Add to direct upline's structure (level 1 for upline)
        $this->addMemberToUserTree($uplineUserId, $newUserId, 1);

        // 2. Add to all upline chain users (each sees it at their appropriate level)
        $this->addMemberToUplineChain($newUserId, $uplineUserId, $level);
    }

    /**
     * Add a member to a specific user's tree structure
     * 
     * @param int $userId The user ID whose tree to add to
     * @param int $memberUserId The member user ID to add
     * @param int $level The level in that user's tree
     */
    private function addMemberToUserTree($userId, $memberUserId, $level)
    {
        // Find all user slots for this user
        $userSlots = UserSlot::where('user_id', $userId)->get();

        foreach ($userSlots as $userSlot) {
            // Add member to this user's tree structure (excluding themselves)
            $userSlot->addTreeMemberExcludingSelf($memberUserId, $level, $userId);
            Log::info("✅ USER TREE MEMBER ADDED: User {$memberUserId} added to user {$userId}'s tree at level {$level} in slot {$userSlot->id}");
        }
    }

    /**
     * Add member to all users in the upline chain
     * Each user sees the new member at their appropriate level
     * 
     * @param int $newUserId The new user ID
     * @param int $uplineUserId The direct upline user ID
     * @param int $mainLevel The level in the main tree
     */
    private function addMemberToUplineChain($newUserId, $uplineUserId, $mainLevel)
    {
        $currentUplineId = $uplineUserId;
        $levelInUplineTree = 2; // Start at level 2 for upline's upline

        // Traverse up the upline chain
        while ($currentUplineId && $levelInUplineTree <= 4) {
            // Find the next upline
            $currentUpline = ReferralRelationship::where('user_id', $currentUplineId)
                ->orderBy('id', 'desc')
                ->first();

            if (!$currentUpline || !$currentUpline->upline_id) {
                break;
            }

            $nextUplineId = $currentUpline->upline_id;

            // Add to next upline's tree at the calculated level
            $this->addMemberToUserTree($nextUplineId, $newUserId, $levelInUplineTree);

            $currentUplineId = $nextUplineId;
            $levelInUplineTree++;
        }
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

        // Always target earliest NOT-FULL round (member_count < 30) if available,
        // otherwise default to newest tree
        // Choose earliest root whose current computed 4-level member total is < 30, else newest
        $roots = ReferralRelationship::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();

        $targetRoot = null;
        foreach ($roots as $root) {
            $total = $this->computeFourLevelCountForRoot($root->id);
            if ($total < 30) {
                $targetRoot = $root;
                break;
            }
        }
        if (!$targetRoot) {
            $targetRoot = ReferralRelationship::where('user_id', $userId)
                ->orderBy('id', 'desc')
                ->first();
        }

        // Self-purchase rule: do NOT place under the root user's own L/R.
        // Start BFS from level-2 candidates: get level-1 children first, then traverse their subtrees.
        $level1 = ReferralRelationship::where('main_upline_id', $targetRoot->id)
            ->where('upline_id', $userId)
            ->orderBy('position', 'asc')
            ->get(['id','user_id']);

        // If no level 1 yet in this round, allow direct placement under root user's L then R
        if ($level1->isEmpty()) {
            if ($this->isSlotAvailableInTree($userId, 'L', $targetRoot->id)) {
                return [
                    'upline_id' => $userId,
                    'upline_username' => $user->username,
                    'position' => 'L',
                    'upline_relationship_id' => $targetRoot->id
                ];
            }
            if ($this->isSlotAvailableInTree($userId, 'R', $targetRoot->id)) {
                return [
                    'upline_id' => $userId,
                    'upline_username' => $user->username,
                    'position' => 'R',
                    'upline_relationship_id' => $targetRoot->id
                ];
            }
        }

        // BFS within the chosen root restricted by main_upline_id = root id, starting from level-1 children
        $queue = $level1->pluck('id')->all();
        $visited = [];

        while (!empty($queue)) {
            $currentRelId = array_shift($queue);

            if (in_array($currentRelId, $visited)) {
                continue;
            }
            $visited[] = $currentRelId;

            // Get the actual user_id for this relationship row
            $rel = ReferralRelationship::find($currentRelId);
            if (!$rel) {
                continue;
            }

            // Check if this user has available slots (left-right priority) WITHIN THIS TREE
            $availableSlot = null;
            if ($this->isSlotAvailableInTree($rel->user_id, 'L', $targetRoot->id)) {
                $availableSlot = [
                    'upline_id' => $rel->user_id,
                    'upline_username' => User::find($rel->user_id)->username,
                    'position' => 'L',
                    'upline_relationship_id' => $targetRoot->id
                ];
            } elseif ($this->isSlotAvailableInTree($rel->user_id, 'R', $targetRoot->id)) {
                $availableSlot = [
                    'upline_id' => $rel->user_id,
                    'upline_username' => User::find($rel->user_id)->username,
                    'position' => 'R',
                    'upline_relationship_id' => $targetRoot->id
                ];
            }
            if ($availableSlot) {
                return $availableSlot;
            }

            // Both slots are filled, add children relationships of THIS ROOT to queue for next level
            $children = ReferralRelationship::where('main_upline_id', $targetRoot->id)
                ->where('upline_id', $rel->user_id)
                ->orderBy('position', 'asc')
                ->get(['id','user_id']);

            // If none found in this root (legacy rows), also look for classic children to skip to next in global tree
            if ($children->isEmpty()) {
                $children = ReferralRelationship::whereNull('main_upline_id')
                    ->where('upline_id', $rel->user_id)
                    ->orderBy('position', 'asc')
                    ->get(['id','user_id']);
            }

            foreach ($children as $child) {
                $queue[] = $child->id; // traverse by relationship id within same tree
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
    private function addUserToTreeWithLevel(User $sponsor, User $newUser, $levelPlan, $userSlotId)
    {
        // Log the start of adding user to tree
        Log::info("🔄 API ADDING USER WITH LEVEL: {$newUser->username} (ID: {$newUser->id}) sponsored by {$sponsor->username} (ID: {$sponsor->id}) with level {$levelPlan->level_number}");

        // Use DB transaction to ensure atomicity
        DB::transaction(function () use ($sponsor, $newUser, $levelPlan, $userSlotId) {
            // Step 1: Find first empty slot in SPONSOR'S ACTIVE TREE (latest slot subtree)
            $placement = $this->findFirstEmptySlotInSponsorActiveTree($sponsor->id);

            // Step 2: Validate that slot is not already occupied WITHIN THIS TREE (immutability check)
            $this->validateSlotNotReplaced($placement['upline_id'], $placement['position'], $newUser->id, $placement['upline_relationship_id'] ?? null);

            // Get the main upline ID within the ACTIVE TREE (referral_relationships primary ID for the chosen upline relationship)
            $mainUplineId = $placement['upline_relationship_id'] ?? $this->getMainUplineId($placement['upline_id']);

            // Step 3: Insert the new user entry with level plan information and user_slots_id
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
                'level_id' => $levelPlan->id,
                'user_slots_id' => $userSlotId,
                'main_upline_id' => $mainUplineId
            ];

            $referralRelationship = ReferralRelationship::create($newUserEntry);

            // Step 4: Update user_slots table with referral_relationship_id
            $userSlot = UserSlot::find($userSlotId);
            $userSlot->update([
                'referral_relationship_id' => $referralRelationship->id
            ]);

            // Calculate the level based on the upline's position in the tree
            $level = $this->calculateTreeLevel($placement['upline_id'], $newUser->id);

            // Rebuild tree members for this root to keep per-level caps correct (2/4/8/16)
            if ($mainUplineId) {
                $this->rebuildTreeMembersForRoot($mainUplineId);
            }

            // Add this user to ALL relevant users' tree structures (legacy per-user view)
            $this->addMemberToAllRelevantTrees($newUser->id, $level, $placement['upline_id']);

            // Log the successful creation of new user entry
            Log::info("✅ API USER ENTRY CREATED: {$newUser->username} (ID: {$newUser->id}) placed under {$placement['upline_username']} (ID: {$placement['upline_id']}) Position: {$placement['position']} with Level {$levelPlan->level_number} - SLOT PERMANENTLY ASSIGNED");
            Log::info("✅ USER SLOT UPDATED: User slot ID {$userSlotId} updated with referral_relationship_id {$referralRelationship->id}");
            Log::info("✅ TREE MEMBER ADDED: User {$newUser->username} added to tree at level {$level}");
        });
    }


    /**
     * Find first empty slot in sponsor's ACTIVE tree (latest referral relationship root) using tree-aware BFS
     */
    private function findFirstEmptySlotInSponsorActiveTree($sponsorId)
    {
        $sponsor = User::find($sponsorId);
        if (!$sponsor) {
            throw new \Exception("Sponsor not found: {$sponsorId}");
        }

        // Active root: latest referral_relationship for sponsor (their newest slot/tree root)
        $root = ReferralRelationship::where('user_id', $sponsorId)
            ->orderBy('id', 'desc')
            ->first();

        // If sponsor has no relationship yet, use immediate L/R check under sponsor user id
        if (!$root) {
            if ($this->isSlotAvailable($sponsorId, 'L')) {
                return [
                    'upline_id' => $sponsorId,
                    'upline_username' => $sponsor->username,
                    'position' => 'L',
                    'upline_relationship_id' => null
                ];
            }
            if ($this->isSlotAvailable($sponsorId, 'R')) {
                return [
                    'upline_id' => $sponsorId,
                    'upline_username' => $sponsor->username,
                    'position' => 'R',
                    'upline_relationship_id' => null
                ];
            }

            // Fallback
            return [
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'L',
                'upline_relationship_id' => null
            ];
        }

        // Check root's immediate slots within this tree (by relationship id)
        if ($this->isTreeSlotAvailable($root->id, 'L')) {
            return [
                'upline_id' => $root->user_id,
                'upline_username' => $root->user_username,
                'position' => 'L',
                'upline_relationship_id' => $root->id
            ];
        }
        if ($this->isTreeSlotAvailable($root->id, 'R')) {
            return [
                'upline_id' => $root->user_id,
                'upline_username' => $root->user_username,
                'position' => 'R',
                'upline_relationship_id' => $root->id
            ];
        }

        // Tree-aware BFS: traverse by main_upline_id chain inside this tree only
        $queue = [$root->id]; // queue holds relationship IDs
        $visited = [];

        while (!empty($queue)) {
            $currentRelId = array_shift($queue);
            if (in_array($currentRelId, $visited)) {
                continue;
            }
            $visited[] = $currentRelId;

            // Check L then R availability under this relationship
            if ($this->isTreeSlotAvailable($currentRelId, 'L')) {
                $upline = ReferralRelationship::find($currentRelId);
                return [
                    'upline_id' => $upline->user_id,
                    'upline_username' => $upline->user_username,
                    'position' => 'L',
                    'upline_relationship_id' => $currentRelId
                ];
            }
            if ($this->isTreeSlotAvailable($currentRelId, 'R')) {
                $upline = ReferralRelationship::find($currentRelId);
                return [
                    'upline_id' => $upline->user_id,
                    'upline_username' => $upline->user_username,
                    'position' => 'R',
                    'upline_relationship_id' => $currentRelId
                ];
            }

            // Enqueue children relationships (within this tree) in left-right order
            $children = ReferralRelationship::where('main_upline_id', $currentRelId)
                ->orderBy('position', 'asc')
                ->get();

            foreach ($children as $child) {
                if (!in_array($child->id, $visited)) {
                    $queue[] = $child->id;
                }
            }
        }

        // Active tree likely full (30 complete). Fallback to global left-right BFS under sponsor (legacy behavior)
        return $this->findFirstEmptySlotInSponsorSubtreeLegacy($sponsorId);
    }

    /**
     * Tree-aware slot availability: checks if a given relationship's L/R child exists
     */
    private function isTreeSlotAvailable($uplineRelationshipId, $position)
    {
        $existing = ReferralRelationship::where('main_upline_id', $uplineRelationshipId)
            ->where('position', $position)
            ->first();
        return !$existing;
    }

    /**
     * Legacy global BFS under sponsor by upline_id only (ignores main_upline_id tree constraint)
     * Places user in first available left-right slot anywhere under sponsor
     */
    private function findFirstEmptySlotInSponsorSubtreeLegacy($sponsorId)
    {
        $sponsor = User::find($sponsorId);
        if (!$sponsor) {
            throw new \Exception("Sponsor not found: {$sponsorId}");
        }

        if ($this->isSlotAvailable($sponsorId, 'L')) {
            return [
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'L',
                'upline_relationship_id' => null
            ];
        }
        if ($this->isSlotAvailable($sponsorId, 'R')) {
            return [
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'R',
                'upline_relationship_id' => null
            ];
        }

        $queue = [$sponsorId];
        $visited = [];
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            if (in_array($currentId, $visited)) {
                continue;
            }
            $visited[] = $currentId;

            $availableSlot = $this->findFirstAvailableSlotForUpline($currentId);
            if ($availableSlot) {
                $availableSlot['upline_relationship_id'] = null; // legacy path
                return $availableSlot;
            }

            $children = ReferralRelationship::where('upline_id', $currentId)
                ->orderBy('position', 'asc')
                ->get();
            foreach ($children as $child) {
                if (!in_array($child->user_id, $visited)) {
                    $queue[] = $child->user_id;
                }
            }
        }

        // As a last resort, return sponsor left (will be blocked by validator if occupied)
        return [
            'upline_id' => $sponsorId,
            'upline_username' => $sponsor->username,
            'position' => 'L',
            'upline_relationship_id' => null
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
     * Check availability within a specific tree context (by root relationship id)
     */
    private function isSlotAvailableInTree($uplineId, $position, $rootRelationshipId)
    {
        // Prefer strict check inside this tree
        $existingUser = ReferralRelationship::where('main_upline_id', $rootRelationshipId)
            ->where('upline_id', $uplineId)
            ->where('position', $position)
            ->first();

        if ($existingUser) {
            return false;
        }

        // Safety: if legacy rows missed main_upline_id, still consider globally occupied
        $globalExisting = ReferralRelationship::whereNull('main_upline_id')
            ->where('upline_id', $uplineId)
            ->where('position', $position)
            ->first();

        return !$globalExisting;
    }

    /**
     * Validate that user is not trying to replace an existing slot
     * Slots are immutable once assigned
     */
    private function validateSlotNotReplaced($uplineId, $position, $userId, $uplineRelationshipId = null)
    {
        // When a tree context is provided, enforce immutability within that specific tree and upline
        if ($uplineRelationshipId) {
            $existingSlot = ReferralRelationship::where('main_upline_id', $uplineRelationshipId)
                ->where('upline_id', $uplineId)
                ->where('position', $position)
                ->first();
        } else {
            // Fallback to global check by upline user id (legacy flows not tree-aware)
            $existingSlot = ReferralRelationship::where('upline_id', $uplineId)
                ->where('position', $position)
                ->first();
        }

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


    /**
     * Display tree by user slot (round-based)
     * Web route: GET /tree-display/{username}/{round}
     * 
     * @param string $username
     * @param int $round
     * @return \Illuminate\View\View
     */
    public function displayTree($username, $round = 1)
    {
        try {
            // Find the user
            $user = User::where('username', $username)->first();
            if (!$user) {
                return view('errors.404')->with('message', 'User not found');
            }

            // Get all user slots for this user with their level plans
            $userSlots = UserSlot::where('user_id', $user->id)
                ->with('levelPlan')
                ->orderBy('id', 'asc')
                ->get();

            if ($userSlots->isEmpty()) {
                return view('errors.404')->with('message', 'No slots found for user ' . $username);
            }

            // Get available rounds (slot prices)
            $availableRounds = [];
            foreach ($userSlots as $slot) {
                if ($slot->levelPlan) {
                    $availableRounds[] = [
                        'round' => $slot->id,
                        'slot_price' => $slot->levelPlan->price,
                        'level_name' => $slot->levelPlan->name,
                        'level_number' => $slot->levelPlan->level_number
                    ];
                }
            }

            // Find the user slot for the specified round
            $userSlot = $userSlots->where('id', $round)->first();

            if (!$userSlot) {
                // If specific round not found, use the first available slot
                $userSlot = $userSlots->first();
                $round = $userSlot->id;
            }

            // Get the referral relationship for this slot
            $referralRelationship = ReferralRelationship::where('user_slots_id', $userSlot->id)->first();

            if (!$referralRelationship) {
                return view('errors.404')->with('message', 'No tree data found for this slot');
            }

            // Get tree structure using main_upline_id for 4 levels
            $treeData = $this->getTreeStructureByMainUplineId($referralRelationship->id, 4);
            
            // Debug: Log the tree data
            Log::info('Tree Data for ' . $user->username . ' (Round ' . $round . '):', $treeData);
            
            // Build tree structure for display
            $treeStructure = $this->buildTreeStructure($treeData, $referralRelationship);
            
            // Debug: Log the tree structure
            Log::info('Tree Structure for ' . $user->username . ' (Round ' . $round . '):', $treeStructure);

            // Calculate slot price-based statistics
            $slotPriceStats = $this->calculateSlotPriceStats($treeData, $userSlot->levelPlan->price);

            return view('tree-display', [
                'user' => $user,
                'userSlot' => $userSlot,
                'referralRelationship' => $referralRelationship,
                'treeData' => $treeData,
                'treeStructure' => $treeStructure,
                'round' => $round,
                'availableRounds' => $availableRounds,
                'slotPriceStats' => $slotPriceStats
            ]);

        } catch (\Exception $e) {
            Log::error('Error displaying tree: ' . $e->getMessage());
            return view('errors.500')->with('message', 'Error displaying tree: ' . $e->getMessage());
        }
    }

    /**
     * Calculate slot price-based statistics
     * 
     * @param array $treeData
     * @param float $currentSlotPrice
     * @return array
     */
    private function calculateSlotPriceStats($treeData, $currentSlotPrice)
    {
        $stats = [
            'current_slot_price' => $currentSlotPrice,
            'total_members' => 0,
            'members_by_price' => [],
            'level_breakdown' => []
        ];

        // Get all slot prices from level plans
        $levelPlans = LevelPlan::active()->get();
        $slotPrices = $levelPlans->pluck('price')->unique()->sort()->values();

        // Initialize price groups
        foreach ($slotPrices as $price) {
            $stats['members_by_price'][$price] = [
                'count' => 0,
                'members' => []
            ];
        }

        // Count members by slot price for each level
        for ($level = 1; $level <= 4; $level++) {
            $levelCount = 0;
            $levelMembers = [];

            if (isset($treeData[$level]['members'])) {
                foreach ($treeData[$level]['members'] as $member) {
                    $levelCount++;
                    $stats['total_members']++;
                    
                    // Get slot price from member data
                    $memberSlotPrice = $member['slot_price'] ?? $currentSlotPrice;
                    
                    if (isset($stats['members_by_price'][$memberSlotPrice])) {
                        $stats['members_by_price'][$memberSlotPrice]['count']++;
                        $stats['members_by_price'][$memberSlotPrice]['members'][] = $member['username'];
                    }

                    $levelMembers[] = $member['username'];
                }
            }

            $stats['level_breakdown']["level_{$level}"] = [
                'count' => $levelCount,
                'members' => implode(', ', $levelMembers)
            ];
        }

        return $stats;
    }

    /**
     * Get tree structure using main_upline_id for specified levels
     * 
     * @param int $mainUplineId
     * @param int $maxLevels
     * @return array
     */
    private function getTreeStructureByMainUplineId($mainUplineId, $maxLevels = 4)
    {
        $levelData = [];
        // Traverse by user ids per level within fixed main_upline_id (the tree root)
        $root = ReferralRelationship::find($mainUplineId);
        if (!$root) {
            return $levelData;
        }

        $currentUplineUserIds = [$root->user_id];
        $level = 1;
        $anyFound = false;
        while ($level <= $maxLevels && !empty($currentUplineUserIds)) {
            $nextUplineUserIds = [];
            $levelMembers = [];

            // Collect children per-parent to allow fallback if some rows missed main_upline_id
            foreach ($currentUplineUserIds as $parentUserId) {
                $rows = ReferralRelationship::where('main_upline_id', $mainUplineId)
                    ->where('upline_id', $parentUserId)
                    ->orderBy('position', 'asc')
                    ->get();

                // Fallback: if none found with main_upline_id for this parent, try classic by upline only
                if ($rows->isEmpty()) {
                    $rows = ReferralRelationship::where('upline_id', $parentUserId)
                        ->orderBy('position', 'asc')
                        ->get();
                }

                foreach ($rows as $child) {
                    $nextUplineUserIds[] = $child->user_id;
                    $levelMembers[] = [
                        'user_id' => $child->user_id,
                        'username' => $child->user_username,
                        'position' => $child->position,
                        'upline_id' => $child->upline_id,
                        'upline_username' => $child->upline_username,
                        'relationship_id' => $child->id,
                        'level_number' => $child->level_number,
                        'slot_price' => $child->slot_price
                    ];
                }
            }

            $levelData[$level] = [
                'count' => count($levelMembers),
                'members' => $levelMembers
            ];

            if (count($levelMembers) > 0) {
                $anyFound = true;
            }
            $currentUplineUserIds = $nextUplineUserIds;
            $level++;
        }

        // Fallback: if no members were found under this root (data may not have main_upline_id filled),
        // attempt classic traversal without main_upline_id constraint so at least something renders.
        if (!$anyFound) {
            $levelData = [];
            $current = [$root->user_id];
            $level = 1;
            while ($level <= $maxLevels && !empty($current)) {
                $next = [];
                $members = ReferralRelationship::whereIn('upline_id', $current)
                    ->orderBy('upline_id')
                    ->orderBy('position', 'asc')
                    ->get();

                $mapped = [];
                foreach ($members as $child) {
                    $next[] = $child->user_id;
                    $mapped[] = [
                        'user_id' => $child->user_id,
                        'username' => $child->user_username,
                        'position' => $child->position,
                        'upline_id' => $child->upline_id,
                        'upline_username' => $child->upline_username,
                        'relationship_id' => $child->id,
                        'level_number' => $child->level_number,
                        'slot_price' => $child->slot_price
                    ];
                }

                $levelData[$level] = [
                    'count' => count($mapped),
                    'members' => $mapped
                ];

                $current = $next;
                $level++;
            }
        }

        // Fill remaining levels with empty data if we didn't reach max levels
        for ($i = $level; $i <= $maxLevels; $i++) {
            $levelData[$i] = [
                'count' => 0,
                'members' => []
            ];
        }

        return $levelData;
    }

    /**
     * Build tree structure for display
     * 
     * @param array $treeData
     * @param object $referralRelationship
     * @return array
     */
    public function buildTreeStructure($treeData, $referralRelationship)
    {
        $treeStructure = [];
        
        // Add members from each level (level 1 = direct children, level 2 = grandchildren, etc.)
        for ($level = 1; $level <= 4; $level++) {
            if (isset($treeData[$level]['members']) && count($treeData[$level]['members']) > 0) {
                $treeStructure[$level] = $treeData[$level]['members'];
            } else {
                // Empty level
                $treeStructure[$level] = [];
            }
        }
        
        return $treeStructure;
    }

    /**
     * Get tree statistics
     * API endpoint: GET /api/tree-stats/{username}
     * 
     * @param string $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTreeStats($username)
    {
        try {
            $user = User::where('username', $username)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get user's slots
            $userSlots = UserSlot::where('user_id', $user->id)->get();

            $stats = [
                'total_slots' => $userSlots->count(),
                'slots' => []
            ];

            foreach ($userSlots as $slot) {
                $levelPlan = $slot->levelPlan;
                $referralRelationship = $slot->referralRelationship;

                $stats['slots'][] = [
                    'slot_id' => $slot->id,
                    'username' => $slot->username, // Username from user_slots table
                    'level_plan' => $levelPlan ? [
                        'id' => $levelPlan->id,
                        'level_number' => $levelPlan->level_number,
                        'price' => $levelPlan->price
                    ] : null,
                    'referral_relationship' => $referralRelationship ? [
                        'id' => $referralRelationship->id,
                        'position' => $referralRelationship->position,
                        'upline_username' => $referralRelationship->upline_username
                    ] : null,
                    'tree_member_count' => $slot->getAllTreeMembers() ? count($slot->getAllTreeMembers()) : 0
                ];
            }

            return response()->json([
                'success' => true,
                'message' => "Tree stats retrieved for {$username}",
                'data' => $stats
            ], 200);
        } catch (\Exception $e) {
            Log::error('API Error getting tree stats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving tree stats: ' . $e->getMessage()
            ], 500);
        }
    }
}

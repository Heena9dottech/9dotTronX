<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ReferralRelationship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TreeController extends Controller
{
    // Add new user to tree with proper binary MLM logic
    public function addUser(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username',
            'sponsor_id' => 'nullable|exists:users,id'
        ]);

        DB::transaction(function () use ($request) {
            // 1️⃣ Create the user
            $user = User::create([
                'username' => $request->username,
                'email' => $request->username . '@mlm.com', // auto-generate email
                'password' => Hash::make('123456'),
                'sponsor_id' => $request->sponsor_id
            ]);

            // 2️⃣ Check if sponsor is provided
            if ($request->sponsor_id) {
                $sponsor = User::find($request->sponsor_id);
                $this->addUserToTree($sponsor, $user);
            } else {
                // Tree owner (no sponsor) - create as root
                ReferralRelationship::create([
                    'user_id' => $user->id,
                    'user_username' => $user->username,
                    'sponsor_id' => null,
                    'sponsor_username' => null,
                    'upline_id' => null,
                    'upline_username' => null,
                    'position' => null,
                    'tree_owner_id' => $user->id,
                    'tree_owner_username' => $user->username,
                    'tree_round' => 1,
                    'is_spillover_slot' => false,
                ]);
            }
        });

        return redirect('add-user-form')->with('success', 'User added to binary MLM tree');
    }

    /**
     * Add user to tree with correct 30th member flow
     * Implements the exact MLM logic from the requirements:
     * - Normal tree filling (breadth-first, left-to-right)
     * - When 30th member is added, create new tree entry for owner
     * - New tree entry goes to first available slot in inviter's tree
     */
    public function addUserToTree(User $sponsor, User $newUser)
    {
        // Log the start of adding user to tree
        Log::info("🔄 ADDING USER: {$newUser->username} (ID: {$newUser->id}) sponsored by {$sponsor->username} (ID: {$sponsor->id})");
        
        // Use DB transaction to ensure atomicity
        DB::transaction(function () use ($sponsor, $newUser) {
            // Step 1: Determine tree owner
            $treeOwner = $this->findTreeOwner($sponsor);
            
            // Step 2: Determine which round to add the new user to
            $targetRound = $this->getCurrentActiveRound($treeOwner->id);
            
            // Step 3: Find first empty slot in sponsor's subtree (BFS: sponsor.left, sponsor.right, then children)
            $placement = $this->findFirstEmptySlotInSponsorSubtree($sponsor->id, $treeOwner->id, $targetRound);
            
            // Step 4: Check count BEFORE insertion
            $countBefore = $this->countTreeMembersInRound($treeOwner->id, $targetRound);
            
            // Debug log before insertion
            Log::info("DEBUG: Before adding {$newUser->username}, {$treeOwner->username}'s tree has {$countBefore} regular members");
            
            // Step 5: Insert the new user entry first
            $newUserEntry = [
                'user_id' => $newUser->id,
                'user_username' => $newUser->username,
                'sponsor_id' => $sponsor->id,
                'sponsor_username' => $sponsor->username,
                'upline_id' => $placement['upline_id'],
                'upline_username' => $placement['upline_username'],
                'position' => $placement['position'],
                'tree_owner_id' => $treeOwner->id,
                'tree_owner_username' => $treeOwner->username,
                'tree_round' => $targetRound,
                'is_spillover_slot' => false,
            ];
            
            ReferralRelationship::create($newUserEntry);
            
            // Log the successful creation of new user entry
            Log::info("✅ USER ENTRY CREATED: {$newUser->username} (ID: {$newUser->id}) placed under {$placement['upline_username']} (ID: {$placement['upline_id']}) Position: {$placement['position']} in {$treeOwner->username}'s tree");
            
            // Step 6: After insertion, check if we just reached 30 members
            $countAfter = $this->countTreeMembersInRound($treeOwner->id, $targetRound);
            
            // Log the count after adding new user
            Log::info("User count after adding {$newUser->username}: {$countAfter} regular members in {$treeOwner->username}'s tree");
            
            // Step 7: If we just reached 30 members, create owner's new tree entry
            if ($countAfter == 30) {
                Log::info("🎯 TREE COMPLETED: {$treeOwner->username}'s tree reached 30 members in round {$targetRound}! Creating new tree entry...");
                $this->createNewTreeEntryForOwner($treeOwner);
            } else {
                Log::info("❌ TREE NOT COMPLETED: {$treeOwner->username}'s tree has {$countAfter} members in round {$targetRound} (need 30 for new tree entry)");
            }
        });
    }

    /**
     * Find proper binary tree placement for new user
     * Implements: Left-to-right, top-to-bottom placement with 30-member tree spillover
     */
    public function findBinaryTreePlacement(User $sponsor, User $newUser)
    {
        // Find the tree owner for this sponsor 
        //JOhn
        $treeOwner = $this->findTreeOwner($sponsor);
        
        // Check if tree owner's current round tree will be full after adding this person (30 members)
        $currentRoundMembers = $this->countTreeMembersInRound($treeOwner->id, 1);
        $willBeFull = ($currentRoundMembers >= 29); // 29 + 1 new person = 30
        
        if ($willBeFull) {
            // Tree will be full after adding this person (30th person)
            // STEP 1: Place the new person (30th user) first in the tree owner's current round
            $placement = $this->findFirstEmptySlotInTree($treeOwner->id, 1);
            
            // Create entry for new person in tree owner's tree
            $newPersonEntry = [
                'user_id' => $newUser->id,
                'user_username' => $newUser->username,
                'sponsor_id' => $sponsor->id,
                'sponsor_username' => $sponsor->username,
                'upline_id' => $placement['upline_id'],
                'upline_username' => $placement['upline_username'],
                'position' => $placement['position'],
                'tree_owner_id' => $treeOwner->id,
                'tree_owner_username' => $treeOwner->username,
                'tree_round' => 1,
                'is_spillover_slot' => false,
            ];
            
            // STEP 2: After creating the 30th user, create tree owner's spillover slot
            // This will find the NEXT available empty slot (not the same as 30th user)
            $this->createSpilloverSlotAfterUser($treeOwner, $placement);
            
            return $newPersonEntry;
        } else {
            // Tree has space, find first empty slot in sponsor's tree
            $placement = $this->findFirstEmptySlotInTree($treeOwner->id, 1);
            
            return [
                'user_id' => $newUser->id,
                'user_username' => $newUser->username,
                'sponsor_id' => $sponsor->id,
                'sponsor_username' => $sponsor->username,
                'upline_id' => $placement['upline_id'],
                'upline_username' => $placement['upline_username'],
                'position' => $placement['position'],
                'tree_owner_id' => $treeOwner->id,
                'tree_owner_username' => $treeOwner->username,
                'tree_round' => 1,
                'is_spillover_slot' => false,
            ];
        }
    }
    
    /**
     * Find the tree owner for a given user
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
     * Returns the round number where new users should be added
     * If spillover slot exists, new users go to the same round as spillover slot
     */
    private function getCurrentActiveRound($treeOwnerId)
    {
        // Check if tree owner has a spillover slot
        $spilloverSlot = ReferralRelationship::where('user_id', $treeOwnerId)
            ->where('is_spillover_slot', true)
            ->first();
            
        if ($spilloverSlot) {
            // If spillover slot exists, new users go to the same round as spillover slot
            Log::info("DEBUG: Tree owner {$treeOwnerId} has spillover slot in round {$spilloverSlot->tree_round}");
            return $spilloverSlot->tree_round;
        }
        
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
        
        // Check each round to find the first one that's not full (less than 30 members)
        foreach ($rounds as $round) {
            $memberCount = $this->countTreeMembersInRound($treeOwnerId, $round);
            if ($memberCount < 30) {
                Log::info("DEBUG: Tree owner {$treeOwnerId} has space in round {$round} ({$memberCount} members)");
                return $round; // This round has space
            }
        }
        
        // All existing rounds are full, create new round
        $newRound = max($rounds) + 1;
        Log::info("DEBUG: All rounds full for tree owner {$treeOwnerId}, creating new round {$newRound}");
        return $newRound;
    }
    
    /**
     * Count members in specific round for a tree owner
     * Excludes spillover slots and the tree owner themselves from the count
     * Only counts actual downline members (30 max per round)
     */
    private function countTreeMembersInRound($treeOwnerId, $round)
    {
        $count = ReferralRelationship::where('tree_owner_id', $treeOwnerId)
            ->where('tree_round', $round)
            ->where('is_spillover_slot', false)
            ->where('user_id', '!=', $treeOwnerId) // Exclude tree owner themselves
            ->count();
            
        // Debug log
        Log::info("DEBUG: countTreeMembersInRound for tree_owner_id={$treeOwnerId}, round={$round}, count={$count} (excluding tree owner)");
        
        return $count;
    }

    /**
     * Count total members in specific round for a tree owner (including spillover slots)
     */
    private function countTotalTreeMembersInRound($treeOwnerId, $round)
    {
        $count = ReferralRelationship::where('tree_owner_id', $treeOwnerId)
            ->where('tree_round', $round)
            ->count();
            
        // Debug log
        Log::info("DEBUG: countTotalTreeMembersInRound for tree_owner_id={$treeOwnerId}, round={$round}, count={$count}");
        
        return $count;
    }

    /**
     * Get all rounds for a tree owner
     * Includes spillover slots in the rounds
     */
    private function getTreeOwnerRounds($treeOwnerId)
    {
        return ReferralRelationship::where('tree_owner_id', $treeOwnerId)
            ->select('tree_round')
            ->distinct()
            ->orderBy('tree_round', 'asc')
            ->pluck('tree_round')
            ->toArray();
    }
    
    /**
     * Create new tree entry for owner when their tree reaches 30 members
     * Places owner in first available empty slot in their OWN tree
     * This implements the exact requirement: "New tree entry for the owner who completed 30 members"
     */
    private function createNewTreeEntryForOwner(User $treeOwner)
    {
        // Find first available empty slot in the tree owner's OWN tree
        $placement = $this->findFirstEmptySlotInTree($treeOwner->id, 1);
        
        if (!$placement) {
            Log::error("❌ No empty slot found in {$treeOwner->username}'s tree for new tree entry.");
            return;
        }
        
        // Create new tree entry for the completed tree owner in their own tree
        $newTreeEntry = [
            'user_id' => $treeOwner->id,
            'user_username' => $treeOwner->username,
            'sponsor_id' => $treeOwner->sponsor_id, // Keep original sponsor
            'sponsor_username' => $treeOwner->sponsor ? $treeOwner->sponsor->username : null,
            'upline_id' => $placement['upline_id'],
            'upline_username' => $placement['upline_username'],
            'position' => $placement['position'],
            'tree_owner_id' => $treeOwner->id, // Owner becomes their own tree owner for new round
            'tree_owner_username' => $treeOwner->username,
            'tree_round' => 2, // New round (2nd round)
            'is_spillover_slot' => true, // Mark as new tree entry
        ];
        
        ReferralRelationship::create($newTreeEntry);
        
        // Update tree_round_count for the tree owner (increment by 1)
        $treeOwner->increment('tree_round_count');
        
        // Log the successful creation of new tree entry
        Log::info("✅ NEW TREE ENTRY CREATED: {$treeOwner->username} (ID: {$treeOwner->id}) placed under {$placement['upline_username']} (ID: {$placement['upline_id']}) Position: {$placement['position']} in {$treeOwner->username}'s own tree, Round: 2, Tree Round Count: {$treeOwner->tree_round_count}");
    }

    /**
     * Create owner's spillover slot after 30th user is inserted
     * Places owner in first available empty slot in the entire system (same round as 30th user)
     */
    private function createOwnerSpilloverSlot(User $treeOwner, $excludePlacement, $round = 1)
    {
        // Create spillover slot in the SAME round as the 30th user
        $spilloverRound = $round;
        
        // Find first available empty slot in the entire system (not in tree owner's own downline)
        $ownerSlot = $this->findFirstGlobalEmptySlotForSpillover($treeOwner->id);
        
        // Debug: Log the owner slot placement
        Log::info("DEBUG: Owner slot placement - Upline: {$ownerSlot['upline_username']} (ID: {$ownerSlot['upline_id']}), Position: {$ownerSlot['position']}");
        
        // Debug: Log the excluded placement
        Log::info("DEBUG: Excluded placement - Upline: {$excludePlacement['upline_username']} (ID: {$excludePlacement['upline_id']}), Position: {$excludePlacement['position']}");
        
        // Debug: Check if owner slot is same as excluded placement
        if ($ownerSlot['upline_id'] == $excludePlacement['upline_id'] && $ownerSlot['position'] == $excludePlacement['position']) {
            Log::error("DEBUG: ERROR - Owner slot is same as excluded placement! This should not happen.");
        }
        
        // Debug: Log the spillover placement
        Log::info("DEBUG: Placing {$treeOwner->username} spillover slot under {$ownerSlot['upline_username']} (ID: {$ownerSlot['upline_id']}) in position {$ownerSlot['position']}");
        
        // Get the upline user (where john will be placed)
        $uplineUser = User::find($ownerSlot['upline_id']);
        
        // Create spillover slot for tree owner in the global system (same round as 30th user)
        $spilloverEntry = ReferralRelationship::create([
            'user_id' => $treeOwner->id,
            'user_username' => $treeOwner->username,
            'sponsor_id' => $uplineUser ? $uplineUser->id : null, // Sponsor is the upline user
            'sponsor_username' => $uplineUser ? $uplineUser->username : null,
            'upline_id' => $ownerSlot['upline_id'],
            'upline_username' => $ownerSlot['upline_username'],
            'position' => $ownerSlot['position'],
            'tree_owner_id' => $treeOwner->id, // john is still the tree owner
            'tree_owner_username' => $treeOwner->username,
            'tree_round' => $spilloverRound, // Same round as the 30th user
            'is_spillover_slot' => true,
        ]);
        
        // Update tree_round_count for the tree owner (increment by 1)
        $treeOwner->increment('tree_round_count');
        
        // Log the successful creation of spillover slot
        Log::info("✅ SPILLOVER SLOT CREATED: {$treeOwner->username} (ID: {$treeOwner->id}) placed under {$ownerSlot['upline_username']} (ID: {$ownerSlot['upline_id']}) Position: {$ownerSlot['position']} Round: {$spilloverRound}, Tree Round Count: {$treeOwner->tree_round_count}");
        

        
        // Debug: Verify spillover slot was created successfully
        $createdSpillover = ReferralRelationship::where('user_id', $treeOwner->id)
            ->where('is_spillover_slot', true)
            ->where('tree_round', $spilloverRound)
            ->first();
        if ($createdSpillover) {
            Log::info("✅ DEBUG: Spillover slot created successfully under {$createdSpillover->upline_username}!");
        } else {
            Log::error("❌ DEBUG: Spillover slot was NOT created!");
        }
    }

    /**
     * Find first empty slot in a specific tree (for a given tree owner and round)
     * Excludes the slot used by the 30th user
     * Uses breadth-first search with left-to-right priority
     * Includes spillover slots in the search
     */
    private function findFirstEmptySlotInTreeExcluding($treeOwnerId, $round, $excludePlacement)
    {
        // Start with tree owner
        $queue = [$treeOwnerId];
        $visited = [];
        
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            
            if (in_array($currentId, $visited)) {
                continue;
            }
            $visited[] = $currentId;
            
            // Check if this user has available slots (left-right priority)
            $availableSlot = $this->findFirstAvailableSlotForUpline($currentId, $treeOwnerId, $round);
            if ($availableSlot) {
                // Check if this slot is the same as the excluded placement
                if ($availableSlot['upline_id'] == $excludePlacement['upline_id'] && 
                    $availableSlot['position'] == $excludePlacement['position']) {
                    // Skip this slot, it's the same as the 30th user's slot
                    continue;
                }
                return $availableSlot;
            }
            
            // Both slots are filled, add children to queue for next level
            // Include both regular users and spillover slots
            $children = ReferralRelationship::where('upline_id', $currentId)
                ->where('tree_owner_id', $treeOwnerId)
                ->where('tree_round', $round)
                ->orderBy('position', 'asc') // Left first, then right
                ->get();
                
            foreach ($children as $child) {
                if (!in_array($child->user_id, $visited)) {
                    $queue[] = $child->user_id;
                }
            }
        }
        
        // Fallback: put under tree owner (if not the same as excluded)
        if ($treeOwnerId != $excludePlacement['upline_id'] || 'L' != $excludePlacement['position']) {
            return [
                'upline_id' => $treeOwnerId,
                'upline_username' => User::find($treeOwnerId)->username,
                'position' => 'L'
            ];
        } else {
            // If tree owner's left slot is excluded, use right slot
            return [
                'upline_id' => $treeOwnerId,
                'upline_username' => User::find($treeOwnerId)->username,
                'position' => 'R'
            ];
        }
    }

    /**
     * Create spillover slot for tree owner after 30th user is placed
     * Ensures tree owner is placed in a different slot than the 30th user
     */
    private function createSpilloverSlotAfterUser(User $treeOwner, $userPlacement)
    {
        // Get the highest tree round for this tree owner
        $lastRound = ReferralRelationship::where('tree_owner_id', $treeOwner->id)
            ->max('tree_round') ?: 1;
        
        $newRound = $lastRound + 1;
        
        // Find first empty slot in the tree owner's own downline tree
        // EXCLUDING the slot that was just used by the 30th user
        $downlineEmptySlot = $this->findFirstEmptySlotInTreeOwnerDownlineExcluding($treeOwner->id, $userPlacement);
        
        // Get the tree owner's sponsor from the users table (keep sponsor_id as is)
        $sponsor = User::find($treeOwner->sponsor_id);
        
        // Create spillover slot for tree owner in their own downline
        ReferralRelationship::create([
            'user_id' => $treeOwner->id,
            'user_username' => $treeOwner->username,
            'sponsor_id' => $sponsor ? $sponsor->id : null, // Keep original sponsor_id
            'sponsor_username' => $sponsor ? $sponsor->username : null,
            'upline_id' => $downlineEmptySlot['upline_id'],
            'upline_username' => $downlineEmptySlot['upline_username'],
            'position' => $downlineEmptySlot['position'],
            'tree_owner_id' => $treeOwner->id,
            'tree_owner_username' => $treeOwner->username,
            'tree_round' => $newRound,
            'is_spillover_slot' => true,
        ]);
        
        // Update tree_round_count for the tree owner (increment by 1)
        $treeOwner->increment('tree_round_count');
    }

    /**
     * Create spillover slot for tree owner when their tree reaches 30 members
     * Places the tree owner in the first available slot in their own downline tree
     */
    private function createSpilloverSlot(User $treeOwner)
    {
        // Get the highest tree round for this tree owner
        $lastRound = ReferralRelationship::where('tree_owner_id', $treeOwner->id)
            ->max('tree_round') ?: 1;
        
        $newRound = $lastRound + 1;
        
        // Find first empty slot in the tree owner's own downline tree
        $downlineEmptySlot = $this->findFirstEmptySlotInTreeOwnerDownline($treeOwner->id);
        
        // Get the tree owner's sponsor from the users table (keep sponsor_id as is)
        $sponsor = User::find($treeOwner->sponsor_id);
        
        // Create spillover slot for tree owner in their own downline
        ReferralRelationship::create([
            'user_id' => $treeOwner->id,
            'user_username' => $treeOwner->username,
            'sponsor_id' => $sponsor ? $sponsor->id : null, // Keep original sponsor_id
            'sponsor_username' => $sponsor ? $sponsor->username : null,
            'upline_id' => $downlineEmptySlot['upline_id'],
            'upline_username' => $downlineEmptySlot['upline_username'],
            'position' => $downlineEmptySlot['position'],
            'tree_owner_id' => $treeOwner->id,
            'tree_owner_username' => $treeOwner->username,
            'tree_round' => $newRound,
            'is_spillover_slot' => true,
        ]);
        
        // Update tree_round_count for the tree owner (increment by 1)
        $treeOwner->increment('tree_round_count');
    }
    
    /**
     * Find first empty slot in tree owner's downline EXCLUDING the slot used by 30th user
     * Ensures no duplicate slot assignment
     */
    private function findFirstEmptySlotInTreeOwnerDownlineExcluding($treeOwnerId, $excludePlacement)
    {
        // Get all users in the tree owner's downline (all rounds)
        $downlineUsers = ReferralRelationship::where('tree_owner_id', $treeOwnerId)
            ->where('is_spillover_slot', false)
            ->get()
            ->pluck('user_id')
            ->toArray();
        
        // Add the tree owner themselves
        $downlineUsers[] = $treeOwnerId;
        
        // Check each downline user for available slots (left-right priority)
        foreach ($downlineUsers as $userId) {
            $availableSlot = $this->findFirstAvailableSlotForUpline($userId, $treeOwnerId);
            if ($availableSlot) {
                // Check if this slot is the same as the excluded placement
                if ($availableSlot['upline_id'] == $excludePlacement['upline_id'] && 
                    $availableSlot['position'] == $excludePlacement['position']) {
                    // Skip this slot, it's the same as the 30th user's slot
                    continue;
                }
                return $availableSlot;
            }
        }
        
        // Fallback: put under tree owner (if not the same as excluded)
        if ($treeOwnerId != $excludePlacement['upline_id'] || 'L' != $excludePlacement['position']) {
            return [
                'upline_id' => $treeOwnerId,
                'upline_username' => User::find($treeOwnerId)->username,
                'position' => 'L'
            ];
        } else {
            // If tree owner's left slot is excluded, use right slot
                return [
                'upline_id' => $treeOwnerId,
                'upline_username' => User::find($treeOwnerId)->username,
                    'position' => 'R'
                ];
        }
    }

    /**
     * Find first empty slot in tree owner's downline for spillover placement
     * Uses breadth-first search with left-to-right priority
     */
    private function findFirstEmptySlotInTreeOwnerDownline($treeOwnerId)
    {
        // Get all users in the tree owner's downline (all rounds)
        $downlineUsers = ReferralRelationship::where('tree_owner_id', $treeOwnerId)
            ->where('is_spillover_slot', false)
            ->get()
            ->pluck('user_id')
            ->toArray();
        
        // Add the tree owner themselves
        $downlineUsers[] = $treeOwnerId;
        
        // Check each downline user for available slots (left-right priority)
        foreach ($downlineUsers as $userId) {
            $availableSlot = $this->findFirstAvailableSlotForUpline($userId, $treeOwnerId);
            if ($availableSlot) {
                return $availableSlot;
            }
        }
        
        // Fallback: put under tree owner
        return [
            'upline_id' => $treeOwnerId,
            'upline_username' => User::find($treeOwnerId)->username,
            'position' => 'L'
        ];
    }
    
    /**
     * Find first empty slot in sponsor's tree for spillover placement
     * Uses breadth-first search with left-to-right priority
     */
    private function findFirstEmptySlotInSponsorTree($sponsorId)
    {
        // Start with sponsor
        $queue = [$sponsorId];
        
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            
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
                $queue[] = $child->user_id;
            }
        }
        
        // Fallback: put under sponsor
        return [
            'upline_id' => $sponsorId,
            'upline_username' => User::find($sponsorId)->username,
            'position' => 'L'
        ];
    }

    /**
     * Find first empty slot in entire system for spillover
     * Uses top-to-bottom, left-to-right priority
     * For root tree owners, places in first available slot in their own tree
     * For non-root tree owners, places outside their own downline
     */
    private function findFirstGlobalEmptySlotForSpillover($treeOwnerId)
    {
        // Get all users in system ordered by ID (creation order)
        $allUsers = User::where('username', '!=', 'admin')->orderBy('id', 'asc')->get();
        
        // Get all users in the tree owner's downline to exclude them
        $downlineUsers = ReferralRelationship::where('tree_owner_id', $treeOwnerId)
            ->where('is_spillover_slot', false)
            ->pluck('user_id')
            ->toArray();
        
        // Add the tree owner themselves to exclusion list
        $downlineUsers[] = $treeOwnerId;
        
        // Debug: Log excluded users
        Log::info("DEBUG: Excluding users from spillover placement: " . implode(', ', $downlineUsers));
        
        // Check if this is a root tree owner (all users are in their downline)
        $usersNotInDownline = [];
        foreach ($allUsers as $user) {
            if (!in_array($user->id, $downlineUsers)) {
                $usersNotInDownline[] = $user->id;
            }
        }
        
        if (empty($usersNotInDownline)) {
            // This is a root tree owner - place in first available slot in their own tree
            Log::info("DEBUG: Root tree owner detected - placing in first available slot in own tree");
            return $this->findFirstEmptySlotInTree($treeOwnerId, 1);
        }
        
        // Non-root tree owner - find slot outside their downline
        foreach ($allUsers as $user) {
            // Skip if this user is in the tree owner's downline
            if (in_array($user->id, $downlineUsers)) {
                Log::info("DEBUG: Skipping user {$user->username} (ID: {$user->id}) - in tree owner's downline");
                continue;
            }
            
            // Check if this user has available slots
            $availableSlot = $this->findFirstAvailableSlotForUpline($user->id);
            if ($availableSlot) {
                Log::info("DEBUG: Found available slot under {$user->username} (ID: {$user->id})");
                return $availableSlot;
            }
        }
        
        // Fallback: if no slot found, place under first available user
        foreach ($allUsers as $user) {
            if (!in_array($user->id, $downlineUsers)) {
                Log::info("DEBUG: Fallback - placing under {$user->username} (ID: {$user->id})");
                return [
                    'upline_id' => $user->id,
                    'upline_username' => $user->username,
                    'position' => 'L'
                ];
            }
        }
        
        // Last resort: place under first user
        Log::info("DEBUG: Last resort - placing under first user");
        return [
            'upline_id' => $allUsers->first()->id,
            'upline_username' => $allUsers->first()->username,
            'position' => 'L'
        ];
    }

    /**
     * Find first empty slot in sponsor's subtree using BFS
     * Always places under sponsor first (sponsor.left, sponsor.right, then children left→right)
     * If tree has spillover slot, prioritize placing under spillover slot
     */
    private function findFirstEmptySlotInSponsorSubtree($sponsorId, $treeOwnerId, $round = 1)
    {
        // Step 1: Check if tree owner has a spillover slot (after 30th member)
        $spilloverSlot = ReferralRelationship::where('user_id', $treeOwnerId)
            ->where('is_spillover_slot', true)
            ->where('tree_round', $round)
            ->first();
            
        Log::info("DEBUG: Checking for spillover slot for tree owner ID {$treeOwnerId} in round {$round}");
        if ($spilloverSlot) {
            Log::info("DEBUG: Found spillover slot for {$spilloverSlot->user_username} under {$spilloverSlot->upline_username}");
        } else {
            Log::info("DEBUG: No spillover slot found for tree owner ID {$treeOwnerId} in round {$round}");
        }
            
        if ($spilloverSlot) {
            // If spillover slot exists, check if it has available slots
            $spilloverUser = User::find($spilloverSlot->user_id);
            if ($spilloverUser) {
                // Check spillover slot's left position first
                if ($this->isSlotAvailable($spilloverSlot->user_id, 'L', $treeOwnerId, $round)) {
                    Log::info("DEBUG: Placing new user under spillover slot {$spilloverUser->username} in left position");
                    return [
                        'upline_id' => $spilloverSlot->user_id,
                        'upline_username' => $spilloverUser->username,
                        'position' => 'L'
                    ];
                }
                
                // Check spillover slot's right position second
                if ($this->isSlotAvailable($spilloverSlot->user_id, 'R', $treeOwnerId, $round)) {
                    Log::info("DEBUG: Placing new user under spillover slot {$spilloverUser->username} in right position");
                    return [
                        'upline_id' => $spilloverSlot->user_id,
                        'upline_username' => $spilloverUser->username,
                        'position' => 'R'
                    ];
                }
            }
        }
        
        // Step 2: Check sponsor's own slots first (left, then right)
        $sponsor = User::find($sponsorId);
        if (!$sponsor) {
            throw new \Exception("Sponsor not found: {$sponsorId}");
        }
        
        // Check sponsor's left slot first
        if ($this->isSlotAvailable($sponsorId, 'L', $treeOwnerId)) {
            return [
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'L'
            ];
        }
        
        // Check sponsor's right slot second
        if ($this->isSlotAvailable($sponsorId, 'R', $treeOwnerId)) {
            return [
                'upline_id' => $sponsorId,
                'upline_username' => $sponsor->username,
                'position' => 'R'
            ];
        }
        
        // Step 3: If sponsor's slots are full, use BFS on sponsor's subtree
        $queue = [$sponsorId];
        $visited = [];
        
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            
            if (in_array($currentId, $visited)) {
                continue;
            }
            $visited[] = $currentId;
            
            // Check if this user has available slots (left-right priority)
            $availableSlot = $this->findFirstAvailableSlotForUpline($currentId, $treeOwnerId);
            if ($availableSlot) {
                return $availableSlot;
            }
            
            // Both slots are filled, add children to queue for next level
            $children = ReferralRelationship::where('upline_id', $currentId)
                ->where('tree_owner_id', $treeOwnerId)
                ->where('tree_round', $round)
                ->where('is_spillover_slot', false)
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
     * Find first empty slot in a specific tree (for a given tree owner and round)
     * Uses breadth-first search with left-to-right priority
     * Includes spillover slots in the search
     */
    private function findFirstEmptySlotInTree($treeOwnerId, $round)
    {
        // Start with tree owner
        $queue = [$treeOwnerId];
        
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            
            // Check if this user has available slots (left-right priority)
            $availableSlot = $this->findFirstAvailableSlotForUpline($currentId, $treeOwnerId, $round);
            if ($availableSlot) {
                return $availableSlot;
            }
            
            // Both slots are filled, add children to queue for next level
            // Include both regular users and spillover slots
            $children = ReferralRelationship::where('upline_id', $currentId)
                ->where('tree_owner_id', $treeOwnerId)
                ->where('tree_round', $round)
                ->orderBy('position', 'asc') // Left first, then right
                ->get();
                
            foreach ($children as $child) {
                $queue[] = $child->user_id;
            }
        }
        
        // Fallback: put under tree owner
        return [
            'upline_id' => $treeOwnerId,
            'upline_username' => User::find($treeOwnerId)->username,
            'position' => 'L'
        ];
    }

    /**
     * Check if a specific slot is available for a user
     * For spillover slots, check within the specific round
     * For regular slots, check across ALL rounds to prevent duplicates
     */
    private function isSlotAvailable($uplineId, $position, $treeOwnerId = null, $round = null)
    {
        $query = ReferralRelationship::where('upline_id', $uplineId)
            ->where('position', $position);
        
        // If tree owner is specified, only check within that tree
        if ($treeOwnerId) {
            $query->where('tree_owner_id', $treeOwnerId);
        }
        
        // If round is specified (for spillover slots), check within that round
        if ($round) {
            $query->where('tree_round', $round);
        }
        
        $existingUser = $query->first();
        
        return !$existingUser; // Return true if slot is empty
    }

    /**
     * Find first available slot for a specific upline (left-right priority)
     * Checks across ALL rounds to ensure no duplicate slot assignments
     * Includes spillover slots in the check
     */
    private function findFirstAvailableSlotForUpline($uplineId, $treeOwnerId = null, $round = null)
    {
        // Check left slot first (top priority)
        if ($this->isSlotAvailable($uplineId, 'L', $treeOwnerId)) {
            $uplineUser = User::find($uplineId);
            return [
                'upline_id' => $uplineId,
                'upline_username' => $uplineUser ? $uplineUser->username : null,
                'position' => 'L'
            ];
        }
        
        // Check right slot second
        if ($this->isSlotAvailable($uplineId, 'R', $treeOwnerId)) {
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
     * Get tree structure for visualization
     * Includes spillover slots in the tree structure
     */
    public function getTreeStructure($treeOwnerId = null)
    {
        if (!$treeOwnerId) {
            // Get all tree owners
            $treeOwners = ReferralRelationship::whereNull('upline_id')
                ->where('is_spillover_slot', false)
                ->get();
            
            $trees = [];
            foreach ($treeOwners as $owner) {
                $trees[] = $this->buildTreeStructure($owner->user_id);
            }
            
            return $trees;
        }
        
        return $this->buildTreeStructure($treeOwnerId);
    }

    /**
     * Build tree structure recursively
     * Includes spillover slots in the tree structure
     */
    private function buildTreeStructure($userId, $level = 0)
    {
        $user = User::find($userId);
        if (!$user) return null;
        
        $children = ReferralRelationship::where('upline_id', $userId)
            ->orderBy('position', 'asc')
            ->get();
        
        $leftChild = $children->where('position', 'L')->first();
        $rightChild = $children->where('position', 'R')->first();
        
        return [
            'id' => $user->id,
            'username' => $user->username,
            'level' => $level,
            'is_spillover' => $leftChild ? $leftChild->is_spillover_slot : false,
            'left' => $leftChild ? $this->buildTreeStructure($leftChild->user_id, $level + 1) : null,
            'right' => $rightChild ? $this->buildTreeStructure($rightChild->user_id, $level + 1) : null,
        ];
    }

    /**
     * Get tree statistics
     * Includes spillover slots in the statistics
     */
    public function getTreeStats($treeOwnerId)
    {
        $regularMembers = $this->countTreeMembersInRound($treeOwnerId, 1);
        $totalMembers = $this->countTotalTreeMembersInRound($treeOwnerId, 1);
        $levels = $this->getTreeLevels($treeOwnerId);
        $rounds = $this->getTreeOwnerRounds($treeOwnerId);
        
        return [
            'regular_members' => $regularMembers,
            'total_members' => $totalMembers,
            'levels' => $levels,
            'rounds' => $rounds,
            'is_complete' => $regularMembers >= 30
        ];
    }

    /**
     * Get comprehensive MLM tree information for a user
     * Shows all rounds and their status
     */
    public function getMLMTreeInfo($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return ['error' => 'User not found'];
        }

        // Get all rounds for this user as tree owner
        $rounds = ReferralRelationship::where('tree_owner_id', $userId)
            ->select('tree_round')
            ->distinct()
            ->orderBy('tree_round', 'asc')
            ->pluck('tree_round')
            ->toArray();

        $treeInfo = [
            'user' => $user,
            'total_rounds' => count($rounds),
            'tree_round_count' => $user->tree_round_count,
            'rounds' => []
        ];

        foreach ($rounds as $round) {
            $regularMembers = $this->countTreeMembersInRound($userId, $round);
            $totalMembers = $this->countTotalTreeMembersInRound($userId, $round);
            $isComplete = $regularMembers >= 30;

            $treeInfo['rounds'][$round] = [
                'round_number' => $round,
                'regular_members' => $regularMembers,
                'total_members' => $totalMembers,
                'is_complete' => $isComplete,
                'completion_percentage' => round(($regularMembers / 30) * 100, 2),
                'members_needed' => max(0, 30 - $regularMembers)
            ];
        }

        return $treeInfo;
    }

    /**
     * Get all MLM tree owners and their statistics
     */
    public function getAllMLMTreeOwners()
    {
        $treeOwners = User::where('username', '!=', 'admin')
            ->whereHas('treeEntry')
            ->with(['treeEntry'])
            ->get();

        $owners = [];
        foreach ($treeOwners as $owner) {
            $treeInfo = $this->getMLMTreeInfo($owner->id);
            $owners[] = $treeInfo;
        }

        return $owners;
    }

    /**
     * Get tree levels with member counts
     * Includes spillover slots in the level counts
     */
    private function getTreeLevels($treeOwnerId)
    {
        $levels = [];
        
        // Level 0 (tree owner)
        $levels[0] = 1;
        
        // Level 1-4 (binary tree levels)
        for ($level = 1; $level <= 4; $level++) {
            $count = $this->countMembersAtLevel($treeOwnerId, $level);
            $levels[$level] = $count;
        }
        
        return $levels;
    }

    /**
     * Count members at specific level
     * Includes spillover slots in the count
     */
    private function countMembersAtLevel($treeOwnerId, $level)
    {
        // This is a simplified version - in a real implementation,
        // you'd need to traverse the tree to count members at each level
        $totalMembers = $this->countTotalTreeMembersInRound($treeOwnerId, 1);
        
        // Calculate expected members at each level
        $expectedAtLevel = pow(2, $level);
        $maxAtLevel = min($expectedAtLevel, $totalMembers - array_sum(array_slice([1, 2, 4, 8, 16], 0, $level)));
        
        return max(0, $maxAtLevel);
    }
}

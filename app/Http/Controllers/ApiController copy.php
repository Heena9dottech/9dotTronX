<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LevelPlan;
use App\Models\ReferralRelationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    /**
     * Create user with level plan via API
     * Takes: username, sponsor_name, level_id
     * Creates user and adds to tree with level plan
     */
    public function createUserWithLevel(Request $request)
    {
        // Validate the request
        $request->validate([
            'username' => 'required|unique:users,username',
            'sponsor_name' => 'required|exists:users,username',
            'level_id' => 'required|exists:level_plans,id'
        ]);

        try {
            DB::transaction(function () use ($request) {
                // 1️⃣ Get sponsor by name
                $sponsor = User::where('username', $request->sponsor_name)->first();
                if (!$sponsor) {
                    throw new \Exception("Sponsor not found with name: " . $request->sponsor_name);
                }
                
                // 2️⃣ Get level plan details
                $levelPlan = LevelPlan::find($request->level_id);
                
                // 3️⃣ Create the user
                $user = User::create([
                    'username' => $request->username,
                    'email' => $request->username . '@mlm.com', // auto-generate email
                    'password' => Hash::make('123456'),
                    'sponsor_id' => $sponsor->id
                ]);

                // 4️⃣ Add user to tree with level plan information
                $this->addUserToTreeWithLevel($sponsor, $user, $levelPlan);
            });

            return response()->json([
                'success' => true,
                'message' => 'User created successfully with level plan!',
                'data' => [
                    'username' => $request->username,
                    'sponsor_name' => $request->sponsor_name,
                    'sponsor_id' => User::where('username', $request->sponsor_name)->first()->id,
                    'level_id' => $request->level_id
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('API Error creating user with level: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add user to tree with level plan information
     * Same logic as TreeController but in separate method
     */
    private function addUserToTreeWithLevel(User $sponsor, User $newUser, $levelPlan)
    {
        // Log the start of adding user to tree
        Log::info("🔄 API ADDING USER WITH LEVEL: {$newUser->username} (ID: {$newUser->id}) sponsored by {$sponsor->username} (ID: {$sponsor->id}) with level {$levelPlan->level_number}");
        
        // Use DB transaction to ensure atomicity
        DB::transaction(function () use ($sponsor, $newUser, $levelPlan) {
            // Step 1: Determine tree owner
            $treeOwner = $this->findTreeOwner($sponsor);
            
            // Step 2: Determine which round to add the new user to
            $targetRound = $this->getCurrentActiveRound($treeOwner->id);
            
            // Step 3: Find first empty slot in sponsor's subtree (BFS: sponsor.left, sponsor.right, then children)
            $placement = $this->findFirstEmptySlotInSponsorSubtree($sponsor->id, $treeOwner->id, $targetRound);
            
            // Step 4: Insert the new user entry with level plan information
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
                'level_number' => $levelPlan->level_number,
                'slot_price' => $levelPlan->price,
                'level_id' => $levelPlan->id
            ];
            
            ReferralRelationship::create($newUserEntry);
            
            // Log the successful creation of new user entry
            Log::info("✅ API USER ENTRY CREATED: {$newUser->username} (ID: {$newUser->id}) placed under {$placement['upline_username']} (ID: {$placement['upline_id']}) Position: {$placement['position']} in {$treeOwner->username}'s tree with Level {$levelPlan->level_number}");
        });
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
        $latestRound = max($rounds);
        Log::info("DEBUG: Tree owner {$treeOwnerId} using round {$latestRound}");
        return $latestRound;
    }

    /**
     * Find first empty slot in sponsor's subtree using BFS
     */
    private function findFirstEmptySlotInSponsorSubtree($sponsorId, $treeOwnerId, $round = 1)
    {
        // Step 1: Check sponsor's own slots first (left, then right)
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
}

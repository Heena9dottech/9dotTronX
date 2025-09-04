<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ReferralRelationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiTreeLogicController extends Controller
{
    /**
     * Get total members under a user for 4 levels
     * API endpoint: GET /api/member-count/{username}
     * 
     * @param string $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMemberCountByLevels(Request $request, $username)
    {
        try {
            // Validate username parameter
            if (empty($username)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username is required'
                ], 400);
            }

            // Find the user by username
            $user = User::where('username', $username)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found with username: ' . $username
                ], 404);
            }

            // Get the tree owner for this user
            $treeOwner = $this->findTreeOwner($user);
            // dump($treeOwner);
            // Get current active round
            $currentRound = $this->getCurrentActiveRound($treeOwner->id);


            // Calculate member count for 4 levels
            $memberCounts = $this->calculateMemberCountByLevels($user->id, $treeOwner->id, $currentRound, 4);
            // dd($memberCounts);
            // Calculate total members
            $totalMembers = array_sum($memberCounts);
            // dump($memberCounts[1]);
            return response()->json([
                'success' => true,
                'data' => [
                    'username' => $username,
                    'user_id' => $user->id,
                    'tree_owner' => $treeOwner->username,
                    'tree_round' => $currentRound,
                    'level_breakdown' => [
                        'level_1' => $memberCounts[1] ?? 0,
                        'level_2' => $memberCounts[2] ?? 0,
                        'level_3' => $memberCounts[3] ?? 0,
                        'level_4' => $memberCounts[4] ?? 0,
                    ],
                    'total_members' => $totalMembers,
                    'calculation' => [
                        'level_1' => $memberCounts[1] ?? 0,
                        'level_2' => $memberCounts[2] ?? 0,
                        'level_3' => $memberCounts[3] ?? 0,
                        'level_4' => $memberCounts[4] ?? 0,
                        'total' => $totalMembers
                    ]
                ],
                'message' => "Found {$totalMembers} total members under {$username} across 4 levels"
            ], 200);
        } catch (\Exception $e) {
            Log::error('API Error getting member count: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error getting member count: ' . $e->getMessage()
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
        // dd($userId, $treeOwnerId, $round, $maxLevels);
        $levelCounts = [];
        $currentLevel = [$userId]; // Start with the user's ID
        $level = 1;

        // dump($currentLevel);
        while ($level <= $maxLevels && !empty($currentLevel)) {
            $nextLevel = [];
            $levelCount = 0;

            // dump("while start-------".json_encode($currentLevel));
            // dump($currentLevel);
            foreach ($currentLevel as $currentUserId) {

                // dump("currentUserId-------".$currentUserId);
                // Get direct children (referrals) of current user
                $children = ReferralRelationship::where('upline_id', $currentUserId)
                    ->where('tree_owner_id', $treeOwnerId)
                    ->where('tree_round', $round)
                    ->where('is_spillover_slot', false)
                    ->get();

                // dump("------------------");

                foreach ($children as $child) {
                    // dump("id-------".$child->user_id);
                    // dump("username-------".$child->user_username);
                    $nextLevel[] = $child->user_id;
                    $levelCount++;
                    // dump("nextLevel-------".json_encode($nextLevel));
                    // dump("levelCount-------".$levelCount);
                    // dump("------2nd loop over--------");
                }
                // dump("------------------");
                // dump("------1st for loop over--------");
            }

            $levelCounts[$level] = $levelCount;
            $currentLevel = $nextLevel;
            $level++;
            // dump("----While loop over--------");
        }

        // Fill remaining levels with 0 if we didn't reach max levels
        for ($i = $level; $i <= $maxLevels; $i++) {
            $levelCounts[$i] = 0;
        }

        return $levelCounts;
    }

    /**
     * Alternative method using recursive approach (for reference)
     * This method counts all descendants recursively
     * 
     * @param int $userId
     * @param int $treeOwnerId
     * @param int $round
     * @param int $currentLevel
     * @param int $maxLevels
     * @return array
     */
    private function calculateMemberCountRecursive($userId, $treeOwnerId, $round, $currentLevel = 1, $maxLevels = 4)
    {
        if ($currentLevel > $maxLevels) {
            return [];
        }

        $levelCounts = [];
        $currentLevelCount = 0;

        // Get direct children
        $children = ReferralRelationship::where('upline_id', $userId)
            ->where('tree_owner_id', $treeOwnerId)
            ->where('tree_round', $round)
            ->where('is_spillover_slot', false)
            ->get();

        $currentLevelCount = $children->count();
        $levelCounts[$currentLevel] = $currentLevelCount;

        // Recursively count children's descendants
        foreach ($children as $child) {
            $childCounts = $this->calculateMemberCountRecursive(
                $child->user_id,
                $treeOwnerId,
                $round,
                $currentLevel + 1,
                $maxLevels
            );

            // Merge child counts
            foreach ($childCounts as $level => $count) {
                $levelCounts[$level] = ($levelCounts[$level] ?? 0) + $count;
            }
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
        // dd($rounds);
        if (empty($rounds)) {
            return 1; // First round
        }

        // Always use the latest round
        return max($rounds);
    }

    /**
     * Get detailed tree structure for a user (bonus method)
     * This shows the actual tree structure up to 4 levels
     * 
     * @param Request $request
     * @param string $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTreeStructure(Request $request, $username)
    {
        try {
            // Find the user by username
            $user = User::where('username', $username)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found with username: ' . $username
                ], 404);
            }

            // Get the tree owner for this user
            $treeOwner = $this->findTreeOwner($user);
            $currentRound = $this->getCurrentActiveRound($treeOwner->id);

            // Build tree structure
            $treeStructure = $this->buildTreeStructure($user->id, $treeOwner->id, $currentRound, 4);

            return response()->json([
                'success' => true,
                'data' => [
                    'username' => $username,
                    'tree_owner' => $treeOwner->username,
                    'tree_round' => $currentRound,
                    'tree_structure' => $treeStructure
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('API Error getting tree structure: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error getting tree structure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build tree structure recursively
     * 
     * @param int $userId
     * @param int $treeOwnerId
     * @param int $round
     * @param int $maxLevels
     * @param int $currentLevel
     * @return array
     */
    private function buildTreeStructure($userId, $treeOwnerId, $round, $maxLevels = 4, $currentLevel = 1)
    {
        if ($currentLevel > $maxLevels) {
            return null;
        }

        $user = User::find($userId);
        $structure = [
            'user_id' => $userId,
            'username' => $user->username,
            'level' => $currentLevel,
            'children' => []
        ];

        // Get direct children
        $children = ReferralRelationship::where('upline_id', $userId)
            ->where('tree_owner_id', $treeOwnerId)
            ->where('tree_round', $round)
            ->where('is_spillover_slot', false)
            ->orderBy('position', 'asc')
            ->get();

        foreach ($children as $child) {
            $childStructure = $this->buildTreeStructure(
                $child->user_id,
                $treeOwnerId,
                $round,
                $maxLevels,
                $currentLevel + 1
            );

            if ($childStructure) {
                $structure['children'][] = $childStructure;
            }
        }

        return $structure;
    }
}

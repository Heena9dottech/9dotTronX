<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ReferralRelationship;
use Illuminate\Http\Request;

class UserTreeController extends Controller
{
    public function index()
    {
        $users = User::where('username', '!=', 'admin')
            ->orderBy('id', 'asc')
            ->get();
        
        $levelPlans = \App\Models\LevelPlan::active()->ordered()->limit(1)->get();
        
        return view('users.index', compact('users', 'levelPlans'));
    }

    /**
     * Show tree overview with all trees
     */
    public function overview()
    {
        // Get all tree owners (users with no upline)
        $treeOwners = ReferralRelationship::whereNull('upline_id')
            ->where('is_spillover_slot', false)
            ->with('user')
            ->get();
        
        $trees = [];
        $totalUsers = User::where('username', '!=', 'admin')->count();
        $totalSpillovers = ReferralRelationship::where('is_spillover_slot', true)->count();
        $completeTrees = 0;
        
        foreach ($treeOwners as $owner) {
            $tree = $this->buildTree($owner->user_id);
            $trees[] = $tree;
            
            if ($tree['member_count'] >= 30) {
                $completeTrees++;
            }
        }
        
        return view('tree_overview', compact('trees', 'totalUsers', 'totalTrees', 'totalSpillovers', 'completeTrees'));
    }

    public function showTree($username, $round = 1)
    {
        $user = User::where('username', $username)->firstOrFail();
        
        // Get the user's tree structure for specific round
        $tree = $this->buildTreeForRound($user->id, $round);
        // dump($tree['level2'][0]->user->id);
        // dump($tree['level2'][0]->user->username);
        // dd($tree['level2'][0]);
        // dd($tree);
        // Get all available rounds for this user
        $availableRounds = $this->getAvailableRounds($user->id);
        
        return view('users.tree', compact('user', 'tree', 'round', 'availableRounds'));
    }

    /**
     * Get tree statistics for a specific user
     */
    public function getTreeStats($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        $treeController = new TreeController();
        
        return response()->json($treeController->getTreeStats($user->id));
    }

    /**
     * Get tree structure as JSON for API usage
     */
    public function getTreeStructure($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        $treeController = new TreeController();
        
        return response()->json($treeController->getTreeStructure($user->id));
    }

    public function buildTree($userId)
    {
        $tree = [
            'owner' => null,
            'level1' => [null, null], // [0] = left, [1] = right
            'level2' => [null, null, null, null], // [0,1] under left, [2,3] under right
            'level3' => [null, null, null, null, null, null, null, null], // 8 positions
            'level4' => [null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null], // 16 positions
            'spillover_slots' => [], // Track spillover slots for this tree owner
            'tree_rounds' => [], // All rounds for this tree owner
            'member_count' => 0,
            'round_details' => [] // Details for each round
        ];

        // Get owner (the user themselves)
        $owner = User::find($userId);
        $tree['owner'] = $owner;

        // Count total members in this user's complete downline tree
        $tree['member_count'] = $this->countCompleteDownlineMembers($userId);

        // Get all rounds for this tree owner
        $allRounds = ReferralRelationship::where('tree_owner_id', $userId)
            ->select('tree_round')
            ->distinct()
            ->orderBy('tree_round', 'asc')
            ->pluck('tree_round')
            ->toArray();

        $tree['tree_rounds'] = $allRounds;
        
        // Get details for each round
        foreach ($allRounds as $round) {
            $roundMembers = ReferralRelationship::where('tree_owner_id', $userId)
                ->where('tree_round', $round)
                ->where('is_spillover_slot', false)
                ->count();
            
            $tree['round_details'][] = [
                'round' => $round,
                'members' => $roundMembers,
                'status' => $roundMembers >= 30 ? 'Full' : 'Active'
            ];
        }
        
        // Get all spillover slots for this tree owner
        $spilloverSlots = ReferralRelationship::where('tree_owner_id', $userId)
            ->where('is_spillover_slot', true)
            ->orderBy('tree_round', 'asc')
            ->get();

        foreach ($spilloverSlots as $slot) {
            $tree['spillover_slots'][] = (object)[
                'user_id' => $slot->user_id,
                'user' => $slot->user,
                'tree_round' => $slot->tree_round,
                'position_info' => "Round {$slot->tree_round} - Position: {$slot->position}",
                'upline_username' => $slot->upline_username
            ];
        }

        // Build the complete tree structure showing all downline members
        $this->buildCompleteTreeStructure($tree, $userId);

        return $tree;
    }

    // Old methods removed - now using buildCompleteTreeStructure for complete downline display

    // Count complete downline members for level income calculation
    private function countCompleteDownlineMembers($userId)
    {
        // Count all members in the complete downline tree (recursive)
        $totalMembers = $this->countDownlineRecursively($userId);
        return $totalMembers;
    }
    
    // Recursively count all downline members
    private function countDownlineRecursively($userId, $visited = [])
    {
        // Prevent infinite recursion
        if (in_array($userId, $visited)) {
            return 0;
        }
        $visited[] = $userId;
        
        $count = 0;
        
        // Get direct children (include both regular members AND spillover slots)
        $directChildren = ReferralRelationship::where('upline_id', $userId)
            ->get();
        
        $count += $directChildren->count();
        
        // Recursively count children of each child
        foreach ($directChildren as $child) {
            $count += $this->countDownlineRecursively($child->user_id, $visited);
        }
        
        return $count;
    }

    // Build complete tree structure showing all downline members
    private function buildCompleteTreeStructure(&$tree, $userId)
    {
        // Get all direct children of this user (where this user is the upline)
        // Include both regular members AND spillover slots
        $directChildren = ReferralRelationship::where('upline_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'asc') // Order by creation time for proper placement
            ->get();
        
        // Build tree structure with proper level placement
        $this->buildTreeWithLevels($tree, $directChildren, 1);
    }
    
    // Build tree structure with proper level placement
    private function buildTreeWithLevels(&$tree, $children, $level, $visited = [])
    {
        if ($level > 4) return; // Maximum 4 levels
        
        $levelKey = "level{$level}";
        
        foreach ($children as $child) {
            // Prevent infinite recursion
            if (in_array($child->user_id, $visited)) {
                continue;
            }
            $visited[] = $child->user_id;
            
            // Find the correct position in this level based on the child's position
            $position = $this->findPositionInLevel($tree, $child, $level);
            
            if ($position !== -1 && $position < count($tree[$levelKey])) {
                $tree[$levelKey][$position] = (object)[
                    'user_id' => $child->user_id,
                    'user' => $child->user,
                    'position' => $child->position
                ];
                
                // Recursively get children of this child
                // For spillover slots, show their new round tree, not their original tree
                if ($child->is_spillover_slot) {
                    // For spillover slots, get children from their new round
                    $grandChildren = ReferralRelationship::where('upline_id', $child->user_id)
                        ->where('tree_round', $child->tree_round)
                        ->with('user')
                        ->orderBy('created_at', 'asc')
                        ->get();
                } else {
                    // For regular members, get all their children
                    $grandChildren = ReferralRelationship::where('upline_id', $child->user_id)
                        ->with('user')
                        ->orderBy('created_at', 'asc')
                        ->get();
                }
                
                if ($grandChildren->count() > 0) {
                    $this->buildTreeWithLevels($tree, $grandChildren, $level + 1, $visited);
                }
            }
        }
    }
    
    // Find the correct position in a level for a child
    private function findPositionInLevel($tree, $child, $level)
    {
        if ($level == 1) {
            // Level 1: Direct children of root
            return $child->position === 'L' ? 0 : 1;
        }
        
        // For other levels, find the parent's position and calculate child position
        $parentPosition = $this->findParentPositionInTree($tree, $child->upline_id, $level - 1);
        if ($parentPosition === -1) return -1;
        
        // Calculate child position based on parent position
        $baseIndex = $parentPosition * 2;
        return $child->position === 'L' ? $baseIndex : $baseIndex + 1;
    }
    
    // Find parent's position in the tree
    private function findParentPositionInTree($tree, $parentId, $level)
    {
        $levelKey = "level{$level}";
        if (!isset($tree[$levelKey])) return -1;
        
        for ($i = 0; $i < count($tree[$levelKey]); $i++) {
            if ($tree[$levelKey][$i] && $tree[$levelKey][$i]->user_id == $parentId) {
                return $i;
            }
        }
        
        return -1;
    }

    /**
     * Build tree structure for a specific round
     */
    public function buildTreeForRound($userId, $round)
    {
        $tree = [
            'owner' => null,
            'level1' => [null, null], // [0] = left, [1] = right
            'level2' => [null, null, null, null], // [0,1] under left, [2,3] under right
            'level3' => [null, null, null, null, null, null, null, null], // 8 positions
            'level4' => [null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null], // 16 positions
            'member_count' => 0,
            'round' => $round
        ];

        // Get owner (the user themselves)
        $owner = User::find($userId);
        $tree['owner'] = $owner;

        // Count members in this specific round
        $tree['member_count'] = $this->countMembersInRound($userId, $round);

        // Build the tree structure for this specific round
        $this->buildRoundTreeStructure($tree, $userId, $round);

        return $tree;
    }

    /**
     * Get all available rounds for a user
     */
    public function getAvailableRounds($userId)
    {
        $rounds = ReferralRelationship::where('tree_owner_id', $userId)
            ->select('tree_round')
            ->distinct()
            ->orderBy('tree_round', 'asc')
            ->pluck('tree_round')
            ->toArray();

        // Always include round 1 (main tree)
        if (!in_array(1, $rounds)) {
            array_unshift($rounds, 1);
        }

        return $rounds;
    }

    /**
     * Count members in a specific round (ONLY 4 levels: 2+4+8+16=30)
     */
    private function countMembersInRound($userId, $round)
    {
        // Count ONLY the 4 levels (2+4+8+16=30 max)
        // Level 5+ members are NOT counted in this tree owner's count
        // They belong to their own separate trees
        
        return $this->countFourLevelsOnly($userId, $round);
    }
    
    /**
     * Count only 4 levels of downline members (2+4+8+16=30)
     */
    private function countFourLevelsOnly($userId, $round, $currentLevel = 1, $treeOwnerId = null)
    {
        if ($currentLevel > 4) {
            return 0;
        }
        
        // Set tree owner ID on first call
        if ($treeOwnerId === null) {
            $treeOwnerId = $userId;
        }
        
        $count = 0;
        
        // Get direct children of this user (including spillover slots)
        $children = ReferralRelationship::where('upline_id', $userId)
            ->where('tree_round', $round)
            ->get();
        
        $count += $children->count();
        
        // Recursively count children of each child (only up to level 4)
        if ($currentLevel < 4) {
            foreach ($children as $child) {
                $count += $this->countFourLevelsOnly($child->user_id, $round, $currentLevel + 1, $treeOwnerId);
            }
        }
        
        return $count;
    }

    /**
     * Build tree structure for a specific round
     */
    private function buildRoundTreeStructure(&$tree, $userId, $round)
    {
        // Get all direct children of this user in the specific round (including spillover slots)
        // Show all children to display complete tree structure
        $directChildren = ReferralRelationship::where('upline_id', $userId)
            ->where('tree_round', $round)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Build tree structure with proper level placement for this round
        $this->buildRoundTreeWithLevels($tree, $directChildren, 1, $round, $userId);
    }
    
    /**
     * Build tree structure with proper level placement for a specific round (display only 4 levels)
     */
    private function buildRoundTreeWithLevels(&$tree, $children, $level, $round, $treeOwnerId, $visited = [])
    {
        if ($level > 4) return; // Display only 4 levels - MLM tree structure
        
        $levelKey = "level{$level}";
        
        foreach ($children as $child) {
            // Prevent infinite recursion
            if (in_array($child->user_id, $visited)) {
                continue;
            }
            $visited[] = $child->user_id;
            
            // Find the correct position in this level based on the child's position
            $position = $this->findPositionInLevel($tree, $child, $level);
            
            if ($position !== -1 && $position < count($tree[$levelKey])) {
                $tree[$levelKey][$position] = (object)[
                    'user_id' => $child->user_id,
                    'user' => $child->user,
                    'position' => $child->position,
                    'is_spillover' => $child->is_spillover_slot
                ];
                
                // Get children for display (only 4 levels shown)
                // Level 4 members' children are NOT part of this tree owner's tree
                if ($level < 4) {
                    // Recursively get children of this child in the same round (including spillover slots)
                    // Show all children regardless of tree owner to display complete tree
                    $grandChildren = ReferralRelationship::where('upline_id', $child->user_id)
                        ->where('tree_round', $round)
                        ->with('user')
                        ->orderBy('created_at', 'asc')
                        ->get();
                    
                    if ($grandChildren->count() > 0) {
                        $this->buildRoundTreeWithLevels($tree, $grandChildren, $level + 1, $round, $treeOwnerId, $visited);
                    }
                }
            }
        }
    }
}

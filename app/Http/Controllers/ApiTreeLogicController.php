<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ReferralRelationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class ApiTreeLogicController extends Controller
{
    /**
     * Get total members under a user for 4 levels using left-right child logic
     * API endpoint: GET /api/member-count/{username}
     * 
     * @param string $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMemberCountByLevels(Request $request, $username)
    {

        // $apiURL =  "https://api.trongrid.io/v1/accounts/TTQMAsEFLN9v3Z5CmAL2nspSzeTribjorp/transactions/trc20?only_to=true&limit=50";
        // $apiURL =  "https://api.trongrid.io/v1/accounts/TKgbg9fbzQpSyuw3YLxvX8DTwatW4hivDD/transactions/trc20?only_to=true&limit=50";
        // $apiURL = "https://api.trongrid.io/v1/accounts/TKgbg9fbzQpSyuw3YLxvX8DTwatW4hivDD/transactions/trc20?only_to=true&limit=50";

        // $response = Http::retry(5, 1000, function ($exception, $request) {
        //                     return $exception->getCode() === 429; // retry only on "Too Many Requests"
        //                 })
        //                 ->timeout(15)
        //                 ->get($apiURL);
    
        // if ($response->failed()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'API request failed',
        //         'status' => $response->status(),
        //     ], $response->status());
        // }
    
        // $responseObj = $response->json();
        // $data_count = isset($responseObj['data']) ? count($responseObj['data']) : 0;
    
        // return response()->json([
        //     'success' => true,
        //     'message' => 'done',
        //     'responseObj' => $responseObj,
        //     'data' => $data_count
        // ]);

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

            // Calculate member count and names for 4 levels using left-right child logic
            $memberData = $this->calculateMemberDataByLeftRightSlots($user->id, 4);

            // Calculate total members
            $totalMembers = array_sum(array_column($memberData, 'count'));

            return response()->json([
                'success' => true,
                'data' => [
                    'username' => $username,
                    'user_id' => $user->id,
                    'level_breakdown' => [
                        'level_1' => [
                            'count' => $memberData[1]['count'] ?? 0,
                            'names' => implode(', ', array_column($memberData[1]['members'] ?? [], 'username'))
                        ],
                        'level_2' => [
                            'count' => $memberData[2]['count'] ?? 0,
                            'names' => implode(', ', array_column($memberData[2]['members'] ?? [], 'username'))
                        ],
                        'level_3' => [
                            'count' => $memberData[3]['count'] ?? 0,
                            'names' => implode(', ', array_column($memberData[3]['members'] ?? [], 'username'))
                        ],
                        'level_4' => [
                            'count' => $memberData[4]['count'] ?? 0,
                            'names' => implode(', ', array_column($memberData[4]['members'] ?? [], 'username'))
                        ]
                    ],
                    'total_members' => $totalMembers,
                    'calculation' => [
                        'level_1' => $memberData[1]['count'] ?? 0,
                        'level_2' => $memberData[2]['count'] ?? 0,
                        'level_3' => $memberData[3]['count'] ?? 0,
                        'level_4' => $memberData[4]['count'] ?? 0,
                        'total' => $totalMembers
                    ]
                ],
                'message' => "Found {$totalMembers} total members under {$username} across 4 levels using left-right child logic"
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
     * Calculate member count and names by levels using left-right child logic
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
    private function calculateMemberDataByLeftRightSlots($userId, $maxLevels = 4)
    {
        $levelData = [];
        $currentLevel = [$userId]; // Start with the user's ID
        $level = 1;

        while ($level <= $maxLevels && !empty($currentLevel)) {
            $nextLevel = [];
            $levelMembers = [];

            foreach ($currentLevel as $currentUserId) {
                // Get direct children (referrals) of current user using left-right logic only
                $children = ReferralRelationship::where('upline_id', $currentUserId)
                    ->orderBy('position', 'asc') // Left first, then right
                    ->get();

                foreach ($children as $child) {
                    $nextLevel[] = $child->user_id;
                    $levelMembers[] = [
                        'user_id' => $child->user_id,
                        'username' => $child->user_username,
                        'position' => $child->position,
                        'upline_id' => $child->upline_id,
                        'upline_username' => $child->upline_username,
                        'relationship_id' => $child->id // Add relationship ID to track each slot separately
                    ];
                }
            }

            $levelData[$level] = [
                'count' => count($levelMembers),
                'members' => $levelMembers
            ];

            // Log for debugging - show if same user appears multiple times
            $usernames = array_column($levelMembers, 'username');
            $duplicateUsernames = array_diff_assoc($usernames, array_unique($usernames));
            if (!empty($duplicateUsernames)) {
                Log::info("Level {$level} has duplicate usernames: " . implode(', ', array_unique($duplicateUsernames)));
            }

            $currentLevel = $nextLevel;
            $level++;
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
     * Calculate member count by levels using left-right child logic (legacy method)
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
    private function calculateMemberCountByLeftRightSlots($userId, $maxLevels = 4)
    {
        $levelCounts = [];
        $currentLevel = [$userId]; // Start with the user's ID
        $level = 1;

        while ($level <= $maxLevels && !empty($currentLevel)) {
            $nextLevel = [];
            $levelCount = 0;

            foreach ($currentLevel as $currentUserId) {
                // Get direct children (referrals) of current user using left-right logic only
                $children = ReferralRelationship::where('upline_id', $currentUserId)
                    ->orderBy('position', 'asc') // Left first, then right
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
     * Get detailed tree structure for a user using left-right child logic
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

            // Build tree structure using left-right child logic
            $treeStructure = $this->buildTreeStructureByLeftRight($user->id, 4);

            return response()->json([
                'success' => true,
                'data' => [
                    'username' => $username,
                    'user_id' => $user->id,
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
     * Build tree structure using left-right child logic
     * 
     * @param int $userId
     * @param int $maxLevels
     * @param int $currentLevel
     * @return array
     */
    private function buildTreeStructureByLeftRight($userId, $maxLevels = 4, $currentLevel = 1)
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

        // Get direct children using left-right logic only
        $children = ReferralRelationship::where('upline_id', $userId)
            ->orderBy('position', 'asc') // Left first, then right
            ->get();

        foreach ($children as $child) {
            $childStructure = $this->buildTreeStructureByLeftRight(
                $child->user_id,
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

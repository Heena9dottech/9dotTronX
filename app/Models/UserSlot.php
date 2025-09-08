<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'level_plans_id', 
        'referral_relationship_id',
        'tree_member_ids'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'level_plans_id' => 'integer',
        'referral_relationship_id' => 'integer',
        'tree_member_ids' => 'array'
    ];

    // Relationship to get the user details
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship to get the level plan details
    public function levelPlan()
    {
        return $this->belongsTo(LevelPlan::class, 'level_plans_id');
    }

    // Relationship to get the referral relationship details
    public function referralRelationship()
    {
        return $this->belongsTo(ReferralRelationship::class, 'referral_relationship_id');
    }

    /**
     * Add a member to the tree structure at the specified level
     * 
     * @param int $memberId The user ID to add
     * @param int $level The level (1-4) where to add the member
     */
    public function addTreeMember($memberId, $level)
    {
        $treeMembers = $this->tree_member_ids ?? [];
        
        // Initialize level if it doesn't exist
        if (!isset($treeMembers["level_{$level}"])) {
            $treeMembers["level_{$level}"] = [];
        }
        
        // Check if member already exists in this level
        if (!in_array($memberId, $treeMembers["level_{$level}"])) {
            // Add member to the level only if not already present
            $treeMembers["level_{$level}"][] = $memberId;
            
            // Update the model
            $this->tree_member_ids = $treeMembers;
            $this->save();
        }
        
        return $this;
    }

    /**
     * Add a member to the tree structure at the specified level
     * Excludes the user themselves from their own tree structure
     * 
     * @param int $memberUserId The user ID to add
     * @param int $level The level (1-4) where to add the member
     * @param int $currentUserId The user ID to exclude from their own tree
     */
    public function addTreeMemberExcludingSelf($memberUserId, $level, $currentUserId)
    {
        // Don't add the user to their own tree structure
        if ($memberUserId == $currentUserId) {
            return $this;
        }
        
        // Use the main addTreeMember method which already checks for duplicates
        return $this->addTreeMember($memberUserId, $level);
    }

    /**
     * Get tree members for a specific level
     * 
     * @param int $level The level to get members from
     * @return array Array of member IDs
     */
    public function getTreeMembersByLevel($level)
    {
        $treeMembers = $this->tree_member_ids ?? [];
        return $treeMembers["level_{$level}"] ?? [];
    }

    /**
     * Get all tree members across all levels
     * 
     * @return array Array of all member IDs
     */
    public function getAllTreeMembers()
    {
        $treeMembers = $this->tree_member_ids ?? [];
        $allMembers = [];
        
        foreach ($treeMembers as $level => $members) {
            $allMembers = array_merge($allMembers, $members);
        }
        
        return $allMembers;
    }

    /**
     * Get tree structure summary
     * 
     * @return array Summary of tree structure
     */
    public function getTreeSummary()
    {
        $treeMembers = $this->tree_member_ids ?? [];
        $summary = [];
        
        for ($i = 1; $i <= 4; $i++) {
            $levelMembers = $treeMembers["level_{$i}"] ?? [];
            $summary["level_{$i}"] = [
                'count' => count($levelMembers),
                'members' => $levelMembers
            ];
        }
        
        return $summary;
    }
}

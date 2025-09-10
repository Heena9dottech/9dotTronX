<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'user_username',
        'sponsor_id', 'sponsor_username',
        'upline_id', 'upline_username',
        'upline1', 'upline2', 'upline3', 'upline4',
        'position',
        'tree_owner_id', 'tree_owner_username',
        'tree_round', 'is_spillover_slot',
        'level_number', 'slot_price', 'level_id',
        'user_slots_id', 'main_upline_id'
    ];

    protected $casts = [
        'tree_round' => 'integer',
        'is_spillover_slot' => 'boolean',
        'level_number' => 'integer',
        'slot_price' => 'decimal:2',
        'user_slots_id' => 'integer',
        'main_upline_id' => 'integer',
        'upline1' => 'integer',
        'upline2' => 'integer',
        'upline3' => 'integer',
        'upline4' => 'integer'
    ];

    // Relationship to get the user details
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship to get the sponsor details
    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    // Relationship to get the upline details
    public function upline()
    {
        return $this->belongsTo(User::class, 'upline_id');
    }

    // Relationship to get the tree owner details
    public function treeOwner()
    {
        return $this->belongsTo(User::class, 'tree_owner_id');
    }

    // Relationship to get the level plan details
    public function levelPlan()
    {
        return $this->belongsTo(LevelPlan::class, 'level_id');
    }

    // Relationship to get the user slot details
    public function userSlot()
    {
        return $this->belongsTo(UserSlot::class, 'user_slots_id');
    }

    // Upline relationships
    public function upline1User()
    {
        return $this->belongsTo(User::class, 'upline1');
    }

    public function upline2User()
    {
        return $this->belongsTo(User::class, 'upline2');
    }

    public function upline3User()
    {
        return $this->belongsTo(User::class, 'upline3');
    }

    public function upline4User()
    {
        return $this->belongsTo(User::class, 'upline4');
    }

    /**
     * Calculate upline columns based on the referral tree
     */
    public function calculateUplineColumns()
    {
        $currentUserId = $this->user_id;
        $uplineId = $this->upline_id;
        
        $this->upline1 = $uplineId;
        $this->upline2 = null;
        $this->upline3 = null;
        $this->upline4 = null;

        if ($uplineId) {
            //5
            $upline1 = ReferralRelationship::where('user_id', $uplineId)->first();
            if ($upline1) {
                $this->upline2 = $upline1->upline_id;
                
                if ($upline1->upline_id) {
                    $upline2 = ReferralRelationship::where('user_id', $upline1->upline_id)->first();
                    if ($upline2) {
                        $this->upline3 = $upline2->upline_id;
                        
                        if ($upline2->upline_id) {
                            $upline3 = ReferralRelationship::where('user_id', $upline2->upline_id)->first();
                            if ($upline3) {
                                $this->upline4 = $upline3->upline_id;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get upline chain as array
     */
    public function getUplineChain(): array
    {
        $chain = [];
        
        if ($this->upline1) $chain['upline1'] = $this->upline1;
        if ($this->upline2) $chain['upline2'] = $this->upline2;
        if ($this->upline3) $chain['upline3'] = $this->upline3;
        if ($this->upline4) $chain['upline4'] = $this->upline4;
        
        return $chain;
    }

    /**
     * Boot method to automatically calculate uplines when creating/updating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($relationship) {
            $relationship->calculateUplineColumns();
        });

        static::updating(function ($relationship) {
            $relationship->calculateUplineColumns();
        });
    }
}

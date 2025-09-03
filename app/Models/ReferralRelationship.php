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
        'position',
        'tree_owner_id', 'tree_owner_username',
        'tree_round', 'is_spillover_slot',
        'level_number', 'slot_price', 'level_id'
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
}

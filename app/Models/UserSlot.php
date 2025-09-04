<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level_plans_id', 
        'referral_relationship_id'
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
}

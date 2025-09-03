<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $fillable = ['username', 'email', 'password', 'sponsor_id', 'tree_round_count'];

    // A user can have many referrals (people they sponsored)
    public function referrals()
    {
        return $this->hasMany(User::class, 'sponsor_id');
    }

    // A user can have one tree entry (their position under upline)
    public function treeEntry()
    {
        return $this->hasOne(ReferralRelationship::class, 'user_id');
    }

    // Upline (tree position)
    public function upline()
    {
        return $this->belongsTo(ReferralRelationship::class, 'upline_id');
    }

    // Sponsor (who recruited this user)
    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }
}

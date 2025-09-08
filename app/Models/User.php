<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'username', 
        'email', 
        'password', 
        'sponsor_id', 
        'tree_round_count'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tree_round_count' => 'integer'
    ];

    // A user can have many referrals (people they sponsored)
    public function referrals()
    {
        return $this->hasMany(User::class, 'sponsor_id');
    }

    // A user can have many tree entries (multiple slots)
    public function treeEntries()
    {
        return $this->hasMany(ReferralRelationship::class, 'user_id');
    }

    // A user can have many user slots
    public function userSlots()
    {
        return $this->hasMany(UserSlot::class, 'user_id');
    }

    // Sponsor (who recruited this user)
    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }
}

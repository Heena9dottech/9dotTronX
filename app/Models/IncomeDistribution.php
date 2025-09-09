<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeDistribution extends Model
{
    protected $table = 'income_distribution';
    protected $fillable = [
        'user_id',
        'level_plan_id',
        'recipient_id',
        'level',
        'percentage',
        'from_address',
        'to_address',
        'hash',
        'status',
        'amount',
        'level_plan_price',
        'description'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'level_plan_price' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function levelPlan(): BelongsTo
    {
        return $this->belongsTo(LevelPlan::class, 'level_plan_id');
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeReceivedBy($query, int $userId)
    {
        return $query->where('recipient_id', $userId);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeAdminDistributions($query)
    {
        return $query->where('level', 'admin');
    }

    public function scopeUplineDistributions($query)
    {
        return $query->whereIn('level', ['upline1', 'upline2', 'upline3', 'upline4']);
    }

    public function scopeSponsorDistributions($query)
    {
        return $query->where('level', 'sponsor');
    }

    public function scopeByLevelPlan($query, int $levelPlanId)
    {
        return $query->where('level_plan_id', $levelPlanId);
    }

    // Static methods for distribution percentages
    public static function getDistributionPercentages(): array
    {
        return [
            'upline1' => 5.00,
            'upline2' => 10.00,
            'upline3' => 20.00,
            'upline4' => 25.00,
            'sponsor' => 40.00,
        ];
    }

    public static function getLevelPlanPrice(int $levelPlanId = null): float
    {
        if ($levelPlanId === null) {
            return 200.00; // Default fallback price
        }

        $levelPlan = LevelPlan::find($levelPlanId);
        return $levelPlan ? $levelPlan->price : 200.00;
    }

    // Helper methods
    public function isAdminDistribution(): bool
    {
        return $this->level === 'admin';
    }

    public function isUplineDistribution(): bool
    {
        return in_array($this->level, ['upline1', 'upline2', 'upline3', 'upline4']);
    }

    public function isSponsorDistribution(): bool
    {
        return $this->level === 'sponsor';
    }

    public function getRecipientName(): string
    {
        if ($this->isAdminDistribution()) {
            return 'Admin';
        }

        return $this->recipient ? ($this->recipient->name ?? $this->recipient->username) : 'Unknown';
    }
}

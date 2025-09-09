<?php

namespace App\Services;

use App\Models\ReferralRelationship;
use App\Models\User;
use App\Models\IncomeDistribution;
use App\Models\LevelPlan;
use Illuminate\Support\Facades\DB;

class IncomeService
{
    /**
     * Distribute level plan income to uplines, sponsor, and admin
     *
     * @param ReferralRelationship $relationship
     * @param float $levelPlanPrice
     * @param int|null $levelPlanId
     * @return array
     */
    public function distributeLevelPlanIncome(ReferralRelationship $relationship, float $levelPlanPrice = null, int $levelPlanId = null): array
    {
        $distributions = [];
        $totalDistributed = 0.00;

        // Use dynamic level plan price if not provided
        if ($levelPlanPrice === null) {
            $levelPlanPrice = IncomeDistribution::getLevelPlanPrice($levelPlanId);
        }

        // Get distribution percentages
        $percentages = IncomeDistribution::getDistributionPercentages();

        // Distribute to uplines
        for ($level = 1; $level <= 4; $level++) {
            $uplineColumn = "upline{$level}";
            $uplineId = $relationship->$uplineColumn;

            if ($uplineId) {
                $percentage = $percentages["upline{$level}"];
                $amount = ($levelPlanPrice * $percentage) / 100;

                $distribution = IncomeDistribution::create([
                    'user_id' => $relationship->user_id,
                    'level_plan_id' => $levelPlanId,
                    'recipient_id' => $uplineId,
                    'level' => "upline{$level}",
                    'percentage' => $percentage,
                    'amount' => $amount,
                    'level_plan_price' => $levelPlanPrice,
                    'description' => "Upline {$level} distribution for user {$relationship->user_username}"
                ]);

                $distributions[] = $distribution;
                $totalDistributed += $amount;
            }
        }

        // Distribute to sponsor
        if ($relationship->sponsor_id) {
            $sponsorPercentage = $percentages['sponsor'];
            $sponsorAmount = ($levelPlanPrice * $sponsorPercentage) / 100;

            $distribution = IncomeDistribution::create([
                'user_id' => $relationship->user_id,
                'level_plan_id' => $levelPlanId,
                'recipient_id' => $relationship->sponsor_id,
                'level' => 'sponsor',
                'percentage' => $sponsorPercentage,
                'amount' => $sponsorAmount,
                'level_plan_price' => $levelPlanPrice,
                'description' => "Sponsor distribution for user {$relationship->user_username}"
            ]);

            $distributions[] = $distribution;
            $totalDistributed += $sponsorAmount;
        }

        // Calculate remaining amount for admin
        $remainingAmount = $levelPlanPrice - $totalDistributed;

        if ($remainingAmount > 0) {
            $adminPercentage = ($remainingAmount / $levelPlanPrice) * 100;

            $distribution = IncomeDistribution::create([
                'user_id' => $relationship->user_id,
                'level_plan_id' => $levelPlanId,
                'recipient_id' => null, // null for admin
                'level' => 'admin',
                'percentage' => $adminPercentage,
                'amount' => $remainingAmount,
                'level_plan_price' => $levelPlanPrice,
                'description' => "Admin distribution for user {$relationship->user_username} (remaining amount)"
            ]);

            $distributions[] = $distribution;
        }

        return $distributions;
    }

    /**
     * Get income distribution summary for a user
     *
     * @param int $userId
     * @return array
     */
    public function getUserIncomeDistribution(int $userId): array
    {
        $distributions = IncomeDistribution::forUser($userId)
            ->with('recipient')
            ->get();

        $summary = [
            'total_level_plan_price' => 0,
            'total_distributed' => 0,
            'distributions' => [],
            'by_level' => []
        ];

        foreach ($distributions as $distribution) {
            $summary['total_level_plan_price'] = $distribution->level_plan_price;
            $summary['total_distributed'] += $distribution->amount;

            $summary['distributions'][] = [
                'level' => $distribution->level,
                'recipient' => $distribution->recipient ? $distribution->recipient->name : 'Admin',
                'percentage' => $distribution->percentage,
                'amount' => $distribution->amount,
                'description' => $distribution->description
            ];

            // Group by level
            if (!isset($summary['by_level'][$distribution->level])) {
                $summary['by_level'][$distribution->level] = [
                    'count' => 0,
                    'total_amount' => 0
                ];
            }
            $summary['by_level'][$distribution->level]['count']++;
            $summary['by_level'][$distribution->level]['total_amount'] += $distribution->amount;
        }

        return $summary;
    }

    /**
     * Get income received by a user from all sources
     *
     * @param int $userId
     * @return array
     */
    public function getUserIncomeReceived(int $userId): array
    {
        $distributions = IncomeDistribution::receivedBy($userId)
            ->with('user')
            ->get();

        $summary = [
            'total_received' => 0,
            'by_level' => [],
            'distributions' => []
        ];

        foreach ($distributions as $distribution) {
            $summary['total_received'] += $distribution->amount;

            $summary['distributions'][] = [
                'from_user' => $distribution->user->name ?? $distribution->user->username,
                'level' => $distribution->level,
                'percentage' => $distribution->percentage,
                'amount' => $distribution->amount,
                'level_plan_price' => $distribution->level_plan_price,
                'date' => $distribution->created_at
            ];

            // Group by level
            if (!isset($summary['by_level'][$distribution->level])) {
                $summary['by_level'][$distribution->level] = [
                    'count' => 0,
                    'total_amount' => 0
                ];
            }
            $summary['by_level'][$distribution->level]['count']++;
            $summary['by_level'][$distribution->level]['total_amount'] += $distribution->amount;
        }

        return $summary;
    }

    /**
     * Get admin income summary
     *
     * @return array
     */
    public function getAdminIncomeSummary(): array
    {
        $adminDistributions = IncomeDistribution::adminDistributions()
            ->with('user')
            ->get();

        $summary = [
            'total_admin_income' => 0,
            'total_level_plans' => $adminDistributions->count(),
            'distributions' => []
        ];

        foreach ($adminDistributions as $distribution) {
            $summary['total_admin_income'] += $distribution->amount;

            $summary['distributions'][] = [
                'from_user' => $distribution->user->name ?? $distribution->user->username,
                'percentage' => $distribution->percentage,
                'amount' => $distribution->amount,
                'level_plan_price' => $distribution->level_plan_price,
                'date' => $distribution->created_at
            ];
        }

        return $summary;
    }

    /**
     * Get income distribution statistics
     *
     * @return array
     */
    public function getIncomeDistributionStats(): array
    {
        $totalDistributions = IncomeDistribution::count();
        $totalAmount = IncomeDistribution::sum('amount');
        $totalLevelPlanPrice = IncomeDistribution::sum('level_plan_price');

        $byLevel = [];
        foreach (['upline1', 'upline2', 'upline3', 'upline4', 'sponsor', 'admin'] as $level) {
            $byLevel[$level] = [
                'count' => IncomeDistribution::byLevel($level)->count(),
                'total_amount' => IncomeDistribution::byLevel($level)->sum('amount')
            ];
        }

        return [
            'total_distributions' => $totalDistributions,
            'total_amount_distributed' => $totalAmount,
            'total_level_plan_price' => $totalLevelPlanPrice,
            'by_level' => $byLevel
        ];
    }

    /**
     * Process level plan purchase and distribute income
     *
     * @param int $userId
     * @param int $levelPlanId
     * @return array
     */
    public function processLevelPlanPurchase(int $userId, int $levelPlanId): array
    {
        $levelPlan = LevelPlan::find($levelPlanId);
        if (!$levelPlan) {
            throw new \Exception('Level plan not found');
        }

        $relationship = ReferralRelationship::where('user_id', $userId)->first();
        if (!$relationship) {
            throw new \Exception('User referral relationship not found');
        }

        return $this->distributeLevelPlanIncome($relationship, $levelPlan->price, $levelPlanId);
    }
}

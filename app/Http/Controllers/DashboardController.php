<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ReferralRelationship;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get counts for dashboard
        $totalUsers = User::where('username', '!=', 'admin')->count();
        $totalReferrals = ReferralRelationship::count();
        $activeUsers = User::where('username', '!=', 'admin')->where('created_at', '>=', now()->subDays(30))->count();
        $pendingReferrals = 0; // Removed status column reference
        
        // Get recent users
        $recentUsers = User::where('username', '!=', 'admin')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Get top sponsors (users with most referrals)
        // $topSponsors = User::select('users.*')
        //     ->leftjoin('referral_relationships', 'users.id', '=', 'referral_relationships.sponsor_id')
        //     ->where('users.username', '!=', 'admin')
        //     ->groupBy('users.id')
        //     ->orderByRaw('COUNT(referral_relationships.id) DESC')
        //     ->take(5)
        //     ->get();
        
            $topSponsors = [];
        // Get MLM tree statistics
        $treeStats = $this->getMLMTreeStats();
        
        return view('dashboard', compact(
            'totalUsers',
            'totalReferrals', 
            'activeUsers',
            'pendingReferrals',
            'recentUsers',
            'topSponsors',
            'treeStats'
        ));
    }

    /**
     * Get MLM tree statistics for dashboard
     */
    private function getMLMTreeStats()
    {
        // Get all tree owners (users who have completed trees or are in progress)
        $treeOwners = User::where('username', '!=', 'admin')
            ->whereHas('treeEntry')
            ->with(['treeEntry'])
            ->get();

        $stats = [
            'total_tree_owners' => $treeOwners->count(),
            'completed_trees' => 0,
            'in_progress_trees' => 0,
            'total_tree_rounds' => 0,
            'tree_rounds_breakdown' => []
        ];

        foreach ($treeOwners as $owner) {
            // Count members in each round for this tree owner
            $rounds = ReferralRelationship::where('tree_owner_id', $owner->id)
                ->select('tree_round')
                ->distinct()
                ->pluck('tree_round')
                ->toArray();

            $stats['total_tree_rounds'] += count($rounds);

            foreach ($rounds as $round) {
                $memberCount = ReferralRelationship::where('tree_owner_id', $owner->id)
                    ->where('tree_round', $round)
                    ->where('is_spillover_slot', false)
                    ->count();

                if (!isset($stats['tree_rounds_breakdown'][$round])) {
                    $stats['tree_rounds_breakdown'][$round] = 0;
                }
                $stats['tree_rounds_breakdown'][$round]++;

                if ($memberCount >= 30) {
                    $stats['completed_trees']++;
                } else {
                    $stats['in_progress_trees']++;
                }
            }
        }

        return $stats;
    }
}

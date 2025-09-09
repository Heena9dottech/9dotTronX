<?php

namespace App\Http\Controllers;

use App\Services\IncomeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IncomeController extends Controller
{
    protected $incomeService;

    public function __construct(IncomeService $incomeService)
    {
        $this->incomeService = $incomeService;
    }

    /**
     * Get user's income distribution summary
     */
    public function getUserIncomeDistribution(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $summary = $this->incomeService->getUserIncomeDistribution($userId);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get user's income received summary
     */
    public function getUserIncomeReceived(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $summary = $this->incomeService->getUserIncomeReceived($userId);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get admin income summary
     */
    public function getAdminIncomeSummary(Request $request): JsonResponse
    {
        // Add admin check here if needed
        $summary = $this->incomeService->getAdminIncomeSummary();

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get income distribution statistics
     */
    public function getIncomeStats(Request $request): JsonResponse
    {
        // Add admin check here if needed
        $stats = $this->incomeService->getIncomeDistributionStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Process level plan purchase and distribute income
     */
    public function processLevelPlanPurchase(Request $request): JsonResponse
    {
        $request->validate([
            'level_plan_id' => 'required|exists:level_plans,id'
        ]);

        try {
            $userId = $request->user()->id;
            $levelPlanId = $request->level_plan_id;

            $distributions = $this->incomeService->processLevelPlanPurchase($userId, $levelPlanId);

            return response()->json([
                'success' => true,
                'message' => 'Level plan purchase processed and income distributed successfully',
                'data' => [
                    'distributions_count' => count($distributions),
                    'distributions' => $distributions
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

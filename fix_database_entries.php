<?php
/**
 * Fix Database Entries Script
 * This script will fix the incorrect binary tree structure
 * Moving David from Mike's LEFT slot to Mike's RIGHT slot
 */

require_once 'vendor/autoload.php';

use App\Models\ReferralRelationship;
use Illuminate\Support\Facades\DB;

echo "=== FIXING DATABASE ENTRIES ===\n\n";

try {
    DB::transaction(function () {
        // Find David's entry (should be under Mike's LEFT slot incorrectly)
        $davidEntry = ReferralRelationship::where('user_username', 'david')
            ->where('upline_username', 'mike')
            ->where('position', 'L')
            ->first();
        
        if ($davidEntry) {
            echo "Found David's entry:\n";
            echo "   - ID: {$davidEntry->id}\n";
            echo "   - User: {$davidEntry->user_username} (ID: {$davidEntry->user_id})\n";
            echo "   - Upline: {$davidEntry->upline_username} (ID: {$davidEntry->upline_id})\n";
            echo "   - Current Position: {$davidEntry->position}\n";
            echo "   - Tree Owner: {$davidEntry->tree_owner_username} (ID: {$davidEntry->tree_owner_id})\n\n";
            
            // Update David's position from LEFT to RIGHT
            $davidEntry->update(['position' => 'R']);
            
            echo "✅ FIXED: David moved from Mike's LEFT slot to RIGHT slot\n\n";
        } else {
            echo "❌ David's entry not found or already correct\n\n";
        }
        
        // Verify the fix
        echo "=== VERIFICATION ===\n";
        
        $emmaEntry = ReferralRelationship::where('user_username', 'emma')
            ->where('upline_username', 'mike')
            ->where('position', 'L')
            ->first();
            
        $davidEntryAfter = ReferralRelationship::where('user_username', 'david')
            ->where('upline_username', 'mike')
            ->where('position', 'R')
            ->first();
        
        if ($emmaEntry) {
            echo "✅ Emma: Under Mike (ID: {$emmaEntry->upline_id}) in LEFT position\n";
        }
        
        if ($davidEntryAfter) {
            echo "✅ David: Under Mike (ID: {$davidEntryAfter->upline_id}) in RIGHT position\n";
        }
        
        if ($emmaEntry && $davidEntryAfter) {
            echo "\n🎉 SUCCESS: Binary tree structure is now correct!\n";
            echo "   - Mike now has Emma in LEFT slot and David in RIGHT slot\n";
            echo "   - Each person has exactly 2 slots (left and right)\n";
            echo "   - New members will be placed correctly with left-right priority\n";
        }
    });
    
    echo "\n=== DATABASE FIX COMPLETED ===\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>

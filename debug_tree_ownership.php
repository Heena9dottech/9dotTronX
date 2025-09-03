<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\ReferralRelationship;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG: Tree Ownership for John's Downline ===\n\n";

// Find John
$john = User::where('username', 'john')->first();

if ($john) {
    echo "John found: ID {$john->id}\n\n";
    
    // Check all referral relationships
    echo "1. All Referral Relationships:\n";
    $allRelationships = ReferralRelationship::all();
    
    foreach ($allRelationships as $rel) {
        echo "  ID: {$rel->id}, User: {$rel->user_username}, Tree Owner: {$rel->tree_owner_username}, Round: {$rel->tree_round}\n";
    }
    
    echo "\n";
    
    // Check John's direct relationships
    echo "2. John's Direct Relationships:\n";
    $johnRelationships = ReferralRelationship::where('user_id', $john->id)->get();
    
    foreach ($johnRelationships as $rel) {
        echo "  ID: {$rel->id}, User: {$rel->user_username}, Tree Owner: {$rel->tree_owner_username}, Round: {$rel->tree_round}\n";
    }
    
    echo "\n";
    
    // Check who has John as tree owner
    echo "3. Users with John as Tree Owner:\n";
    $johnTreeMembers = ReferralRelationship::where('tree_owner_id', $john->id)->get();
    
    foreach ($johnTreeMembers as $rel) {
        echo "  ID: {$rel->id}, User: {$rel->user_username}, Tree Owner: {$rel->tree_owner_username}, Round: {$rel->tree_round}\n";
    }
    
    echo "\n";
    
    // Check the count method logic
    echo "4. Count Method Debug:\n";
    $countQuery = ReferralRelationship::where('tree_owner_id', $john->id)
        ->where('is_spillover_slot', false)
        ->where('tree_round', 1);
    
    echo "   Raw SQL: " . $countQuery->toSql() . "\n";
    echo "   Bindings: " . json_encode($countQuery->getBindings()) . "\n";
    echo "   Count: " . $countQuery->count() . "\n";
    
} else {
    echo "John not found!\n";
}

echo "\n=== Debug Complete ===\n";

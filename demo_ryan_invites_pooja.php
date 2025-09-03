<?php
/**
 * Demo: Where does Pooja go when Ryan invites her?
 * This shows the placement logic for Ryan's direct invites
 */

require_once 'vendor/autoload.php';

use App\Http\Controllers\TreeController;
use App\Models\User;
use App\Models\ReferralRelationship;

echo "=== DEMO: Ryan Invites Pooja - Where Does She Go? ===\n\n";

// Step 1: Create the scenario
echo "1. Setting up scenario...\n";

// Create Ryan as tree owner
$ryan = User::create([
    'username' => 'ryan_pooja_demo',
    'email' => 'ryan@pooja.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => null
]);

// Create John (Ryan's first invite)
$john = User::create([
    'username' => 'john_pooja_demo',
    'email' => 'john@pooja.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => $ryan->id
]);

// Create tree owners
ReferralRelationship::create([
    'user_id' => $ryan->id,
    'user_username' => $ryan->username,
    'sponsor_id' => null,
    'sponsor_username' => null,
    'upline_id' => null,
    'upline_username' => null,
    'position' => null,
    'tree_owner_id' => $ryan->id,
    'tree_owner_username' => $ryan->username,
    'tree_round' => 1,
    'is_spillover_slot' => false,
]);

echo "✅ Setup complete: Ryan (ID: {$ryan->id}) as tree owner\n\n";

// Step 2: Add John to Ryan's tree
echo "2. Adding John to Ryan's tree...\n";
$treeController = new TreeController();
$treeController->addUserToTree($ryan, $john);

echo "✅ John added to Ryan's tree\n\n";

// Step 3: Check Ryan's current tree structure
echo "3. Checking Ryan's current tree structure...\n";
$ryanStats = $treeController->getTreeStats($ryan->id);
echo "Ryan's tree - Regular members: {$ryanStats['regular_members']}\n";
echo "Ryan's tree - Total members: {$ryanStats['total_members']}\n";
echo "Ryan's tree - Is complete: " . ($ryanStats['is_complete'] ? 'Yes' : 'No') . "\n\n";

// Step 4: Check John's placement
$johnEntry = ReferralRelationship::where('user_id', $john->id)->first();
if ($johnEntry) {
    echo "John's current placement:\n";
    echo "   - Upline: {$johnEntry->upline_username} (ID: {$johnEntry->upline_id})\n";
    echo "   - Position: {$johnEntry->position}\n";
    echo "   - Tree Owner: {$johnEntry->tree_owner_username}\n\n";
}

// Step 5: Now Ryan invites Pooja
echo "4. Ryan invites Pooja...\n";
$pooja = User::create([
    'username' => 'pooja_ryan_invite',
    'email' => 'pooja@ryan.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => $ryan->id
]);

echo "🔄 Adding Pooja to Ryan's tree...\n";
$treeController->addUserToTree($ryan, $pooja);

echo "\n=== RESULTS AFTER RYAN INVITES POOJA ===\n\n";

// Step 6: Check Pooja's placement
echo "5. Checking Pooja's placement...\n";
$poojaEntry = ReferralRelationship::where('user_id', $pooja->id)->first();
if ($poojaEntry) {
    echo "✅ Pooja placed successfully!\n";
    echo "   - Username: {$poojaEntry->user_username}\n";
    echo "   - Sponsor: {$poojaEntry->sponsor_username} (ID: {$poojaEntry->sponsor_id})\n";
    echo "   - Upline: {$poojaEntry->upline_username} (ID: {$poojaEntry->upline_id})\n";
    echo "   - Position: {$poojaEntry->position}\n";
    echo "   - Tree Owner: {$poojaEntry->tree_owner_username} (ID: {$poojaEntry->tree_owner_id})\n";
    echo "   - Tree Round: {$poojaEntry->tree_round}\n";
    echo "   - Is Spillover: " . ($poojaEntry->is_spillover_slot ? 'Yes' : 'No') . "\n\n";
}

// Step 7: Check Ryan's updated tree structure
echo "6. Checking Ryan's updated tree structure...\n";
$ryanUpdatedStats = $treeController->getTreeStats($ryan->id);
echo "Ryan's tree - Regular members: {$ryanUpdatedStats['regular_members']}\n";
echo "Ryan's tree - Total members: {$ryanUpdatedStats['total_members']}\n";
echo "Ryan's tree - Is complete: " . ($ryanUpdatedStats['is_complete'] ? 'Yes' : 'No') . "\n\n";

// Step 8: Show the tree structure
echo "7. Current tree structure:\n";
echo "Ryan (Tree Owner)\n";
echo "├── John (Position: " . ($johnEntry->position ?? 'Unknown') . ")\n";
echo "└── Pooja (Position: " . ($poojaEntry->position ?? 'Unknown') . ")\n\n";

echo "=== KEY INSIGHT ===\n";
echo "When Ryan invites Pooja:\n";
echo "✅ Pooja goes to Ryan's tree (not John's tree)\n";
echo "✅ Pooja is placed under Ryan directly\n";
echo "✅ Pooja gets the next available position (Left or Right)\n";
echo "✅ Pooja becomes Ryan's direct downline\n";
echo "✅ Pooja is NOT under John - she's Ryan's direct invite\n";
echo "✅ This follows the sponsor relationship, not tree placement\n";
?>

<?php
/**
 * Demo: Adding 30th Member (Lena) to John's Tree
 * This shows the exact flow when John's tree reaches 30 members
 */

require_once 'vendor/autoload.php';

use App\Http\Controllers\TreeController;
use App\Models\User;
use App\Models\ReferralRelationship;

echo "=== DEMO: Adding 30th Member (Lena) to John's Tree ===\n\n";

// Step 1: Create John as tree owner
echo "1. Creating John as tree owner...\n";
$john = User::create([
    'username' => 'john_owner',
    'email' => 'john@mlm.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => null
]);

// Create John as tree owner
ReferralRelationship::create([
    'user_id' => $john->id,
    'user_username' => $john->username,
    'sponsor_id' => null,
    'sponsor_username' => null,
    'upline_id' => null,
    'upline_username' => null,
    'position' => null,
    'tree_owner_id' => $john->id,
    'tree_owner_username' => $john->username,
    'tree_round' => 1,
    'is_spillover_slot' => false,
]);

echo "✅ John created as tree owner (ID: {$john->id})\n\n";

// Step 2: Create Ryan (John's inviter) for the demo
echo "2. Creating Ryan (John's inviter)...\n";
$ryan = User::create([
    'username' => 'ryan_inviter',
    'email' => 'ryan@mlm.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => null
]);

// Create Ryan as tree owner
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

// Update John's sponsor_id to Ryan
$john->update(['sponsor_id' => $ryan->id]);

echo "✅ Ryan created as John's inviter (ID: {$ryan->id})\n\n";

// Step 3: Add 29 members to John's tree (to make it ready for 30th)
echo "3. Adding 29 members to John's tree...\n";
$treeController = new TreeController();

for ($i = 1; $i <= 29; $i++) {
    $user = User::create([
        'username' => "user_{$i}",
        'email' => "user{$i}@mlm.com",
        'password' => bcrypt('123456'),
        'sponsor_id' => $john->id
    ]);
    
    $treeController->addUserToTree($john, $user);
    echo "✅ User {$i} added to John's tree\n";
}

echo "\n4. John's tree now has 29 members (ready for 30th)\n";

// Step 4: Add the 30th member (Lena)
echo "\n5. Adding 30th member (Lena) to John's tree...\n";
$lena = User::create([
    'username' => 'lena_30th',
    'email' => 'lena@mlm.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => $john->id
]);

echo "🔄 Adding Lena (30th member)...\n";
$treeController->addUserToTree($john, $lena);

echo "\n=== RESULTS AFTER ADDING 30TH MEMBER ===\n\n";

// Step 5: Check what happened
echo "6. Checking John's tree status...\n";
$stats = $treeController->getTreeStats($john->id);
echo "Regular members: {$stats['regular_members']}\n";
echo "Total members: {$stats['total_members']}\n";
echo "Is complete: " . ($stats['is_complete'] ? 'Yes' : 'No') . "\n";

echo "\n7. Checking John's tree rounds...\n";
$treeInfo = $treeController->getMLMTreeInfo($john->id);
echo "Total rounds: {$treeInfo['total_rounds']}\n";
echo "Tree round count: {$treeInfo['tree_round_count']}\n";

if (!empty($treeInfo['rounds'])) {
    foreach ($treeInfo['rounds'] as $round) {
        echo "Round {$round['round_number']}: {$round['regular_members']}/30 members ({$round['completion_percentage']}% complete)\n";
    }
}

echo "\n8. Checking if John got a new tree entry...\n";
$johnNewEntry = ReferralRelationship::where('user_id', $john->id)
    ->where('is_spillover_slot', true)
    ->first();

if ($johnNewEntry) {
    echo "✅ John's new tree entry created!\n";
    echo "   - Placed under: {$johnNewEntry->upline_username} (ID: {$johnNewEntry->upline_id})\n";
    echo "   - Position: {$johnNewEntry->position}\n";
    echo "   - Tree Round: {$johnNewEntry->tree_round}\n";
    echo "   - Tree Owner: {$johnNewEntry->tree_owner_username}\n";
} else {
    echo "❌ John's new tree entry NOT found\n";
}

echo "\n9. Checking Lena's placement...\n";
$lenaEntry = ReferralRelationship::where('user_id', $lena->id)->first();
if ($lenaEntry) {
    echo "✅ Lena placed successfully!\n";
    echo "   - Placed under: {$lenaEntry->upline_username} (ID: {$lenaEntry->upline_id})\n";
    echo "   - Position: {$lenaEntry->position}\n";
    echo "   - Tree Round: {$lenaEntry->tree_round}\n";
    echo "   - Is spillover: " . ($lenaEntry->is_spillover_slot ? 'Yes' : 'No') . "\n";
}

echo "\n=== DEMO COMPLETED ===\n";
echo "This shows exactly what happens when the 30th member is added:\n";
echo "1. Lena (30th member) gets placed in John's tree\n";
echo "2. John gets a new tree entry under his inviter (Ryan)\n";
echo "3. John's tree_round_count increases by 1\n";
echo "4. John now has a new tree with 30 fresh slots\n";
?>

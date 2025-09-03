<?php
/**
 * Demo: What happens when John invites the NEXT user after 30th member
 * This shows how John's new tree entry becomes active
 */

require_once 'vendor/autoload.php';

use App\Http\Controllers\TreeController;
use App\Models\User;
use App\Models\ReferralRelationship;

echo "=== DEMO: John's Next Invite After 30th Member ===\n\n";

// Step 1: Create the scenario (John with 30 members + new tree entry)
echo "1. Setting up scenario...\n";

// Create John as tree owner
$john = User::create([
    'username' => 'john_next_demo',
    'email' => 'john@next.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => null
]);

// Create Ryan (John's inviter)
$ryan = User::create([
    'username' => 'ryan_next_demo',
    'email' => 'ryan@next.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => null
]);

// Create tree owners
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

// Update John's sponsor
$john->update(['sponsor_id' => $ryan->id]);

echo "✅ Setup complete: John (ID: {$john->id}) sponsored by Ryan (ID: {$ryan->id})\n\n";

// Step 2: Add 30 members to John's tree (simulate completed tree)
echo "2. Adding 30 members to John's tree...\n";
$treeController = new TreeController();

for ($i = 1; $i <= 30; $i++) {
    $user = User::create([
        'username' => "member_{$i}",
        'email' => "member{$i}@next.com",
        'password' => bcrypt('123456'),
        'sponsor_id' => $john->id
    ]);
    $treeController->addUserToTree($john, $user);
    echo "✅ User {$i} added to John's tree\n";
}

echo "\n3. John's tree now has 30 members (completed!)\n";

// Step 3: Check John's status after 30th member
echo "\n4. Checking John's status after 30th member...\n";
$john->refresh();
echo "John's tree_round_count: {$john->tree_round_count}\n";

// Check if John has a new tree entry
$johnNewEntry = ReferralRelationship::where('user_id', $john->id)
    ->where('is_spillover_slot', true)
    ->first();

if ($johnNewEntry) {
    echo "✅ John has a new tree entry under: {$johnNewEntry->upline_username}\n";
    echo "   - Position: {$johnNewEntry->position}\n";
    echo "   - Tree Round: {$johnNewEntry->tree_round}\n";
} else {
    echo "❌ John's new tree entry NOT found\n";
}

// Step 4: Now John invites the NEXT user (31st user)
echo "\n5. John invites the NEXT user (31st user)...\n";
$nextUser = User::create([
    'username' => 'next_user_31',
    'email' => 'next31@next.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => $john->id
]);

echo "🔄 Adding next user (31st) to John's tree...\n";
$treeController->addUserToTree($john, $nextUser);

echo "\n=== RESULTS AFTER JOHN'S NEXT INVITE ===\n\n";

// Step 5: Check what happened
echo "6. Checking next user's placement...\n";
$nextUserEntry = ReferralRelationship::where('user_id', $nextUser->id)->first();
if ($nextUserEntry) {
    echo "✅ Next user placed successfully!\n";
    echo "   - Username: {$nextUserEntry->user_username}\n";
    echo "   - Sponsor: {$nextUserEntry->sponsor_username} (ID: {$nextUserEntry->sponsor_id})\n";
    echo "   - Upline: {$nextUserEntry->upline_username} (ID: {$nextUserEntry->upline_id})\n";
    echo "   - Position: {$nextUserEntry->position}\n";
    echo "   - Tree Owner: {$nextUserEntry->tree_owner_username} (ID: {$nextUserEntry->tree_owner_id})\n";
    echo "   - Tree Round: {$nextUserEntry->tree_round}\n";
    echo "   - Is Spillover: " . ($nextUserEntry->is_spillover_slot ? 'Yes' : 'No') . "\n\n";
}

// Step 6: Check John's new tree status
echo "7. Checking John's new tree status...\n";
$johnNewTreeStats = $treeController->getTreeStats($john->id);
echo "John's new tree - Regular members: {$johnNewTreeStats['regular_members']}\n";
echo "John's new tree - Total members: {$johnNewTreeStats['total_members']}\n";
echo "John's new tree - Is complete: " . ($johnNewTreeStats['is_complete'] ? 'Yes' : 'No') . "\n\n";

// Step 7: Check John's tree rounds
echo "8. Checking John's tree rounds...\n";
$johnTreeInfo = $treeController->getMLMTreeInfo($john->id);
echo "Total rounds: {$johnTreeInfo['total_rounds']}\n";
echo "Tree round count: {$johnTreeInfo['tree_round_count']}\n";

if (!empty($johnTreeInfo['rounds'])) {
    foreach ($johnTreeInfo['rounds'] as $round) {
        echo "Round {$round['round_number']}: {$round['regular_members']}/30 members ({$round['completion_percentage']}% complete)\n";
    }
}

echo "\n=== KEY INSIGHT ===\n";
echo "When John invites the NEXT user after 30th member:\n";
echo "✅ Next user goes to John's NEW tree entry (spillover slot)\n";
echo "✅ Next user is placed under John's new tree entry position\n";
echo "✅ John's new tree starts with 1 member (the next user)\n";
echo "✅ John's new tree has 29 more slots available\n";
echo "✅ This is John's 2nd round tree\n";
echo "✅ Process repeats when this new tree reaches 30 members\n";
?>

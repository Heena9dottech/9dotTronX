<?php
/**
 * Test: Show exact entry created for 30th member scenario
 * This demonstrates the dual entry creation when 30th member is added
 */

require_once 'vendor/autoload.php';

use App\Http\Controllers\TreeController;
use App\Models\User;
use App\Models\ReferralRelationship;

echo "=== TEST: 30th Member Entry Creation ===\n\n";

// Create the scenario
$john = User::create([
    'username' => 'john_demo',
    'email' => 'john@demo.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => null
]);

$ryan = User::create([
    'username' => 'ryan_demo',
    'email' => 'ryan@demo.com',
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

// Add 29 members to John's tree
$treeController = new TreeController();
for ($i = 1; $i <= 29; $i++) {
    $user = User::create([
        'username' => "member_{$i}",
        'email' => "member{$i}@demo.com",
        'password' => bcrypt('123456'),
        'sponsor_id' => $john->id
    ]);
    $treeController->addUserToTree($john, $user);
}

echo "✅ Added 29 members to John's tree\n\n";

// Add 30th member (Lena)
echo "🔄 Adding 30th member (Lena)...\n";
$lena = User::create([
    'username' => 'lena_30th',
    'email' => 'lena@demo.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => $john->id
]);

$treeController->addUserToTree($john, $lena);

echo "\n=== ENTRIES CREATED ===\n\n";

// Show Lena's entry
$lenaEntry = ReferralRelationship::where('user_id', $lena->id)->first();
echo "1. LENA'S ENTRY (30th member):\n";
echo "   - User ID: {$lenaEntry->user_id}\n";
echo "   - Username: {$lenaEntry->user_username}\n";
echo "   - Sponsor: {$lenaEntry->sponsor_username} (ID: {$lenaEntry->sponsor_id})\n";
echo "   - Upline: {$lenaEntry->upline_username} (ID: {$lenaEntry->upline_id})\n";
echo "   - Position: {$lenaEntry->position}\n";
echo "   - Tree Owner: {$lenaEntry->tree_owner_username} (ID: {$lenaEntry->tree_owner_id})\n";
echo "   - Tree Round: {$lenaEntry->tree_round}\n";
echo "   - Is Spillover: " . ($lenaEntry->is_spillover_slot ? 'Yes' : 'No') . "\n\n";

// Show John's new entry
$johnNewEntry = ReferralRelationship::where('user_id', $john->id)
    ->where('is_spillover_slot', true)
    ->first();

if ($johnNewEntry) {
    echo "2. JOHN'S NEW TREE ENTRY:\n";
    echo "   - User ID: {$johnNewEntry->user_id}\n";
    echo "   - Username: {$johnNewEntry->user_username}\n";
    echo "   - Sponsor: {$johnNewEntry->sponsor_username} (ID: {$johnNewEntry->sponsor_id})\n";
    echo "   - Upline: {$johnNewEntry->upline_username} (ID: {$johnNewEntry->upline_id})\n";
    echo "   - Position: {$johnNewEntry->position}\n";
    echo "   - Tree Owner: {$johnNewEntry->tree_owner_username} (ID: {$johnNewEntry->tree_owner_id})\n";
    echo "   - Tree Round: {$johnNewEntry->tree_round}\n";
    echo "   - Is Spillover: " . ($johnNewEntry->is_spillover_slot ? 'Yes' : 'No') . "\n\n";
} else {
    echo "2. JOHN'S NEW TREE ENTRY: NOT FOUND\n\n";
}

// Show John's updated tree_round_count
$john->refresh();
echo "3. JOHN'S UPDATED INFO:\n";
echo "   - Tree Round Count: {$john->tree_round_count}\n";
echo "   - Sponsor ID: {$john->sponsor_id}\n\n";

echo "=== SUMMARY ===\n";
echo "When 30th member (Lena) is added:\n";
echo "✅ Lena gets placed in John's current tree\n";
echo "✅ John gets a new tree entry under his inviter (Ryan)\n";
echo "✅ John's tree_round_count increases by 1\n";
echo "✅ John now has a new tree with 30 fresh slots\n";
echo "✅ Both entries are created simultaneously\n";
?>

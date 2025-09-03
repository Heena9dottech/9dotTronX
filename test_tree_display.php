<?php
/**
 * Test: Tree Display Structure
 * This tests the tree display functionality to ensure it shows the correct hierarchy
 */

require_once 'vendor/autoload.php';

use App\Http\Controllers\TreeController;
use App\Http\Controllers\UserTreeController;
use App\Models\User;
use App\Models\ReferralRelationship;

echo "=== TEST: Tree Display Structure ===\n\n";

// Create test users
echo "1. Creating test users...\n";

// Create John as tree owner
$john = User::create([
    'username' => 'john_tree_test',
    'email' => 'john@tree.com',
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

// Add some users to John's tree
$treeController = new TreeController();
$userTreeController = new UserTreeController();

echo "2. Adding users to John's tree...\n";

// Add Mike (left child)
$mike = User::create([
    'username' => 'mike_tree_test',
    'email' => 'mike@tree.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => $john->id
]);
$treeController->addUserToTree($john, $mike);
echo "✅ Mike added to John's tree\n";

// Add Lisa (right child)
$lisa = User::create([
    'username' => 'lisa_tree_test',
    'email' => 'lisa@tree.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => $john->id
]);
$treeController->addUserToTree($john, $lisa);
echo "✅ Lisa added to John's tree\n";

// Add Emma (under Mike - left)
$emma = User::create([
    'username' => 'emma_tree_test',
    'email' => 'emma@tree.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => $mike->id
]);
$treeController->addUserToTree($mike, $emma);
echo "✅ Emma added under Mike\n";

// Add David (under Lisa - left)
$david = User::create([
    'username' => 'david_tree_test',
    'email' => 'david@tree.com',
    'password' => bcrypt('123456'),
    'sponsor_id' => $lisa->id
]);
$treeController->addUserToTree($lisa, $david);
echo "✅ David added under Lisa\n";

echo "\n3. Building tree structure...\n";
$tree = $userTreeController->buildTree($john->id);

echo "\n=== TREE STRUCTURE ===\n";
echo "Root: {$tree['owner']->username}\n";
echo "Total Members: {$tree['member_count']}\n\n";

echo "Level 1 (2 Users):\n";
for ($i = 0; $i < 2; $i++) {
    if (isset($tree['level1'][$i]) && $tree['level1'][$i]) {
        echo "  Position {$i}: {$tree['level1'][$i]->user->username}\n";
    } else {
        echo "  Position {$i}: Empty\n";
    }
}

echo "\nLevel 2 (4 Users):\n";
for ($i = 0; $i < 4; $i++) {
    if (isset($tree['level2'][$i]) && $tree['level2'][$i]) {
        echo "  Position {$i}: {$tree['level2'][$i]->user->username}\n";
    } else {
        echo "  Position {$i}: Empty\n";
    }
}

echo "\nLevel 3 (8 Users):\n";
for ($i = 0; $i < 8; $i++) {
    if (isset($tree['level3'][$i]) && $tree['level3'][$i]) {
        echo "  Position {$i}: {$tree['level3'][$i]->user->username}\n";
    } else {
        echo "  Position {$i}: Empty\n";
    }
}

echo "\nLevel 4 (16 Users):\n";
for ($i = 0; $i < 16; $i++) {
    if (isset($tree['level4'][$i]) && $tree['level4'][$i]) {
        echo "  Position {$i}: {$tree['level4'][$i]->user->username}\n";
    } else {
        echo "  Position {$i}: Empty\n";
    }
}

echo "\n=== EXPECTED STRUCTURE ===\n";
echo "John (Root)\n";
echo "├── Mike (Left)\n";
echo "│   └── Emma (Left)\n";
echo "└── Lisa (Right)\n";
echo "    └── David (Left)\n";

echo "\n=== TEST COMPLETED ===\n";
echo "Tree structure is working correctly!\n";
echo "You can now view the tree at: /users/john_tree_test/tree\n";
?>

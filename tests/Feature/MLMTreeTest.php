<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ReferralRelationship;
use App\Http\Controllers\TreeController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class MLMTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_30th_user_creates_tree_owner_spillover_slot()
    {
        // Create admin user
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@mlm.com',
            'password' => bcrypt('password'),
            'sponsor_id' => null
        ]);

        // Create tree owner (John)
        $john = User::create([
            'username' => 'john',
            'email' => 'john@mlm.com',
            'password' => bcrypt('password'),
            'sponsor_id' => $admin->id
        ]);

        // Create John's tree entry (as tree owner)
        ReferralRelationship::create([
            'user_id' => $john->id,
            'user_username' => $john->username,
            'sponsor_id' => $admin->id,
            'sponsor_username' => $admin->username,
            'upline_id' => null,
            'upline_username' => null,
            'position' => null,
            'tree_owner_id' => $john->id,
            'tree_owner_username' => $john->username,
            'tree_round' => 1,
            'is_spillover_slot' => false,
        ]);

        // Create 28 users under John's tree to simulate a nearly full tree
        // We'll create a simple binary tree structure
        $users = [];
        for ($i = 1; $i <= 28; $i++) {
            $user = User::create([
                'username' => "user{$i}",
                'email' => "user{$i}@mlm.com",
                'password' => bcrypt('password'),
                'sponsor_id' => $john->id
            ]);
            $users[] = $user;

            // Create a simple binary tree structure
            // First 2 users go under John (left and right)
            if ($i <= 2) {
                $uplineId = $john->id;
                $position = ($i == 1) ? 'L' : 'R';
            } else {
                // For simplicity, put remaining users under the first user
                $uplineId = $users[0]->id;
                $position = ($i % 2 == 1) ? 'L' : 'R';
            }

            ReferralRelationship::create([
                'user_id' => $user->id,
                'user_username' => $user->username,
                'sponsor_id' => $john->id,
                'sponsor_username' => $john->username,
                'upline_id' => $uplineId,
                'upline_username' => User::find($uplineId)->username,
                'position' => $position,
                'tree_owner_id' => $john->id,
                'tree_owner_username' => $john->username,
                'tree_round' => 1,
                'is_spillover_slot' => false,
            ]);
        }

        // Verify we have 29 entries in John's tree (28 users + John himself)
        $countBefore = ReferralRelationship::where('tree_owner_id', $john->id)
            ->where('is_spillover_slot', false)
            ->where('tree_round', 1)
            ->count();
        
        $this->assertEquals(29, $countBefore);

        // Create the 30th user (Lena)
        $lena = User::create([
            'username' => 'lena',
            'email' => 'lena@mlm.com',
            'password' => bcrypt('password'),
            'sponsor_id' => $john->id
        ]);

        // Use the TreeController to add Lena
        $controller = new TreeController();
        $controller->addUserToTree($john, $lena);

        // Verify Lena was added
        $lenaEntry = ReferralRelationship::where('user_id', $lena->id)->first();
        $this->assertNotNull($lenaEntry);
        $this->assertEquals($john->id, $lenaEntry->tree_owner_id);
        $this->assertEquals(1, $lenaEntry->tree_round);
        $this->assertEquals(0, $lenaEntry->is_spillover_slot); // 0 = false in database

        // Verify John's spillover slot was created
        $johnSpilloverEntry = ReferralRelationship::where('user_id', $john->id)
            ->where('is_spillover_slot', 1)
            ->first();
        
        $this->assertNotNull($johnSpilloverEntry, 'John\'s spillover slot should be created');
        $this->assertEquals($john->id, $johnSpilloverEntry->tree_owner_id);
        $this->assertEquals(2, $johnSpilloverEntry->tree_round, 'John\'s spillover slot should be in round 2');
        $this->assertEquals(1, $johnSpilloverEntry->is_spillover_slot); // 1 = true in database

        // Verify the spillover slot is in John's own tree (not under admin)
        $this->assertNotEquals($admin->id, $johnSpilloverEntry->upline_id, 'John\'s spillover slot should not be under admin');
        
        // Verify the spillover slot is not in the same position as Lena
        $this->assertFalse(
            $johnSpilloverEntry->upline_id == $lenaEntry->upline_id && 
            $johnSpilloverEntry->position == $lenaEntry->position,
            'John\'s spillover slot should not be in the same position as Lena'
        );

        // Verify total count is now 31 (29 original + Lena + John's spillover)
        $totalCount = ReferralRelationship::where('tree_owner_id', $john->id)->count();
        $this->assertEquals(31, $totalCount);
    }
}

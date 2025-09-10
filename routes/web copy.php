<?php

use App\Http\Controllers\BuySlotTreeController;
use App\Http\Controllers\TreeController;
use App\Http\Controllers\UserTreeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Tree display routes - moved to top to avoid conflicts
Route::get('/tree-display/{id?}/{round?}', [BuySlotTreeController::class, 'displayTreeSimple'])->name('tree.display');
// Route::get('/tree-display/{id}/{round}', [BuySlotTreeController::class, 'displayTree'])->name('tree.display.round');

Route::get('/add-user-form', function () {
    $users = \App\Models\User::where('username', '!=', 'admin')
        ->orderBy('id', 'asc')
        ->get(); // fetch all users except admin, ordered by ID for sponsor dropdown
    
    $plans = \App\Models\LevelPlan::active()->ordered()->limit(3)->get(); // fetch all active level plans
    
    return view('add_user', compact('users', 'plans'));
})->name('add-user-form');

Route::post('/add-user', [TreeController::class, 'addUser'])->name('add-user');
Route::post('/buy-level-plan', [TreeController::class, 'buyLevelPlan'])->name('buy-level-plan');

// New routes for user tree display
Route::get('/users', [UserTreeController::class, 'index'])->name('users.index');
Route::get('/users/{username}/tree/{round?}', [UserTreeController::class, 'showTree'])->name('users.tree');

// Test route to debug the issue
Route::get('/test-tree/{id}', function($id) {
    return "Test route working with ID: " . $id;
})->name('test.tree');

// Simple test route for tree-display
Route::get('/simple-tree/{id}', function($id) {
    return "Simple tree route working with ID: " . $id;
})->name('simple.tree');

Route::get('/tree-overview', [UserTreeController::class, 'overview'])->name('tree.overview');

// API routes for tree data
Route::get('/api/users/{username}/tree/stats', [UserTreeController::class, 'getTreeStats'])->name('api.users.tree.stats');
Route::get('/api/users/{username}/tree/structure', [UserTreeController::class, 'getTreeStructure'])->name('api.users.tree.structure');

// Route for user to see their own tree members (when they are tree owner)
Route::get('/my-tree-members', [TreeController::class, 'getMyTreeMembers'])->name('my.tree.members');

// MLM Tree Management Routes
Route::get('/mlm-trees', [TreeController::class, 'getAllMLMTreeOwners'])->name('mlm.trees');
Route::get('/mlm-tree/{userId}', [TreeController::class, 'getMLMTreeInfo'])->name('mlm.tree.info');
Route::get('/mlm-tree-stats', [TreeController::class, 'getTreeStats'])->name('mlm.tree.stats');


Route::get('/buy-slot-form', function () {
    $plans = \App\Models\LevelPlan::active()->ordered()->limit(3)->get(); // fetch all active level plans
    return view('buy_slot', compact('plans'));
})->name('buy-slot-form');

Route::post('/buy-slot', [BuySlotTreeController::class, 'buySlot'])->name('buy-slot');

// Income Distribution Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/income/dashboard', function () {
        return view('income_dashboard');
    })->name('income.dashboard');
    Route::get('/income/distribution', [IncomeController::class, 'getUserIncomeDistribution'])->name('income.distribution');
    Route::get('/income/received', [IncomeController::class, 'getUserIncomeReceived'])->name('income.received');
    Route::post('/income/process-purchase', [IncomeController::class, 'processLevelPlanPurchase'])->name('income.process.purchase');
});

// Admin Income Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/income/summary', [IncomeController::class, 'getAdminIncomeSummary'])->name('admin.income.summary');
    Route::get('/admin/income/stats', [IncomeController::class, 'getIncomeStats'])->name('admin.income.stats');
});

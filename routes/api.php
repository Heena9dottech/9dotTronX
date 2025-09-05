<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ApiTreeLogicController;
use App\Http\Controllers\ApiUserController;
use App\Http\Controllers\ApiMLMUserController;
use App\Http\Controllers\ApiBuySlotController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Routes for MLM Tree
Route::post('/create-user-with-level', [ApiController::class, 'createUserWithLevel']);

// API Routes for Tree Logic
Route::get('/member-count/{username}', [ApiTreeLogicController::class, 'getMemberCountByLevels']);
Route::get('/tree-structure/{username}', [ApiTreeLogicController::class, 'getTreeStructure']);

// API Routes for User Operations
Route::post('/sponsor-member-count', [ApiUserController::class, 'getSponsorMemberCount']);
Route::post('/create-user-under-sponsor', [ApiUserController::class, 'createUserUnderSponsor']);

Route::post('deleteuser', [ApiUserController::class, 'deleteuser']);

// API MLM User Controller Routes - Based on Left/Right Slots
Route::post('/create-new-user', [ApiMLMUserController::class, 'createNewUser']);

// API Buy Slot Controller Routes
Route::post('useradd', [ApiBuySlotController::class, 'useradd']);
Route::post('buyslot', [ApiBuySlotController::class, 'buyslot']);

// https://api.postman.com/collections/28320364-9bc33565-096e-4201-8d2b-4e4e7747be3d?access_key=PMAT-01K4ADQDSHD4H78FPCB0HYJ0BY
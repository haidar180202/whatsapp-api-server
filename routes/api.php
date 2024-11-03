<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatroomController;
use App\Http\Controllers\AuthController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chatrooms', [ChatroomController::class, 'createChatroom']);
    Route::get('/chatrooms', [ChatroomController::class, 'listChatrooms']);
    Route::post('/chatrooms/{chatroomId}/enter', [ChatroomController::class, 'enterChatroom']);
    Route::post('/chatrooms/{chatroomId}/leave', [ChatroomController::class, 'leaveChatroom']);
    Route::post('/chatrooms/{chatroomId}/messages', [ChatroomController::class, 'sendMessage']); // send message
    Route::get('/chatrooms/{chatroomId}/messages', [ChatroomController::class, 'listMessages']); // get all messages
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

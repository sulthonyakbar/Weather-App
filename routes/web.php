<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [ChatController::class, 'chat'])->name('chat');
Route::post('/', [ChatController::class, 'handleChat'])->name('handleChat');
Route::delete('/clear-chat', [ChatController::class, 'clearChat'])->name('clearChat');

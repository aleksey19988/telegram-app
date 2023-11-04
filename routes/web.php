<?php

use App\Http\Controllers\Controller;
use App\Telegraph\Handler;
use Illuminate\Support\Facades\Route;

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

Route::post('handle-commit', [Handler::class, 'handleCommit'])->name('handle-commit');
Route::post('stats', [Handler::class, 'stats'])->name('stats');
Route::get('reset', [Controller::class, 'reset'])->name('reset');

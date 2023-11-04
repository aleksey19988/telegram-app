<?php

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

Route::post('send-message', [Handler::class, 'sendMessage'])->name('send-message');
Route::post('action', [Handler::class, 'action'])->name('action');
Route::get('test-button', [Handler::class, 'testButton'])->name('test-button');

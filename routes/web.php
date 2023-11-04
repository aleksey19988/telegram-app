<?php

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

Route::post('send-message', [\App\Telegraph\Handler::class, 'sendMessage']);
Route::post('delete', [\App\Telegraph\Handler::class, 'delete']);
Route::get('test-button', [\App\Telegraph\Handler::class, 'testButton']);

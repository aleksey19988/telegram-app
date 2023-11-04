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

Route::controller(Handler::class)->group(function() {
    Route::post('handle-commit', 'handleCommit')->name('handle-commit');
    Route::get('stats-by-commits', 'statsByCommits')->name('stats-by-commits');
    Route::post('stats-by-period', 'statsByPeriod')->name('stats-by-period');
    Route::get('reset', 'reset')->name('reset');
});



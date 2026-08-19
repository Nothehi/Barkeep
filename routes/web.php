<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

/*
 * The app's own home, and so the screen every sign in lands on. It is gated on
 * a chosen workspace: almost everything past here is about one, so an account
 * that has not said which is asked before it gets any further.
 */
Route::middleware(['auth', 'verified', 'workspace.selected'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/workspaces.php';
require __DIR__.'/games.php';
require __DIR__.'/mechanics.php';
require __DIR__.'/playtests.php';
require __DIR__.'/prototypes.php';
require __DIR__.'/balance.php';
require __DIR__.'/game-framework.php';
require __DIR__.'/frameworks.php';

<?php

use App\Http\Controllers\AiRecallController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\EvalGroundingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [TaskController::class, 'index'])->name('dashboard');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/ask', [AiRecallController::class, 'ask'])->name('tasks.ask');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});

// Non-production grounding eval endpoint for promptfoo. Registered ONLY under
// local|testing so it can never run real model calls or accept seed data in
// production; the controller additionally enforces an X-Eval-Token header.
if (app()->environment(['local', 'testing'])) {
    Route::post('/eval/grounding', [EvalGroundingController::class, 'handle'])->name('eval.grounding');
}

require __DIR__.'/auth.php';

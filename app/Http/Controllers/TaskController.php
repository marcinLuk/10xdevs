<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    public function index(): View
    {
        $tasks = auth()->user()->tasks()->latest('task_date')->paginate(15);

        return view('dashboard', compact('tasks'));
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        auth()->user()->tasks()->create($request->validated());

        return redirect()->route('dashboard')->with('success', 'Task added successfully.');
    }
}

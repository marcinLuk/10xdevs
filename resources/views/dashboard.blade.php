<x-app-layout>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            @include('tasks.partials.ai-search')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Tasks') }}</h3>
                        <x-primary-button
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'add-task')"
                        >
                            {{ __('Add Task') }}
                        </x-primary-button>
                    </div>

                    @if ($tasks->isEmpty())
                        <p class="text-gray-500 text-center py-8">
                            {{ __('No tasks yet. Click "Add Task" to log your first garden activity!') }}
                        </p>
                    @else
                        <div class="space-y-3">
                            @foreach ($tasks as $task)
                                <div class="flex items-start gap-4 p-4 rounded-lg border border-gray-200">
                                    <div class="shrink-0 text-sm text-gray-500 w-24">
                                        {{ $task->task_date->format('M j, Y') }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-gray-900">{{ $task->description }}</p>
                                    </div>
                                    @if ($task->type)
                                        <span class="shrink-0 inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            {{ $task->type }}
                                        </span>
                                    @endif
                                    <div class="shrink-0 flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center p-1.5 rounded-md text-gray-400 hover:text-indigo-600 hover:bg-gray-100 transition"
                                            title="{{ __('Edit task') }}"
                                            x-data=""
                                            x-on:click.prevent="
                                                $dispatch('edit-task', {
                                                    id: {{ $task->id }},
                                                    description: {{ Js::from($task->description) }},
                                                    task_date: '{{ $task->task_date->format('Y-m-d') }}',
                                                    type: {{ Js::from($task->type) }}
                                                });
                                                $dispatch('open-modal', 'edit-task');
                                            "
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-gray-100 transition"
                                            title="{{ __('Delete task') }}"
                                            x-data=""
                                            x-on:click.prevent="
                                                $dispatch('delete-task', { id: {{ $task->id }} });
                                                $dispatch('open-modal', 'confirm-delete-task');
                                            "
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $tasks->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    @include('tasks.partials.add-task-form')
    @include('tasks.partials.edit-task-form')
    @include('tasks.partials.delete-task-form')
</x-app-layout>

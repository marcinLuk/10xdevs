<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Garden Log') }}
        </h2>
    </x-slot>

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
</x-app-layout>

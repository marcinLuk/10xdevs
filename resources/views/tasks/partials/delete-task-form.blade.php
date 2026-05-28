<x-modal name="confirm-delete-task" focusable>
    <form method="post" x-data="{ taskId: null }"
        x-on:delete-task.window="taskId = $event.detail.id"
        :action="'/tasks/' + taskId"
        class="p-6">
        @csrf
        @method('DELETE')

        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Task') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Are you sure you want to delete this task? This action cannot be undone.') }}
        </p>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3">
                {{ __('Delete Task') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>

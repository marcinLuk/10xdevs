<x-modal name="add-task" :show="$errors->any()" focusable>
    <form method="post" action="{{ route('tasks.store') }}" class="p-6" x-data="{ typeChoice: '{{ old('type_choice', '') }}' }">
        @csrf

        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Add New Task') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Log a garden task you\'ve completed.') }}
        </p>

        <div class="mt-6">
            <x-input-label for="description" :value="__('Description')" />
            <textarea
                id="description"
                name="description"
                rows="3"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="task_date" :value="__('Date')" />
            <x-text-input
                id="task_date"
                name="task_date"
                type="date"
                class="mt-1 block w-full"
                :value="old('task_date', now()->format('Y-m-d'))"
                required
            />
            <x-input-error :messages="$errors->get('task_date')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="type_choice" :value="__('Type (optional)')" />
            <select
                id="type_choice"
                name="type_choice"
                x-model="typeChoice"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">No type</option>
                <option value="watering">Watering</option>
                <option value="fertilizing">Fertilizing</option>
                <option value="planting">Planting</option>
                <option value="__custom__">Other (custom)</option>
            </select>
        </div>

        <div class="mt-4" x-show="typeChoice === '__custom__'" x-cloak>
            <x-input-label for="custom_type" :value="__('Custom type')" />
            <x-text-input
                id="custom_type"
                name="custom_type"
                type="text"
                class="mt-1 block w-full"
                :value="old('custom_type')"
                maxlength="100"
            />
        </div>

        <x-input-error :messages="$errors->get('type')" class="mt-2" />

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ms-3">
                {{ __('Add Task') }}
            </x-primary-button>
        </div>
    </form>
</x-modal>

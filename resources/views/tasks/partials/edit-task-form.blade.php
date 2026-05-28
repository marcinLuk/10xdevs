<x-modal name="edit-task" focusable>
    <form method="post" x-data="{
        taskId: null,
        description: '',
        task_date: '',
        typeChoice: '',
        customType: '',
        init() {
            this.$watch('typeChoice', (val) => {
                if (val !== '__custom__') this.customType = '';
            });
        },
        setTask(data) {
            this.taskId = data.id;
            this.description = data.description;
            this.task_date = data.task_date;
            const presets = ['watering', 'fertilizing', 'planting'];
            if (!data.type) {
                this.typeChoice = '';
                this.customType = '';
            } else if (presets.includes(data.type)) {
                this.typeChoice = data.type;
                this.customType = '';
            } else {
                this.typeChoice = '__custom__';
                this.customType = data.type;
            }
        }
    }"
    x-on:open-modal.window="if ($event.detail === 'edit-task') { /* handled by button dispatch */ }"
    x-on:edit-task.window="setTask($event.detail)"
    :action="'/tasks/' + taskId"
    class="p-6">
        @csrf
        @method('PUT')

        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Edit Task') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Update your garden task details.') }}
        </p>

        <div class="mt-6">
            <x-input-label for="edit_description" :value="__('Description')" />
            <textarea
                id="edit_description"
                name="description"
                rows="3"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
                x-model="description"
            ></textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="edit_task_date" :value="__('Date')" />
            <x-text-input
                id="edit_task_date"
                name="task_date"
                type="date"
                class="mt-1 block w-full"
                required
                x-model="task_date"
            />
            <x-input-error :messages="$errors->get('task_date')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="edit_type_choice" :value="__('Type (optional)')" />
            <select
                id="edit_type_choice"
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
            <x-input-label for="edit_custom_type" :value="__('Custom type')" />
            <x-text-input
                id="edit_custom_type"
                name="custom_type"
                type="text"
                class="mt-1 block w-full"
                x-model="customType"
                maxlength="100"
            />
        </div>

        <x-input-error :messages="$errors->get('type')" class="mt-2" />

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ms-3">
                {{ __('Save Changes') }}
            </x-primary-button>
        </div>
    </form>
</x-modal>

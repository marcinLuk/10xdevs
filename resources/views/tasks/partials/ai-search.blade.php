<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6"
        x-data="{
            query: '',
            answer: null,
            error: null,
            loading: false,
            lastQuery: '',
            async submit() {
                const q = this.query.trim();
                if (q.length < 5 || this.loading) return;
                this.loading = true;
                this.answer = null;
                this.error = null;
                this.lastQuery = q;
                try {
                    const res = await fetch('{{ route('tasks.ask') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ question: q }),
                    });
                    if (!res.ok) {
                        this.error = 'Request failed (status ' + res.status + ').';
                        return;
                    }
                    const data = await res.json();
                    if (data.ok) {
                        this.answer = data.answer;
                    } else {
                        this.error = data.error || 'Something went wrong.';
                    }
                } catch (e) {
                    this.error = 'Network error. Please try again.';
                } finally {
                    this.loading = false;
                }
            },
            retry() {
                this.query = this.lastQuery;
                this.submit();
            },
        }"
    >
        <h3 class="text-lg font-medium text-gray-900 mb-3">{{ __('Ask your garden log') }}</h3>

        <form @submit.prevent="submit()" class="flex flex-col sm:flex-row gap-2">
            <input
                type="text"
                x-model="query"
                required
                minlength="5"
                maxlength="500"
                :disabled="loading"
                placeholder="{{ __('e.g. when did I last fertilize the tomatoes?') }}"
                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 disabled:opacity-50"
            >
            <x-primary-button type="submit" ::disabled="loading || query.trim().length < 5">
                <span x-show="!loading">{{ __('Ask') }}</span>
                <span x-show="loading" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    {{ __('Thinking...') }}
                </span>
            </x-primary-button>
        </form>

        <div x-show="answer" x-cloak class="mt-4 rounded-md bg-green-50 p-4">
            <p class="text-sm text-green-900 whitespace-pre-wrap" x-text="answer"></p>
        </div>

        <div x-show="error" x-cloak class="mt-4 rounded-md bg-red-50 p-4 flex items-start justify-between gap-3">
            <p class="text-sm text-red-800" x-text="error"></p>
            <button
                type="button"
                @click="retry()"
                :disabled="loading"
                class="shrink-0 text-sm font-medium text-red-700 underline hover:text-red-900 disabled:opacity-50"
            >
                {{ __('Retry') }}
            </button>
        </div>
    </div>
</div>

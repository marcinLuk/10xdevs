<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Facades\Prism;

class AiRecallService
{
    public function ask(User $user, string $question): AiRecallResult
    {
        $limit = (int) config('prism.ai_recall.task_limit', 50);
        $model = (string) config('prism.ai_recall.model', 'anthropic/claude-sonnet-4.5');

        $tasks = $user->tasks()
            ->orderBy('task_date', 'desc')
            ->limit($limit)
            ->get();

        $systemPrompt = view('prompts.garden-recall', ['tasks' => $tasks])->render();

        try {
            $response = Prism::text()
                ->using(Provider::OpenRouter, $model)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($question)
                ->withClientOptions(['timeout' => 10])
                ->withClientRetry(2, 100)
                ->asText();
        } catch (PrismException $e) {
            Log::warning('AiRecallService Prism failure', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return AiRecallResult::error('The AI service is temporarily unavailable. Please try again.');
        }

        return AiRecallResult::success($response->text);
    }
}

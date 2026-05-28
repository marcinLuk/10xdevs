<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiRecallRequest;
use App\Services\AiRecallService;
use Illuminate\Http\JsonResponse;

class AiRecallController extends Controller
{
    public function ask(AiRecallRequest $request, AiRecallService $service): JsonResponse
    {
        $result = $service->ask($request->user(), $request->validated()['question']);

        return response()->json([
            'ok' => $result->ok,
            'answer' => $result->answer,
            'error' => $result->error,
        ]);
    }
}

# Prism PHP — dokumentacja (Context7)

> Źródło: Context7 `/prism-php/prism` (830 snippetów, benchmark score: 83.75)
> Data pobrania: 2026-05-27
> Wersja: v0.100.1 | PHP 8.2+ | Laravel 11-13

---

## 1. Instalacja

```bash
composer require prism-php/prism
php artisan vendor:publish --tag=prism-config
```

---

## 2. Konfiguracja OpenRouter

Plik `config/prism.php`:

```php
'providers' => [
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'url' => env('OPENROUTER_URL', 'https://openrouter.ai/api/v1'),
        'site' => [
            'http_referer' => env('OPENROUTER_SITE_HTTP_REFERER'),
            'x_title' => env('OPENROUTER_SITE_X_TITLE'),
        ],
    ],
],
```

Plik `.env`:

```env
OPENROUTER_API_KEY=your_api_key_here
OPENROUTER_URL=https://openrouter.ai/api/v1
OPENROUTER_SITE_HTTP_REFERER=https://your-site.example
OPENROUTER_SITE_X_TITLE="Your Site Name"
```

---

## 3. Generowanie tekstu

### Podstawowe API

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;

$response = Prism::text()
    ->using(Provider::OpenRouter, 'anthropic/claude-sonnet-4.5')
    ->withSystemPrompt('You are an expert mathematician who explains concepts simply.')
    ->withPrompt('Explain the Pythagorean theorem.')
    ->asText();

echo $response->text;
echo "Prompt tokens: {$response->usage->promptTokens}";
echo "Completion tokens: {$response->usage->completionTokens}";
echo $response->finishReason->name;
```

### System prompt jako Blade view

```php
$response = Prism::text()
    ->using(Provider::OpenRouter, 'anthropic/claude-sonnet-4.5')
    ->withSystemPrompt(view('prompts.garden-recall', ['tasks' => $userTasks]))
    ->withPrompt($userQuestion)
    ->asText();
```

### Obsługa odpowiedzi

```php
$response = Prism::text()
    ->using(Provider::Anthropic, 'claude-3-sonnet')
    ->withPrompt('Explain quantum computing.')
    ->asText();

// Tekst
echo $response->text;

// Powód zakończenia
echo $response->finishReason->name;

// Zużycie tokenów
echo "Prompt tokens: {$response->usage->promptTokens}";
echo "Completion tokens: {$response->usage->completionTokens}";

// Surowa odpowiedź API
$rawResponse = $response->raw;

// Kroki (dla multi-step generations)
foreach ($response->steps as $step) {
    echo "Step text: {$step->text}";
    echo "Step tokens: {$step->usage->completionTokens}";
    $stepRawResponse = $step->raw;
}

// Historia wiadomości
foreach ($response->responseMessages as $message) {
    if ($message instanceof AssistantMessage) {
        echo $message->content;
    }
}
```

### Callback po generowaniu

```php
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Text\Response;

$response = Prism::text()
    ->using(Provider::Anthropic, 'claude-3-5-sonnet-20241022')
    ->withPrompt('Explain Laravel middleware')
    ->asText(function (PendingRequest $request, Response $response) {
        ConversationLog::create([
            'content' => $response->text,
            'role' => 'assistant',
            'tool_calls' => $response->toolCalls,
            'usage' => [
                'prompt_tokens' => $response->usage->promptTokens,
                'completion_tokens' => $response->usage->completionTokens,
            ],
        ]);
    });

echo $response->text;
```

---

## 4. Multi-turn conversations

```php
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;

$response = Prism::text()
    ->using(Provider::Anthropic, 'claude-3-sonnet')
    ->withMessages([
        new UserMessage('What is JSON?'),
        new AssistantMessage('JSON is a lightweight data format...'),
        new UserMessage('Can you show me an example?')
    ])
    ->asText();
```

---

## 5. Provider routing i fallback (OpenRouter)

```php
$response = Prism::text()
    ->using(Provider::OpenRouter, 'anthropic/claude-sonnet-4.5')
    ->withPrompt('Draft a concise product changelog entry.')
    ->withProviderOptions([
        'models' => [
            'anthropic/claude-sonnet-4.5',
            'openai/gpt-4o-mini',
        ],
        'top_k' => 40,
    ])
    ->asText();
```

OpenRouter najpierw próbuje model z `using()`, potem po kolei z `models`. Fallback uruchamia się przy: moderation flags, context-length errors, rate limits, provider downtime.

---

## 6. Streaming

```php
use Prism\Prism\Enums\StreamEventType;

$stream = Prism::text()
    ->using(Provider::OpenRouter, 'openai/gpt-4-turbo')
    ->withSystemPrompt($systemPrompt)
    ->withPrompt($userQuestion)
    ->asStream();

foreach ($stream as $event) {
    match ($event->type()) {
        StreamEventType::TextDelta => echo $event->delta,
        StreamEventType::ToolCall => echo "Tool called: {$event->toolName}\n",
        StreamEventType::ToolResult => echo "Tool result: " . json_encode($event->result) . "\n",
        default => null,
    };
}
```

---

## 7. Structured output (JSON schema)

```php
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

$schema = new ObjectSchema(
    name: 'weather_analysis',
    description: 'Analysis of weather conditions',
    properties: [
        new StringSchema('summary', 'Summary of the weather'),
        new StringSchema('recommendation', 'Recommendation based on weather'),
    ],
    requiredFields: ['summary', 'recommendation']
);

$response = Prism::structured()
    ->using('anthropic', 'claude-3-5-sonnet-latest')
    ->withSchema($schema)
    ->withPrompt('What is the weather in San Francisco?')
    ->asStructured();

dump($response->structured);
// ['summary' => 'Currently sunny and 72F...', 'recommendation' => 'No coat needed...']
```

Anthropic tool calling fallback (dla starszych modeli / nie-angielskiego contentu):

```php
$response = Prism::structured()
    ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
    ->withSchema($schema)
    ->withPrompt('...')
    ->withProviderOptions(['use_tool_calling' => true])
    ->asStructured();
```

---

## 8. Tool calling

```php
use Prism\Prism\Facades\Tool;

$weatherTool = Tool::as('get_weather')
    ->for('Get the current weather for a location')
    ->withStringParameter('location', 'The location to get weather for')
    ->using(function (string $location) {
        return "The weather in {$location} is sunny and 72F";
    });

$response = Prism::text()
    ->using(Provider::OpenRouter, 'openai/gpt-4-turbo')
    ->withPrompt('What is the weather like in Paris?')
    ->withTools([$weatherTool])
    ->withMaxSteps(3)
    ->asText();
```

Structured output + tools:

```php
$response = Prism::structured()
    ->using('anthropic', 'claude-3-5-sonnet-latest')
    ->withSchema($schema)
    ->withTools([$weatherTool])
    ->withMaxSteps(3)
    ->withPrompt('What is the weather?')
    ->asStructured();

foreach ($response->toolCalls as $toolCall) {
    echo "Called: {$toolCall->name}\n";
}
```

---

## 9. Error handling

```php
use Prism\Prism\Exceptions\PrismException;

try {
    $response = Prism::text()
        ->using(Provider::OpenRouter, 'anthropic/claude-sonnet-4.5')
        ->withPrompt('Generate text...')
        ->withClientOptions(['timeout' => 30])
        ->withClientRetry(3, 100) // 3 retries, 100ms delay
        ->asText();

    echo $response->text;
} catch (PrismException $e) {
    Log::error('Prism-specific error:', ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    Log::error('Generic error:', ['error' => $e->getMessage()]);
}
```

OpenRouter automatycznie mapuje errory: rate limiting, request too large, provider overload, invalid API key.

---

## 10. Testowanie — `Prism::fake()`

### Prosty fake tekstu

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Usage;

it('can generate text', function () {
    $fakeResponse = TextResponseFake::make()
        ->withText('Hello, I am Claude!')
        ->withUsage(new Usage(10, 20));

    Prism::fake([$fakeResponse]);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Who are you?')
        ->asText();

    expect($response->text)->toBe('Hello, I am Claude!');
});
```

### Fake structured output

```php
use Prism\Prism\Testing\StructuredResponseFake;

it('can generate structured response', function () {
    $fakeResponse = StructuredResponseFake::make()
        ->withStructured(['name' => 'Alice', 'bio' => 'Developer']);

    Prism::fake([$fakeResponse]);

    $response = Prism::structured()
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('Generate a user profile')
        ->withSchema($schema)
        ->asStructured();

    expect($response->structured['name'])->toBe('Alice');
});
```

### Asercje

```php
$fake = Prism::fake([$fakeResponse]);
// ... make requests ...
$fake->assertPrompt('Who are you?');
$fake->assertCallCount(1);
$fake->assertRequest(function ($requests) {
    expect($requests[0]->provider())->toBe('anthropic');
});
```

### Fake z tool calls (ResponseBuilder)

```php
use Prism\Prism\Testing\TextStepFake;
use Prism\Prism\Text\ResponseBuilder;
use Prism\Prism\ValueObjects\ToolCall;
use Prism\Prism\ValueObjects\ToolResult;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\Enums\FinishReason;

Prism::fake([
    (new ResponseBuilder)
        ->addStep(
            TextStepFake::make()
                ->withToolCalls([
                    new ToolCall(
                        id: 'call_123',
                        name: 'weather',
                        arguments: ['city' => 'Paris']
                    ),
                ])
                ->withFinishReason(FinishReason::ToolCalls)
                ->withUsage(new Usage(15, 25))
                ->withMeta(new Meta('fake-1', 'fake-model'))
        )
        ->addStep(
            TextStepFake::make()
                ->withText('The weather in Paris is sunny, 72F.')
                ->withToolResults([
                    new ToolResult(
                        toolCallId: 'call_123',
                        toolName: 'weather',
                        args: ['city' => 'Paris'],
                        result: 'Sunny, 72F'
                    ),
                ])
                ->withFinishReason(FinishReason::Stop)
                ->withUsage(new Usage(20, 30))
                ->withMeta(new Meta('fake-2', 'fake-model')),
        )
        ->toResponse(),
]);
```

---

## 11. Custom providers

```php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use App\Prism\Providers\MyCustomProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['prism-manager']->extend('my-custom-provider', function ($app, $config) {
            return new MyCustomProvider(
                apiKey: $config['api_key'] ?? '',
            );
        });
    }
}
```

```php
// config/prism.php
'providers' => [
    'my-custom-provider' => [
        'api_key' => env('MY_CUSTOM_PROVIDER_API_KEY'),
    ],
],
```

---

## 12. Prism Server (OpenAI-compatible endpoint)

Prism może wystawić endpoint kompatybilny z OpenAI API:

```
POST /prism/openai/v1/chat/completions
```

Request:

```json
{
  "model": "my-custom-model",
  "messages": [
    {"role": "user", "content": "Hello, who are you?"}
  ]
}
```

Response:

```json
{
  "id": "chatcmpl-12345",
  "object": "chat.completion",
  "created": 1700000000,
  "model": "my-custom-model",
  "choices": [
    {
      "index": 0,
      "message": {
        "role": "assistant",
        "content": "I am a helpful assistant."
      },
      "finish_reason": "stop"
    }
  ]
}
```

---

## 13. Obsługiwane funkcje OpenRouter

| Feature | Status |
|---|---|
| Text Generation | ✅ |
| Structured Output | ✅ |
| Tool Calling | ✅ |
| Multiple Model Support | ✅ |
| Provider Routing (fallback) | ✅ |
| Streaming | ✅ |
| Reasoning/Thinking Tokens | ✅ (kompatybilne modele) |
| Image Support | ✅ |
| Video Support | ✅ |
| Document Support | ✅ |
| Embeddings | ❌ (jeszcze nie) |
| Image Generation | ❌ (jeszcze nie) |

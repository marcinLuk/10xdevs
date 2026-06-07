# Research: ai-recall-loop — dostępne biblioteki PHP/Laravel

> Data: 2026-05-27
> Źródło: Exa web search (3 zapytania, ~30 wyników)
> Cel: znaleźć biblioteki kompatybilne z naszym stackiem (Laravel 13, PHP 8.2+, OpenRouter, SQLite dev / MySQL prod) do
> implementacji FR-009/FR-010 — natural-language AI recall z context injection

---

## Kontekst problemu

S-02 (ai-recall-loop) wymaga:

1. Pobrania tasków użytkownika z bazy (Eloquent)
2. Wstrzyknięcia ich jako kontekst do prompta systemowego
3. Wysłania zapytania do Claude via OpenRouter
4. Zwrócenia odpowiedzi opartej wyłącznie na danych użytkownika (grounding)

To **context injection**, nie pełny RAG — nie potrzebujemy embeddings ani vector store, bo ilość tasków per user jest
ograniczona.

---

## Kategoria 1: Dedykowane pakiety OpenRouter dla Laravel

Bezpośredni dostęp do OpenRouter API — nasz `ai_provider` z tech-stack.md.

### moe-mizrak/laravel-openrouter

- **Composer:** `composer require moe-mizrak/laravel-openrouter`
- **PHP:** 8.1+ | **Laravel:** 10+
- **GitHub:** [moe-mizrak/laravel-openrouter](https://github.com/moe-mizrak/laravel-openrouter)
- **Cechy:** Facade + DI, ChatData DTO, streaming, tool calling, conversation continuity, structured JSON output
- **Dokumentacja:** [GitBook](https://moe-mizrak.gitbook.io/laravel-openrouter)
- **Ocena:** Najpopularniejszy dedykowany wrapper. Solidna dokumentacja, aktywne utrzymanie. Wystarczający dla prostego
  context injection.

### taecontrol/openrouter-laravel-sdk

- **Composer:** `composer require taecontrol/openrouter-laravel-sdk`
- **PHP:** 8.2+ | **Laravel:** 10-11
- **GitHub:** [taecontrol/openrouter-laravel-sdk](https://github.com/taecontrol/openrouter-laravel-sdk)
- **Cechy:** Oparty na Saloon HTTP client. Typed DTOs (CompletionsData, ChatCompletionsData), reasoning tokens,
  embeddings, streaming.
- **Ocena:** Lekki, dobrze typowany. Mniejsza społeczność (2 stars). Niejasna kompatybilność z Laravel 12/13.

### llm-speak/open-router

- **Composer:** `composer require llm-speak/open-router`
- **PHP:** 8.2+ | **Laravel:** 10-12
- **GitHub:
  ** [projectsaturnstudios/llm-speak-open-router](https://github.com/projectsaturnstudios/llm-speak-open-router)
- **Cechy:** Fluent interface, Spatie Laravel Data, reasoning mode, log probabilities, streaming, batch config.
- **Ocena:** Najnowszy, dobrze zaprojektowany API. Część ekosystemu LLMSpeak. Wczesna faza.

### eatzy-software/eatzy-openrouter-sdk

- **Composer:** `composer require eatzy-software/eatzy-openrouter-sdk`
- **PHP:** 8.1+ | **Laravel:** 9+
- **GitHub:** [eatzy-software/eatzy-openrouter-sdk](https://github.com/eatzy-software/eatzy-openrouter-sdk)
- **Cechy:** Framework-agnostic core + Laravel integration, retry logic, timeout handling, middleware, Guzzle.
- **Ocena:** Production-ready wg autora. Dobra separacja framework/core.

### Atarim-Team/openrouter-php-laravel

- **Composer:** `composer require atarim/openrouter-php-laravel`
- **GitHub:** [Atarim-Team/openrouter-php-laravel](https://github.com/Atarim-Team/openrouter-php-laravel)
- **Cechy:** Fork openai-php/client przerobiony na OpenRouter. `fake()` do testów, Responses API.
- **Ocena:** Ciekawy podejście (fork sprawdzonego klienta), ale dokumentacja odsyła do openai-php/client.

---

## Kategoria 2: Abstrakcje multi-provider (unified LLM API)

Jeden interfejs, wiele providerów — pozwalają łatwo zmienić model/provider.

### prism-php/prism ⭐

- **Composer:** `composer require prism-php/prism`
- **PHP:** 8.2+ | **Laravel:** 11-13 ✅
- **GitHub:** [prism-php/prism](https://github.com/prism-php/prism) — **2334 stars, 130 kontrybutorów**
- **Strona:** [prismphp.com](https://prismphp.com)
- **Cechy:**
    - **Natywny provider OpenRouter** (od PR #470, lipiec 2025)
    - Fluent API: `Prism::text()->using(Provider::OpenRouter, 'anthropic/claude-sonnet-4.5')->withPrompt(...)`
    - Structured output, tool calling, streaming, reasoning tokens
    - Provider routing i fallback models via `withProviderOptions()`
    - Image/video/document support
- **Ocena:** **Najdojrzalszy community package.** Aktywny development, duża społeczność. Fundament dla moneo/laravel-rag
  i akoslabs/conductor. Doskonały wybór.

### laravel/ai (Laravel AI SDK) ⭐

- **Composer:** `composer require laravel/ai`
- **PHP:** 8.3+ | **Laravel:** 12+ ✅ (nasz Laravel 13 się kwalifikuje)
- **GitHub:** [laravel/ai](https://github.com/laravel/ai)
- **Docs:** [Laravel AI SDK docs](https://github.com/laravel/docs/blob/main/ai-sdk.md)
- **Cechy:**
    - **Oficjalny first-party pakiet Laravel** (Taylor Otwell's team)
    - Agent classes z atrybutami (`#[Provider]`, `#[Model]`, `#[Temperature]`)
    - Built-in conversation storage (`agent_conversations` table)
    - Tools: WebSearch, WebFetch, FileSearch (RAG)
    - OpenRouter jako custom base URL provider
    - Embeddings, vector stores, structured output, fallbacks
    - Providers: OpenAI, Anthropic, Gemini, Groq, xAI, DeepSeek, Mistral, Ollama
- **Ocena:** **Oficjalny, najbezpieczniejszy long-term bet.** Młodszy niż Prism, ale first-party support = szybki
  development. Wymaga PHP 8.3+.

### mozex/anthropic-laravel

- **Composer:** `composer require mozex/anthropic-laravel`
- **PHP:** 8.2+ | **Laravel:** 11+
- **GitHub:** [mozex/anthropic-laravel](https://github.com/mozex/anthropic-laravel) — 70 stars
- **Cechy:** Pełne pokrycie Anthropic API (batches, extended thinking, token counting, models). Facade + `fake()` do
  testów.
- **Ocena:** **Tylko Anthropic bezpośrednio** — nie przechodzi przez OpenRouter. Najlepsze pokrycie API Anthropic, ale
  nie spełnia wymagania tech-stack (OpenRouter).

### claude-php/claude-php-sdk-laravel

- **Composer:** `composer require claude-php/claude-php-sdk-laravel`
- **PHP:** 8.2+ | **Laravel:** 11-12
- **GitHub:** [claude-php/claude-php-sdk-laravel](https://github.com/claude-php/claude-php-sdk-laravel)
- **Cechy:** Parytet z Python SDK v0.80.0. Agentic patterns (ReAct, CoT, ToT), MCP, streaming, batch processing. 85+
  examples.
- **Ocena:** **Tylko Anthropic bezpośrednio.** Imponujące pokrycie wzorców agentic, ale nie przechodzi przez OpenRouter.

---

## Kategoria 3: RAG pipelines (Retrieval-Augmented Generation)

Pełne rozwiązania do wyszukiwania semantycznego + generacji. **Overkill dla naszego case'u**, ale warto znać na
przyszłość.

### moneo/laravel-rag

- **Composer:** `composer require moneo/laravel-rag`
- **PHP:** 8.2+ | **Laravel:** 12+
- **GitHub:** [moneo/laravel-rag](https://github.com/moneo/laravel-rag)
- **Cechy:** pgvector + sqlite-vec, chunking (Character/Sentence/Markdown/Semantic), hybrid search (RRF), agentic RAG,
  conversation memory, evals, MCP server, Livewire component, Filament admin.
- **Oparty na:** Prism PHP
- **Ocena:** Najpełniejszy RAG dla Laravel. Gdybyśmy potrzebowali prawdziwego RAG w przyszłości — to jest package.

### LarAIgent/larai-kit

- **Composer:** `composer require laraigent/larai-kit`
- **Laravel:** 12-13 ✅
- **GitHub:** [LarAIgent/larai-kit](https://github.com/LarAIgent/larai-kit)
- **Cechy:** Drop-in RAG na Laravel AI SDK. PDF/DOCX parsing, Pinecone/pgvector, streaming SSE, source citations, tenant
  scoping.
- **Ocena:** Dobry addon do Laravel AI SDK, ale overkill dla context injection.

### akoslabs/conductor

- **Composer:** `composer require akoslabs/conductor`
- **GitHub:** [akoslabs/conductor](https://github.com/akoslabs/conductor)
- **Cechy:** Agents + workflows + RAG. Oparty na Prism PHP. Default: Anthropic/claude-sonnet-4.
- **Ocena:** Elegancki, ale wczesna faza.

---

## Kategoria 4: Inne multi-provider pakiety (mniejsze)

| Pakiet                                | Providers                                      | Uwagi                                                   |
|---------------------------------------|------------------------------------------------|---------------------------------------------------------|
| devcbh/laravel-ai-provider            | OpenAI, Claude, Gemini, Mistral, Ollama        | PII masking, failover, parallel requests. v3.2.2.       |
| ghdj/laravel-ai-integration           | OpenAI, Claude, Gemini                         | Rate limiting, cost tracking, prompt templates. v1.0.0. |
| shawnveltman/laravel-openai           | OpenAI, Claude, Gemini, Mistral                | HTTP facade, cost tracking, extended thinking.          |
| sumeetghimire/laravel-ai-orchestrator | OpenAI, Anthropic, Gemini, Ollama, HuggingFace | Fallback chaining, caching, cost tracking. v1.2.0.      |

---

## Porównanie artykułowe (Exa: DEV Community, 2026-05-20)

Artykuł porównawczy Inspector.dev/Neuron vs Laravel AI SDK vs Prism PHP:

- **Prism PHP** — najdojrzalszy community package, najlepszy dla szybkiego dodania LLM features
- **Laravel AI SDK** — oficjalny, najszerszy zakres (text, images, audio, embeddings, RAG, agents), bezpieczny long-term
- **Neuron AI** (Inspector.dev) — framework-agnostic agentic PHP framework, built-in observability, multi-agent
  orchestration

---

## Rekomendacja

### Dla S-02 (ai-recall-loop) — proponowane 3 ścieżki:

#### Ścieżka A: Prism PHP ⭐ (Rekomendowana)

```
composer require prism-php/prism
```

- 2334 stars, aktywny development, natywny OpenRouter provider
- Laravel 11-13 — w pełni kompatybilny
- Fluent API idealny do context injection
- Fundament dla upgrade do RAG (moneo/laravel-rag) w przyszłości
- **Ryzyko:** community package, nie first-party

#### Ścieżka B: Laravel AI SDK (Official)

```
composer require laravel/ai
```

- First-party Laravel team
- Laravel 12+ — nasz Laravel 13 się kwalifikuje
- Agent class model z conversation storage
- OpenRouter via custom base URL
- **Ryzyko:** młodszy, wymaga PHP 8.3+ (do weryfikacji)

#### Ścieżka C: moe-mizrak/laravel-openrouter (Lightweight)

```
composer require moe-mizrak/laravel-openrouter
```

- Najprostszy — bezpośredni OpenRouter API wrapper
- Zero abstrakcji, ChatData DTO, streaming
- Najlżejszy footprint
- **Ryzyko:** brak abstrakcji provider = lock-in na OpenRouter API shape

### Decyzja

Do podjęcia na etapie `/10x-plan ai-recall-loop`.

Kluczowe pytania:

1. Czy PHP w projekcie jest 8.2 czy 8.3+? (warunkuje dostępność Laravel AI SDK)
2. Czy planujemy w przyszłości pełny RAG (embeddings, vector search)?
3. Jak ważna jest testowalność (fake/mock)?

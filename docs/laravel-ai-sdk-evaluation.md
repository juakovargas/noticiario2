# Laravel AI SDK Evaluation

## Installed package status

- Target package: `laravel/ai`.
- Current environment status (April 28, 2026): installation is **blocked by network/proxy restrictions** (`CONNECT tunnel failed, response 403`) when running Composer commands.
- As a result, this branch implements a **safe integration-ready adapter layer** that does not break current behavior when the SDK package is unavailable.

## Laravel AI SDK capabilities relevant to Noticiario

Laravel AI SDK (Laravel 13 official AI layer) is relevant for:

- Text generation (immediately useful for BulletinPromptRun generation).
- Structured output / schema-oriented responses (future parser hardening potential).
- Agents and tools (future optional editorial assistants).
- Audio generation (out of scope in this branch).
- Image generation (out of scope in this branch).
- Embeddings and vector stores (future search/retrieval opportunity).

## Current Noticiario AI implementation (preserved)

- `AiProvider` remains the source of provider/business configuration.
- `AiRequestLog` remains authoritative for request logs.
- Existing cost estimation and usage limits remain in place.
- `BulletinPromptRun` flow remains unchanged at business level.
- Manual AI response paste remains unchanged.
- Existing parser/`parsed_response` flow remains unchanged.
- Source verification flow remains unchanged.

## Recommended migration strategy

### Strategy used in this branch

- Keep existing Noticiario business layer fully intact.
- Add optional execution driver at provider level:
  - `custom` (default existing behavior)
  - `laravel_ai` (new adapter path)
- Add `LaravelAiSdkClient` that implements the existing `AiClient` contract.
- Update `AiClientManager` to route by `ai_providers.client_driver`.
- Keep existing custom clients and do **not** remove them.

### Fallback / safety behavior

- Unsupported provider types for the SDK adapter return safe domain errors.
- If SDK runtime is unavailable, adapter path throws safe provider exception and does not leak secrets.
- No silent fallback from `laravel_ai` to `custom` is performed; behavior is explicit.

## Structured output evaluation (future)

- SDK structured output appears promising for reducing parser fragility.
- This branch deliberately does **not** migrate parser workflow.
- Recommended next dedicated branch: `feature/structured-ai-response-schema`.

## Agents/tools evaluation (future, not implemented now)

Potential future agents:

- `NewsScriptWriterAgent`
- `SourceVerifierAgent`
- `HashtagGeneratorAgent`
- `SocialCopyGeneratorAgent`
- `AudioScriptOptimizerAgent`
- `PublicationAssistantAgent`

No production agents were introduced in this branch.

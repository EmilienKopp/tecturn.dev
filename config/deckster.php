<?php

use Laravel\Ai\Enums\Lab;

/**
 * Deckster "bring your own AI" configuration.
 *
 * `models` is the curated list offered in the UI. Each entry pins a provider
 * (Lab enum value) and a model id known to support the JSON-schema structured
 * output Deckster relies on. Users may also free-text any provider+model, but
 * those are flagged unsupported: the model may reject structured output and the
 * build will fail. Keep this list to models actually verified to work.
 */
return [
    /*
     * The house provider/model used when a user has not selected their own
     * credential. Mirrors the attributes on the Deckster agent; kept here so the
     * fallback is configurable without touching the agent class.
     */
    'house' => [
        'driver' => env('DECKSTER_HOUSE_DRIVER', Lab::Mistral->value),
        'model' => env('DECKSTER_HOUSE_MODEL', 'mistral-medium-3-5'),
    ],

    /*
     * Rate limit for the free house model, per user. Users on their own AI
     * credential (bring your own AI) are not limited: their key, their cost.
     * `max` builds are allowed per rolling `decay_seconds` window.
     */
    'house_limit' => [
        'max' => (int) env('DECKSTER_HOUSE_LIMIT', 3),
        'decay_seconds' => (int) env('DECKSTER_HOUSE_LIMIT_WINDOW', 86400),
    ],

    /*
     * Curated models, grouped for display. `driver` must be a Lab enum value.
     * `supportsSchema` is always true here; the free-text path is the only way
     * to reach an unverified model. An entry may set `base_url` (needed by the
     * openai-compatible driver); a curated openai-compatible entry with an empty
     * base_url is hidden until configured (see DecksterModels::curated()).
     *
     * @var array<int, array{driver: string, model: string, label: string, supportsSchema: bool, base_url?: string|null}>
     */
    'models' => [
        ['driver' => Lab::Mistral->value, 'model' => 'mistral-medium-3-5', 'label' => 'Mistral Medium 3.5', 'supportsSchema' => true],
        ['driver' => Lab::Mistral->value, 'model' => 'mistral-large-latest', 'label' => 'Mistral Large', 'supportsSchema' => true],
        ['driver' => Lab::Anthropic->value, 'model' => 'claude-sonnet-4-6', 'label' => 'Claude Sonnet 4.6', 'supportsSchema' => true],
        ['driver' => Lab::Anthropic->value, 'model' => 'claude-opus-4-8', 'label' => 'Claude Opus 4.8', 'supportsSchema' => true],
        ['driver' => Lab::OpenAI->value, 'model' => 'gpt-4.1', 'label' => 'GPT-4.1', 'supportsSchema' => true],
        ['driver' => Lab::OpenAI->value, 'model' => 'gpt-4.1-mini', 'label' => 'GPT-4.1 mini', 'supportsSchema' => true],
        ['driver' => Lab::Gemini->value, 'model' => 'gemini-2.5-pro', 'label' => 'Gemini 2.5 Pro', 'supportsSchema' => true],
        ['driver' => Lab::Gemini->value, 'model' => 'gemini-2.5-flash', 'label' => 'Gemini 2.5 Flash', 'supportsSchema' => true],
        ['driver' => Lab::DeepSeek->value, 'model' => 'deepseek-chat', 'label' => 'DeepSeek V3', 'supportsSchema' => true],
        ['driver' => Lab::DeepSeek->value, 'model' => 'deepseek-reasoner', 'label' => 'DeepSeek R1', 'supportsSchema' => true],

        // Sakana AI (Fugu) via its OpenAI-compatible API. Endpoint and model id
        // are env-driven because they are account/region specific; the entry
        // stays hidden until DECKSTER_SAKANA_URL is set.
        [
            'driver' => Lab::OpenAICompatible->value,
            'model' => env('DECKSTER_SAKANA_MODEL', 'fugu'),
            'label' => 'Sakana AI (Fugu)',
            'supportsSchema' => true,
            'base_url' => env('DECKSTER_SAKANA_URL', 'https://api.sakana.ai/v1'),
        ],
    ],

    /*
     * Providers a user may free-text a model against. Restricted to drivers the
     * Laravel AI SDK ships text gateways for. Kept as Lab enum values.
     *
     * @var list<string>
     */
    'freetext_drivers' => [
        Lab::Mistral->value,
        Lab::Anthropic->value,
        Lab::OpenAI->value,
        Lab::Gemini->value,
        Lab::Groq->value,
        Lab::DeepSeek->value,
        Lab::xAI->value,
        Lab::OpenRouter->value,
        Lab::OpenAICompatible->value,
    ],

    /*
     * Drivers that require a `base_url` on the credential. The openai-compatible
     * driver has no fixed endpoint, so it cannot be used without one.
     *
     * @var list<string>
     */
    'requires_base_url' => [
        Lab::OpenAICompatible->value,
    ],
];

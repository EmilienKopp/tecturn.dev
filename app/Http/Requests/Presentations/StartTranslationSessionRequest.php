<?php

namespace App\Http\Requests\Presentations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StartTranslationSessionRequest extends FormRequest
{
    private const string UUID_PATTERN = '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i';

    /**
     * Either an event URL (manual linking, while YoYoTranslate has no public
     * API) or a source language (API mode, once keys are available).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_url' => ['required_without:source_language', 'string', 'regex:'.self::UUID_PATTERN],
            'source_language' => ['required_without:event_url', 'string', 'size:2'],
            // YoYoTranslate dropped the `lang=all` wildcard, so the presenter
            // must name the languages captions are requested for.
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => ['string', 'size:2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_url.regex' => 'Paste a YoYoTranslate event URL (or its event id).',
        ];
    }

    /**
     * The requested caption languages, lower-cased and de-duplicated.
     *
     * @return list<string>
     */
    public function languages(): array
    {
        /** @var array<int, string> $languages */
        $languages = $this->validated('languages', []);

        return array_values(array_unique(array_map(
            static fn (string $language): string => mb_strtolower($language),
            $languages,
        )));
    }

    /** The event id extracted from the pasted event URL, if one was provided. */
    public function eventId(): ?string
    {
        $eventUrl = $this->validated('event_url');

        if (! is_string($eventUrl)) {
            return null;
        }

        preg_match(self::UUID_PATTERN, $eventUrl, $matches);

        return isset($matches[0]) ? strtolower($matches[0]) : null;
    }
}

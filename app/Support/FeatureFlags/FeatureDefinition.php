<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

/**
 * Describes a single admin-manageable feature flag: how it is presented, what
 * values it accepts, and whether it is toggled globally or per team.
 */
class FeatureDefinition
{
    /**
     * @param  'boolean'|'select'  $type
     * @param  'global'|'team'  $scope
     * @param  list<array{value: string, label: string}>  $options  Choices for a "select" flag; empty for booleans.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly string $type,
        public readonly string $scope,
        public readonly array $options = [],
    ) {}

    public function isBoolean(): bool
    {
        return $this->type === 'boolean';
    }

    public function isGlobal(): bool
    {
        return $this->scope === 'global';
    }

    /**
     * Whether the given raw value is acceptable for this flag.
     */
    public function accepts(bool|string $value): bool
    {
        if ($this->isBoolean()) {
            return is_bool($value);
        }

        return in_array($value, array_column($this->options, 'value'), true);
    }

    /**
     * @return array{key: string, label: string, description: string, type: string, scope: string, options: list<array{value: string, label: string}>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'type' => $this->type,
            'scope' => $this->scope,
            'options' => $this->options,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAiCredential;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Ai\Enums\Lab;

/**
 * @extends Factory<UserAiCredential>
 */
class UserAiCredentialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => $this->faker->words(2, true),
            'driver' => Lab::Anthropic->value,
            'model' => 'claude-sonnet-4-6',
            'api_key' => 'sk-'.$this->faker->sha256(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }

    public function forDriver(Lab $driver, string $model): static
    {
        return $this->state(fn (): array => [
            'driver' => $driver->value,
            'model' => $model,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\BetaRequestStatus;
use App\Models\BetaRequestModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BetaRequestModel>
 */
class BetaRequestModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'name' => fake()->name(),
            'email' => $email,
            'email_hash' => hash('sha256', Str::lower(trim($email))),
            'message' => fake()->optional()->sentence(),
            'status' => BetaRequestStatus::Pending,
        ];
    }

    /**
     * Set a specific email, keeping the dedupe hash in sync with it.
     */
    public function forEmail(string $email): static
    {
        return $this->state([
            'email' => $email,
            'email_hash' => hash('sha256', Str::lower(trim($email))),
        ]);
    }

    public function approved(): static
    {
        return $this->state(['status' => BetaRequestStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => BetaRequestStatus::Rejected]);
    }
}

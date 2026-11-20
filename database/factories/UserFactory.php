<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'account_type' => AccountType::Customer,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Str0ng!Pass123'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_type' => AccountType::Staff,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}

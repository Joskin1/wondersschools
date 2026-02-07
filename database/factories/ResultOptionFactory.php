<?php

namespace Database\Factories;

use App\Models\ResultOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResultOption>
 */
class ResultOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'key' => fake()->unique()->slug(),
            'value' => fake()->word(),
            'type' => fake()->randomElement(['string', 'number', 'boolean', 'json']),
            'scope' => fake()->randomElement(['general', 'printing', 'computation', null]),
            'school_id' => null,
        ];
    }

    /**
     * Indicate that the option is a string type
     */
    public function string(string $key, string $value, ?string $scope = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => $value,
            'type' => 'string',
            'scope' => $scope,
            'name' => ucwords(str_replace('_', ' ', $key)),
        ]);
    }

    /**
     * Indicate that the option is a number type
     */
    public function number(string $key, float $value, ?string $scope = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => (string) $value,
            'type' => 'number',
            'scope' => $scope,
            'name' => ucwords(str_replace('_', ' ', $key)),
        ]);
    }

    /**
     * Indicate that the option is a boolean type
     */
    public function boolean(string $key, bool $value, ?string $scope = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => $value ? '1' : '0',
            'type' => 'boolean',
            'scope' => $scope,
            'name' => ucwords(str_replace('_', ' ', $key)),
        ]);
    }

    /**
     * Indicate that the option is a JSON type
     */
    public function json(string $key, array $value, ?string $scope = null): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => json_encode($value),
            'type' => 'json',
            'scope' => $scope,
            'name' => ucwords(str_replace('_', ' ', $key)),
        ]);
    }
}

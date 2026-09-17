<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Config>
 */
class ConfigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'type' => 'string',
            'value' => fake()->word(),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Config bertipe integer.
     */
    public function integer(): static
    {
        return $this->state(fn () => [
            'type' => 'integer',
            'value' => (string) fake()->numberBetween(1, 100),
        ]);
    }

    /**
     * Config bertipe boolean.
     */
    public function boolean(): static
    {
        return $this->state(fn () => [
            'type' => 'boolean',
            'value' => fake()->boolean() ? '1' : '0',
        ]);
    }

    /**
     * Config bertipe json.
     */
    public function json(): static
    {
        return $this->state(fn () => [
            'type' => 'json',
            'value' => json_encode(['enabled' => true]),
        ]);
    }
}

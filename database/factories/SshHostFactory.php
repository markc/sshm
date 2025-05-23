<?php

namespace Database\Factories;

use App\Models\SshHost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\SshHost
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class SshHostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = SshHost::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true) . ' Server',
            'hostname' => $this->faker->domainName(),
            'user' => $this->faker->userName(),
            'port' => $this->faker->randomElement([22, 2222, 2200, 8022]),
            'identity_file' => $this->faker->optional()->regexify('[a-z_]{5,15}'),
            'active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the SSH host is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the SSH host is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\SshKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\SshKey
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class SshKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = SshKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $keyType = $this->faker->randomElement(['rsa', 'ed25519', 'ecdsa']);
        $keyName = $this->faker->unique()->words(2, true) . ' Key';
        $email = $this->faker->email();

        // Generate realistic-looking SSH keys based on type
        $publicKey = match ($keyType) {
            'rsa' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC' . base64_encode($this->faker->regexify('[A-Za-z0-9]{64}')) . ' ' . $email,
            'ed25519' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI' . base64_encode($this->faker->regexify('[A-Za-z0-9]{32}')) . ' ' . $email,
            'ecdsa' => 'ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAI' . base64_encode($this->faker->regexify('[A-Za-z0-9]{48}')) . ' ' . $email,
        };

        $privateKey = match ($keyType) {
            'rsa' => "-----BEGIN OPENSSH PRIVATE KEY-----\n" . base64_encode($this->faker->regexify('[A-Za-z0-9+/]{400}')) . "\n-----END OPENSSH PRIVATE KEY-----",
            'ed25519' => "-----BEGIN OPENSSH PRIVATE KEY-----\n" . base64_encode($this->faker->regexify('[A-Za-z0-9+/]{200}')) . "\n-----END OPENSSH PRIVATE KEY-----",
            'ecdsa' => "-----BEGIN OPENSSH PRIVATE KEY-----\n" . base64_encode($this->faker->regexify('[A-Za-z0-9+/]{300}')) . "\n-----END OPENSSH PRIVATE KEY-----",
        };

        return [
            'name' => $keyName,
            'type' => $keyType,
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'comment' => $email,
            'active' => $this->faker->boolean(85), // 85% chance of being active
        ];
    }

    /**
     * Indicate that the SSH key is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the SSH key is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Generate an RSA key.
     */
    public function rsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'rsa',
        ]);
    }

    /**
     * Generate an Ed25519 key.
     */
    public function ed25519(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ed25519',
        ]);
    }

    /**
     * Generate an ECDSA key.
     */
    public function ecdsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ecdsa',
        ]);
    }
}

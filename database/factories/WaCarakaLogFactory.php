<?php

namespace Database\Factories;

use App\Models\WaCarakaLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaCarakaLogFactory extends Factory
{
    protected $model = WaCarakaLog::class;

    public function definition(): array
    {
        return [
            'sender'   => '62' . fake()->numerify('8#########'),
            'receiver' => '62' . fake()->numerify('8#########'),
            'message'  => fake()->sentence(),
            'type'     => 'text',
            'status'   => fake()->randomElement(['sent', 'sent', 'sent', 'failed']),
            'payload'  => ['ok' => true],
        ];
    }

    public function sent(): static
    {
        return $this->state(['status' => 'sent']);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }
}

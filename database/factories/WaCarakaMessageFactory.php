<?php

namespace Database\Factories;

use App\Models\WaCarakaMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaCarakaMessageFactory extends Factory
{
    protected $model = WaCarakaMessage::class;

    public function definition(): array
    {
        $direction = fake()->randomElement(['inbound', 'outbound']);
        $remote = '62' . fake()->numerify('8#########');

        return [
            'user_id'         => null,
            'direction'       => $direction,
            'remote_number'   => $remote,
            'local_number'    => '628001234567',
            'message_text'    => fake()->sentence(),
            'message_type'    => 'text',
            'wa_message_id'   => fake()->uuid(),
            'status'          => $direction === 'inbound' ? 'received' : 'sent',
            'conversation_id' => WaCarakaMessage::conversationIdFor($remote),
            'metadata'        => ['source' => 'factory'],
            'replied_at'      => null,
        ];
    }

    public function inbound(): static
    {
        return $this->state([
            'direction' => 'inbound',
            'status'    => 'received',
        ]);
    }

    public function outbound(): static
    {
        return $this->state([
            'direction' => 'outbound',
            'status'    => 'sent',
        ]);
    }

    public function unreplied(): static
    {
        return $this->state([
            'direction'  => 'inbound',
            'status'     => 'received',
            'replied_at' => null,
        ]);
    }
}

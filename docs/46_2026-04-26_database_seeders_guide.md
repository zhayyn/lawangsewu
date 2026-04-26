# Database Seeders Guide

## Overview

Comprehensive database seeding system for development, testing, and demonstration.

## Base Seeder

Create base seeder with shared logic:

```php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run() {
        // Seed in order of dependencies
        $this->call([
            UserSeeder::class,
            ConversationSeeder::class,
            ChatMessageSeeder::class,
        ]);
    }
}
```

## User Seeder

```php
// database/seeders/UserSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder {
    public function run() {
        // Create admin
        User::create([
            'email' => 'admin@example.com',
            'name' => 'Administrator',
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create operators
        User::factory()
            ->count(5)
            ->state([
                'role' => 'operator',
                'status' => 'active',
            ])
            ->create();

        // Create regular users
        User::factory()
            ->count(20)
            ->state([
                'role' => 'viewer',
                'status' => 'active',
            ])
            ->create();

        // Create test user for development
        if (app()->environment('local')) {
            User::create([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'role' => 'viewer',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }
    }
}
```

## Conversation Seeder

```php
// database/seeders/ConversationSeeder.php
namespace Database\Seeders;

use App\Models\Conversation;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder {
    public function run() {
        // Create conversations with various statuses
        Conversation::factory()
            ->count(10)
            ->state(['status' => 'open'])
            ->create();

        Conversation::factory()
            ->count(5)
            ->state(['status' => 'closed'])
            ->create();

        Conversation::factory()
            ->count(3)
            ->state(['status' => 'archived'])
            ->create();
    }
}
```

## Chat Message Seeder

```php
// database/seeders/ChatMessageSeeder.php
namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatMessageSeeder extends Seeder {
    public function run() {
        $conversations = Conversation::all();
        $users = User::all();

        foreach ($conversations as $conversation) {
            // Create 10-50 messages per conversation
            ChatMessage::factory()
                ->count(rand(10, 50))
                ->state([
                    'conversation_id' => $conversation->id,
                    'user_id' => $users->random()->id,
                ])
                ->create();

            // Add some WA messages
            ChatMessage::factory()
                ->count(rand(0, 5))
                ->state([
                    'conversation_id' => $conversation->id,
                    'user_id' => $users->random()->id,
                    'sent_via_wa' => true,
                    'phone' => fake()->phoneNumber(),
                    'wa_status' => 'delivered',
                ])
                ->create();
        }
    }
}
```

## Factory Classes

Create factories for realistic data:

```php
// database/factories/UserFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory {
    public function definition() {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'avatar_url' => fake()->imageUrl(),
            'role' => 'viewer',
            'status' => 'active',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin() {
        return $this->state(['role' => 'admin']);
    }

    public function operator() {
        return $this->state(['role' => 'operator']);
    }

    public function inactive() {
        return $this->state(['status' => 'inactive']);
    }
}
```

```php
// database/factories/ConversationFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory {
    public function definition() {
        return [
            'case_id' => 'CASE-' . fake()->numerify('######'),
            'nomor_perkara' => fake()->numerify('###/PDT/####/PN.SMG'),
            'pihak_penggugat' => fake()->company(),
            'pihak_tergugat' => fake()->company(),
            'hakim_id' => fake()->numerify('####'),
            'status' => 'open',
            'started_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function closed() {
        return $this->state([
            'status' => 'closed',
            'closed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function archived() {
        return $this->state([
            'status' => 'archived',
            'closed_at' => fake()->dateTimeBetween('-1 year', '-6 months'),
        ]);
    }
}
```

```php
// database/factories/ChatMessageFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory {
    public function definition() {
        return [
            'message' => fake()->paragraph(),
            'type' => 'message',
            'sent_via_wa' => false,
            'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function note() {
        return $this->state(['type' => 'note']);
    }

    public function system() {
        return $this->state(['type' => 'system']);
    }

    public function waMessage() {
        return $this->state([
            'sent_via_wa' => true,
            'phone' => fake()->numerify('62812345####'),
            'wa_status' => 'delivered',
            'wa_message_id' => fake()->uuid(),
        ]);
    }
}
```

## Running Seeders

### Command Line
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder

# Fresh install with seeding
php artisan migrate:fresh --seed

# Fresh with specific seeders
php artisan migrate:fresh --seeder=DatabaseSeeder
```

### Programmatically
```php
// In console command or test
use Illuminate\Database\Seeder;

$seeder = new UserSeeder();
$seeder->run();
```

## Development Seeder

Create lightweight seeder for development:

```php
// database/seeders/DevelopmentSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder {
    public function run() {
        if (!app()->environment('local')) {
            return;
        }

        // Create minimal test data
        User::factory(3)->create([
            'role' => 'admin',
        ]);

        $this->call([
            ConversationSeeder::class,
            ChatMessageSeeder::class,
        ]);
    }
}
```

## Testing Seeder

```php
// database/seeders/TestingSeeder.php
namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestingSeeder extends Seeder {
    public function run() {
        // Create exactly what tests expect
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $operator = User::factory()->create([
            'role' => 'operator',
            'email' => 'operator@test.com',
        ]);

        $conversation = Conversation::factory()->create([
            'case_id' => 'TEST-001',
        ]);

        ChatMessage::factory(5)->create([
            'conversation_id' => $conversation->id,
            'user_id' => $admin->id,
        ]);
    }
}
```

Register in `phpunit.xml`:
```xml
<php>
    <env name="DB_CONNECTION" value="testing"/>
    <env name="DB_DATABASE" value="lawangsewu_testing"/>
    <env name="SEED_DATABASE" value="TestingSeeder"/>
</php>
```

## Conditional Seeding

```php
// database/seeders/DatabaseSeeder.php
public function run() {
    if (app()->environment('testing')) {
        $this->call(TestingSeeder::class);
    } elseif (app()->environment('local')) {
        $this->call(DevelopmentSeeder::class);
    } else {
        $this->call(ProductionSeeder::class);
    }
}
```

## State Management

Use factory states for different scenarios:

```php
// In tests
test('can update user role', function () {
    $user = User::factory()->operator()->create();
    
    $this->actingAs($user)
        ->patch("/users/{$user->id}/role", ['role' => 'admin'])
        ->assertOk();
});

// Multiple states
Conversation::factory()
    ->closed()
    ->count(5)
    ->create();
```

## Mass Seeding

For performance testing:

```php
// database/seeders/MassDataSeeder.php
namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ChatMessage;
use Illuminate\Database\Seeder;

class MassDataSeeder extends Seeder {
    public function run() {
        // Create large datasets
        Conversation::factory(1000)->create();

        Conversation::all()->each(function ($conversation) {
            ChatMessage::factory(100)->create([
                'conversation_id' => $conversation->id,
            ]);
        });
    }
}
```

Run with progress:
```bash
php artisan db:seed --class=MassDataSeeder --verbose
```

## Clear Seeders

Create command to clear specific data:

```php
// app/Console/Commands/ClearSeeds.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearSeeds extends Command {
    protected $signature = 'db:clear-seeds {--all}';

    public function handle() {
        if ($this->option('all')) {
            DB::table('chat_messages')->truncate();
            DB::table('conversations')->truncate();
            DB::table('users')->truncate();
        } else {
            // Clear test data only
            ChatMessage::where('created_at', '>', now()->subHour())
                ->delete();
        }

        $this->info('Seeds cleared successfully');
    }
}
```

Usage:
```bash
php artisan db:clear-seeds
php artisan db:clear-seeds --all
```

## Best Practices

- [ ] Create factories for each model
- [ ] Use realistic fake data
- [ ] Seed in dependency order
- [ ] Create environment-specific seeders
- [ ] Use factory states for variations
- [ ] Create test data fixtures
- [ ] Document expected data
- [ ] Keep seeders fast for development
- [ ] Use transactions for atomic seeding
- [ ] Seed only in development/testing
- [ ] Version control seed definitions
- [ ] Create fresh data regularly
- [ ] Clear old seed data
- [ ] Test seeding in CI/CD
- [ ] Document seeding process

## Files Reference

- `database/seeders/DatabaseSeeder.php` - Main seeder
- `database/factories/` - Model factories
- `database/seeders/*Seeder.php` - Specific seeders
- `phpunit.xml` - Test environment configuration

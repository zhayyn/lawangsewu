<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Channel authorization callbacks. The 'lawangsewu.chat.global' channel
| is public (no auth required beyond being logged in), so we use a
| standard Channel in the event, not a PrivateChannel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('lawangsewu.wacaraka.inbox', function ($user) {
    if (! $user) {
        return false;
    }

    return $user->isSuperAdmin() || in_array($user->role, ['operator', 'admin'], true);
});

// Queue real-time update channels (PTSP dan Sidang)
Broadcast::channel('lawangsewu.queue.ptsp', function ($user) {
    if (! $user) {
        return false;
    }

    return $user->is_active &&
        (in_array($user->role, ['viewer', 'operator', 'admin'], true) || $user->isSuperAdmin());
});

Broadcast::channel('lawangsewu.queue.sidang', function ($user) {
    if (! $user) {
        return false;
    }

    return $user->is_active &&
        (in_array($user->role, ['viewer', 'operator', 'admin'], true) || $user->isSuperAdmin());
});

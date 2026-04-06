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

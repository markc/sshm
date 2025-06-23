<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
| SSH Process Channel Authorization
| We authorize that the authenticated user can only listen to a channel
| if they are the one who initiated the process. We'll store that
| relationship in the cache temporarily for validation.
*/
Broadcast::channel('ssh-process.{processId}', function ($user, $processId) {
    // A simple check: does a cache key exist linking this user to this process?
    // This prevents one user from snooping on another's terminal.
    $expectedUserId = Cache::get("process:{$processId}:user");

    return (int) $user->id === (int) $expectedUserId;
});

<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/


Broadcast::channel('privateName', function ($user) {
    return !is_null($user);
});
Broadcast::channel('track-user', function ($user) {
    return $user;
});
Broadcast::channel('track-channel', function ($user) {
    return $user;
});

Broadcast::channel('user-status', function ($user) {
    return $user;
});
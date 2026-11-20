<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat-conversation.{conversationId}', function ($user) {
    return (bool) $user;
});

Broadcast::channel('chat-inbox', function ($user) {
    return (bool) $user;
});

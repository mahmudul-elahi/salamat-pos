<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat-conversation.{conversationId}', fn ($user) => (bool) $user);

Broadcast::channel('chat-inbox', fn ($user) => (bool) $user);

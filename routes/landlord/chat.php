<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\Chat\ChatConversationController;
use App\Http\Controllers\Pos\Chat\ChatLabelController;
use App\Http\Controllers\Pos\Chat\ChatMessageController;
use App\Http\Controllers\Pos\Chat\TeamController;
use App\Http\Controllers\Pos\WhatsApp\WhatsAppTemplateController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pos', 'middleware' => 'auth.permission'], function (Router $router) {
    $router->apiResource('chat-conversations', ChatConversationController::class)->only(['index', 'show']);

    Route::get('chat-conversations/{chat_conversation}/messages', [ChatConversationController::class, 'messages'])
        ->name('chat-messages.index');
    Route::post('chat-conversations/{chat_conversation}/messages', [ChatMessageController::class, 'store'])
        ->name('chat-messages.store');

    Route::post('chat-conversations/{chat_conversation}/assign', [ChatConversationController::class, 'assign'])
        ->name('chat-conversations.assign');
    Route::post('chat-conversations/{chat_conversation}/claim', [ChatConversationController::class, 'claim'])
        ->name('chat-conversations.claim');
    Route::post('chat-conversations/{chat_conversation}/reassign', [ChatConversationController::class, 'reassign'])
        ->name('chat-conversations.reassign');
    Route::post('chat-conversations/{chat_conversation}/close', [ChatConversationController::class, 'close'])
        ->name('chat-conversations.close');
    Route::post('chat-conversations/{chat_conversation}/reopen', [ChatConversationController::class, 'reopen'])
        ->name('chat-conversations.reopen');
    Route::post('chat-conversations/{chat_conversation}/assign-team', [ChatConversationController::class, 'assignTeam'])
        ->name('chat-conversations.assign-team');
    Route::patch('chat-conversations/{chat_conversation}/priority', [ChatConversationController::class, 'updatePriority'])
        ->name('chat-conversations.priority');
    Route::put('chat-conversations/{chat_conversation}/labels', [ChatConversationController::class, 'syncLabels'])
        ->name('chat-conversations.labels');
    Route::patch('chat-conversations/{chat_conversation}/attach-contact', [ChatConversationController::class, 'attachContact'])
        ->name('chat-conversations.attach-contact');

    Route::get('chat-teams', [TeamController::class, 'index'])->name('chat-teams.index');
    Route::post('chat-teams', [TeamController::class, 'store'])->name('chat-teams.store');
    Route::put('chat-teams/{chat_team}', [TeamController::class, 'update'])->name('chat-teams.update');
    Route::delete('chat-teams/{chat_team}', [TeamController::class, 'destroy'])->name('chat-teams.destroy');

    Route::get('chat-labels', [ChatLabelController::class, 'index'])->name('chat-labels.index');
    Route::post('chat-labels', [ChatLabelController::class, 'store'])->name('chat-labels.store');

    Route::get('whatsapp-templates', [WhatsAppTemplateController::class, 'index'])->name('whatsapp-templates.index');
    Route::post('whatsapp-templates/sync', [WhatsAppTemplateController::class, 'sync'])->name('whatsapp-templates.sync');
});

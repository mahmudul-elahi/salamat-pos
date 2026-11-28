<?php

declare(strict_types=1);

use App\Http\Controllers\Pos\AiAssistant\AiAssistantController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
 * The assistant is open to every signed-in user; each of its tools checks the user's own
 * permission before it reads or changes anything. The "authorize" middleware loads those
 * permissions as gates for the tools to check.
 */
Route::group(['prefix' => 'ai-assistant', 'middleware' => ['authorize']], function (Router $router) {

    $router->post('messages', [AiAssistantController::class, 'message'])
        ->middleware('throttle:20,1')
        ->name('ai_assistant.messages');

    $router->post('conversations/{conversationId}/approvals', [AiAssistantController::class, 'approve'])
        ->middleware('throttle:20,1')
        ->name('ai_assistant.approvals');

    $router->get('conversations/{conversationId}', [AiAssistantController::class, 'show'])
        ->name('ai_assistant.conversations.show');
});

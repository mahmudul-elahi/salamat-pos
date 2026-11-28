<?php

namespace App\Http\Controllers\Pos\AiAssistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\AiAssistant\AiAssistantApprovalRequest;
use App\Http\Requests\Pos\AiAssistant\AiAssistantMessageRequest;
use App\Models\User;
use App\Services\AiAssistant\AiAssistant;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\ResolvesPendingApprovals;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\ApprovalMismatchException;
use Laravel\Ai\Exceptions\ApprovalNotResumableException;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Responses\AgentResponse;

class AiAssistantController extends Controller
{
    public function __construct(
        protected AiAssistant $assistant,
        protected ConversationStore $conversationStore,
    ) {}

    /**
     * Send a message to the assistant, starting a new conversation or continuing one of the user's own.
     */
    public function message(AiAssistantMessageRequest $request): JsonResponse
    {
        $user = $request->user();
        $conversationId = $request->validated('conversation_id');

        if ($conversationId !== null) {
            $this->findOwnConversation($user, $conversationId);

            if ($this->pendingApprovalsFor($conversationId) !== []) {
                return errorResponse('Approve or reject the pending action before sending another message.', null, 409);
            }
        }

        return $this->respond(fn (): AgentResponse => $this->assistant
            ->continueOrStart($conversationId, as: $user)
            ->prompt($request->validated('message')));
    }

    /**
     * Approve or reject the actions the assistant is waiting on, and let it carry on.
     */
    public function approve(AiAssistantApprovalRequest $request, string $conversationId): JsonResponse
    {
        $user = $request->user();
        $this->findOwnConversation($user, $conversationId);

        $decisions = $request->decisionsById();
        $pendingIds = collect($this->pendingApprovalsFor($conversationId))
            ->map(fn (PendingApproval $approval): string => $approval->id)
            ->sort()
            ->values()
            ->all();

        if ($pendingIds === [] || collect($decisions)->keys()->sort()->values()->all() !== $pendingIds) {
            return errorResponse('Send a decision for each pending action in this conversation.', null, 422);
        }

        return $this->respond(fn (): AgentResponse => $this->assistant
            ->continue($conversationId, as: $user)
            ->prompt(Decisions::from($decisions)));
    }

    /**
     * Show the user's own conversation, including any actions still awaiting approval.
     */
    public function show(Request $request, string $conversationId): JsonResponse
    {
        $conversation = $this->findOwnConversation($request->user(), $conversationId);

        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get()
            ->filter(fn (ConversationMessage $message): bool => filled($message->content))
            ->map(fn (ConversationMessage $message): array => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at,
            ])
            ->values();

        return response()->json([
            'conversation_id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $messages,
            'pending_approvals' => $this->serializeApprovals($this->pendingApprovalsFor($conversationId)),
        ]);
    }

    /**
     * @return array<int, PendingApproval>
     */
    protected function pendingApprovalsFor(string $conversationId): array
    {
        return $this->conversationStore instanceof ResolvesPendingApprovals
            ? $this->conversationStore->pendingApprovalsFor($conversationId)
            : [];
    }

    protected function findOwnConversation(User $user, string $conversationId): Conversation
    {
        return Conversation::query()
            ->whereKey($conversationId)
            ->where('participant_type', Conversation::participantType($user))
            ->where('participant_id', Conversation::participantKey($user))
            ->firstOrFail();
    }

    /**
     * @param  callable(): AgentResponse  $prompt
     */
    protected function respond(callable $prompt): JsonResponse
    {
        try {
            $response = $prompt();
        } catch (ApprovalMismatchException|ApprovalNotResumableException $exception) {
            return errorResponse('Those actions are no longer waiting for approval. Reload the conversation and try again.', null, 409);
        } catch (AiException|RequestException|ConnectionException $exception) {
            report($exception);

            return errorResponse('The assistant is unavailable right now. Please try again in a moment.', null, 503);
        }

        return response()->json([
            'conversation_id' => $response->conversationId,
            'reply' => $response->text,
            'pending_approvals' => $this->serializeApprovals($response->pendingApprovals->all()),
        ]);
    }

    /**
     * @param  array<int, PendingApproval>  $approvals
     * @return list<array{id: string, tool: string, summary: ?string, arguments: array<string, mixed>}>
     */
    protected function serializeApprovals(array $approvals): array
    {
        return collect($approvals)
            ->map(fn (PendingApproval $approval): array => [
                'id' => $approval->id,
                'tool' => $approval->tool,
                'summary' => $approval->reason,
                'arguments' => $approval->arguments,
            ])
            ->values()
            ->all();
    }
}

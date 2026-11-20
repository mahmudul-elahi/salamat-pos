<?php

namespace App\Http\Controllers\Pos\Chat;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Chat\ChatConversationFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Chat\ChatConversation;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;

class ChatConversationController extends Controller
{
    public function __construct(protected ChatService $service, protected ChatConversationFilter $filter)
    {
    }

    public function index()
    {
        $perPage = request('per_page', 20);

        return ChatConversation::query()
            ->filters($this->filter)
            ->with([
                'contact:id,name,phone,profile_picture',
                'assignedAgent:id,name',
                'assignedTeam:id,name,icon',
                'labels:id,name,color',
                'latestMessage',
            ])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    public function show(ChatConversation $chat_conversation)
    {
        return $chat_conversation->load([
            'contact',
            'assignedAgent:id,name',
            'assignedTeam:id,name,icon',
            'labels:id,name,color',
        ]);
    }

    public function messages(ChatConversation $chat_conversation)
    {
        $perPage = request('per_page', 30);

        $chat_conversation->update(['unread_count' => 0]);

        return $chat_conversation->messages()
            ->with('sender:id,name')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @throws GeneralException
     */
    public function assign(Request $request, ChatConversation $chat_conversation)
    {
        $data = $request->validate(['agent_id' => ['required', 'exists:users,id']]);

        $conversation = $this->service->assign($chat_conversation, (int) $data['agent_id'], $request->user());

        return successResponse('Conversation assigned successfully', $conversation);
    }

    /**
     * @throws GeneralException
     */
    public function claim(Request $request, ChatConversation $chat_conversation)
    {
        $conversation = $this->service->claim($chat_conversation, $request->user());

        return successResponse('Conversation claimed successfully', $conversation);
    }

    /**
     * @throws GeneralException
     */
    public function reassign(Request $request, ChatConversation $chat_conversation)
    {
        $data = $request->validate(['agent_id' => ['required', 'exists:users,id']]);

        $conversation = $this->service->reassign($chat_conversation, (int) $data['agent_id'], $request->user());

        return successResponse('Conversation reassigned successfully', $conversation);
    }

    /**
     * @throws GeneralException
     */
    public function close(Request $request, ChatConversation $chat_conversation)
    {
        $conversation = $this->service->close($chat_conversation, $request->user());

        return successResponse('Conversation closed successfully', $conversation);
    }

    /**
     * @throws GeneralException
     */
    public function reopen(Request $request, ChatConversation $chat_conversation)
    {
        $conversation = $this->service->reopen($chat_conversation, $request->user());

        return successResponse('Conversation reopened successfully', $conversation);
    }

    /**
     * @throws GeneralException
     */
    public function assignTeam(Request $request, ChatConversation $chat_conversation)
    {
        $data = $request->validate(['team_id' => ['required', 'exists:teams,id']]);

        $conversation = $this->service->assignTeam($chat_conversation, (int) $data['team_id'], $request->user());

        return successResponse('Team assigned successfully', $conversation->load('assignedTeam:id,name,icon'));
    }

    /**
     * @throws GeneralException
     */
    public function updatePriority(Request $request, ChatConversation $chat_conversation)
    {
        $data = $request->validate(['priority' => ['nullable', 'in:low,medium,high,urgent']]);

        $conversation = $this->service->setPriority($chat_conversation, $data['priority'] ?? null, $request->user());

        return successResponse('Priority updated successfully', $conversation);
    }

    /**
     * @throws GeneralException
     */
    public function syncLabels(Request $request, ChatConversation $chat_conversation)
    {
        $data = $request->validate([
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => ['exists:chat_labels,id'],
        ]);

        $conversation = $this->service->syncLabels($chat_conversation, $data['label_ids'] ?? [], $request->user());

        return successResponse('Labels updated successfully', $conversation->load('labels:id,name,color'));
    }

    /**
     * @throws GeneralException
     */
    public function attachContact(Request $request, ChatConversation $chat_conversation)
    {
        $data = $request->validate(['contact_id' => ['required', 'exists:contacts,id']]);

        $conversation = $this->service->attachContact($chat_conversation, (int) $data['contact_id'], $request->user());

        return successResponse('Contact linked successfully', $conversation);
    }
}

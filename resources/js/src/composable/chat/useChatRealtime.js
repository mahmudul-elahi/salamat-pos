import {getEcho} from "@/plugins/echo.js";
import {useChatStore} from "@/stores/chat/chat.js";

let inboxChannel = null;
let conversationChannel = null;
let subscribedConversationId = null;

export function useChatRealtime() {
    const store = useChatStore();

    function subscribeToInbox() {
        if (inboxChannel) {
            return;
        }

        inboxChannel = getEcho()
            .private("chat-inbox")
            .listen(".message.received", (payload) => store.applyIncomingMessage(payload))
            .listen(".conversation.assigned", (payload) => store.applyAssignmentChange(payload));
    }

    function subscribeToConversation(conversationId) {
        if (subscribedConversationId === conversationId) {
            return;
        }

        unsubscribeFromConversation();

        conversationChannel = getEcho()
            .private(`chat-conversation.${conversationId}`)
            .listen(".message.received", (payload) => store.applyIncomingMessage(payload))
            .listen(".message.status-updated", (payload) => {
                const message = store.messages.find((m) => m.id === payload.message_id);
                if (message) {
                    message.status = payload.status;
                    message.error_message = payload.error_message;
                }
            })
            .listen(".conversation.assigned", (payload) => store.applyAssignmentChange(payload));

        subscribedConversationId = conversationId;
    }

    function unsubscribeFromConversation() {
        if (subscribedConversationId) {
            getEcho().leave(`chat-conversation.${subscribedConversationId}`);
            conversationChannel = null;
            subscribedConversationId = null;
        }
    }

    function unsubscribeFromInbox() {
        if (inboxChannel) {
            getEcho().leave("chat-inbox");
            inboxChannel = null;
        }
    }

    return {
        subscribeToInbox,
        subscribeToConversation,
        unsubscribeFromConversation,
        unsubscribeFromInbox,
    };
}

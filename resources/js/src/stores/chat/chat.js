import {defineStore} from "pinia";
import {ref} from "vue";
import Axios from "@/services/axios/index.js";
import {
    CHAT_CONVERSATIONS,
    CHAT_CONVERSATION_DETAILS,
    CHAT_CONVERSATION_MESSAGES,
    CHAT_CONVERSATION_ASSIGN,
    CHAT_CONVERSATION_CLAIM,
    CHAT_CONVERSATION_REASSIGN,
    CHAT_CONVERSATION_CLOSE,
    CHAT_CONVERSATION_REOPEN,
    CHAT_CONVERSATION_ASSIGN_TEAM,
    CHAT_CONVERSATION_PRIORITY,
    CHAT_CONVERSATION_LABELS,
    CHAT_CONVERSATION_ATTACH_CONTACT,
    CHAT_TEAMS,
    CHAT_LABELS,
} from "@/services/endpoints/landlord.js";

export const useChatStore = defineStore("chat", () => {
    const conversations = ref([]);
    const pagination = ref({});
    const activeConversation = ref(null);
    const messages = ref([]);
    const messagesPagination = ref({});
    const filters = ref({});
    const loadingConversations = ref(false);
    const loadingMessages = ref(false);

    async function fetchConversations(params = {}) {
        loadingConversations.value = true;
        try {
            const {data} = await Axios.get(CHAT_CONVERSATIONS, {params: {...filters.value, ...params}});
            conversations.value = data.data;
            pagination.value = data;
        } finally {
            loadingConversations.value = false;
        }
    }

    async function fetchConversationDetails(conversationId) {
        const {data} = await Axios.get(CHAT_CONVERSATION_DETAILS(conversationId));
        applyAssignmentChange(data);
        return data;
    }

    async function fetchMessages(conversationId, params = {}) {
        loadingMessages.value = true;
        try {
            const {data} = await Axios.get(CHAT_CONVERSATION_MESSAGES(conversationId), {params});
            messages.value = [...data.data].reverse();
            messagesPagination.value = data;
        } finally {
            loadingMessages.value = false;
        }
    }

    async function sendMessage(conversationId, payload) {
        const {data} = await Axios.post(CHAT_CONVERSATION_MESSAGES(conversationId), payload);
        messages.value.push(data.result);
        return data.result;
    }

    async function sendAttachment(conversationId, file) {
        const formData = new FormData();
        formData.append("attachment", file);

        // Must NOT hardcode "multipart/form-data" here - it would omit the
        // boundary parameter the server needs to parse the body. Setting the
        // header to undefined removes the axios instance's default
        // "application/json" so the browser can generate the correct
        // multipart Content-Type (with boundary) itself.
        const {data} = await Axios.post(CHAT_CONVERSATION_MESSAGES(conversationId), formData, {
            headers: {"Content-Type": undefined},
        });
        messages.value.push(data.result);
        return data.result;
    }

    async function claim(conversationId) {
        const {data} = await Axios.post(CHAT_CONVERSATION_CLAIM(conversationId));
        applyAssignmentChange(data.result);
        return data.result;
    }

    async function assign(conversationId, agentId) {
        const {data} = await Axios.post(CHAT_CONVERSATION_ASSIGN(conversationId), {agent_id: agentId});
        applyAssignmentChange(data.result);
        return data.result;
    }

    async function reassign(conversationId, agentId) {
        const {data} = await Axios.post(CHAT_CONVERSATION_REASSIGN(conversationId), {agent_id: agentId});
        applyAssignmentChange(data.result);
        return data.result;
    }

    async function close(conversationId) {
        const {data} = await Axios.post(CHAT_CONVERSATION_CLOSE(conversationId));
        applyAssignmentChange(data.result);
        return data.result;
    }

    async function reopen(conversationId) {
        const {data} = await Axios.post(CHAT_CONVERSATION_REOPEN(conversationId));
        applyAssignmentChange(data.result);
        return data.result;
    }

    async function assignTeam(conversationId, teamId) {
        const {data} = await Axios.post(CHAT_CONVERSATION_ASSIGN_TEAM(conversationId), {team_id: teamId});
        applyAssignmentChange(data.result);
        return data.result;
    }

    async function setPriority(conversationId, priority) {
        const {data} = await Axios.patch(CHAT_CONVERSATION_PRIORITY(conversationId), {priority});
        applyAssignmentChange(data.result);
        return data.result;
    }

    async function syncLabels(conversationId, labelIds) {
        const {data} = await Axios.put(CHAT_CONVERSATION_LABELS(conversationId), {label_ids: labelIds});
        applyAssignmentChange(data.result);
        return data.result;
    }

    async function attachContact(conversationId, contactId) {
        const {data} = await Axios.patch(CHAT_CONVERSATION_ATTACH_CONTACT(conversationId), {contact_id: contactId});
        applyAssignmentChange(data.result);
        return data.result;
    }

    async function fetchTeams() {
        const {data} = await Axios.get(CHAT_TEAMS);
        return data;
    }

    async function fetchLabels() {
        const {data} = await Axios.get(CHAT_LABELS);
        return data;
    }

    async function createLabel(payload) {
        const {data} = await Axios.post(CHAT_LABELS, payload);
        return data.result;
    }

    function setActiveConversation(conversation) {
        activeConversation.value = conversation;
    }

    function applyIncomingMessage({message, conversation}) {
        const isActive = activeConversation.value?.id === conversation.id;

        if (isActive && !messages.value.some((m) => m.id === message.id)) {
            messages.value.push(message);
        }

        const index = conversations.value.findIndex((c) => c.id === conversation.id);
        const merged = {...(index >= 0 ? conversations.value[index] : {}), ...conversation, unread_count: isActive ? 0 : conversation.unread_count};

        if (index >= 0) {
            conversations.value.splice(index, 1);
        }
        conversations.value.unshift(merged);
    }

    function applyAssignmentChange(payload) {
        const index = conversations.value.findIndex((c) => c.id === payload.id || c.id === payload.conversation_id);

        if (index >= 0) {
            conversations.value[index] = {...conversations.value[index], ...payload};
        }

        if (activeConversation.value?.id === (payload.id ?? payload.conversation_id)) {
            activeConversation.value = {...activeConversation.value, ...payload};
        }
    }

    return {
        conversations,
        pagination,
        activeConversation,
        messages,
        messagesPagination,
        filters,
        loadingConversations,
        loadingMessages,
        fetchConversations,
        fetchConversationDetails,
        fetchMessages,
        sendMessage,
        sendAttachment,
        claim,
        assign,
        reassign,
        close,
        reopen,
        assignTeam,
        setPriority,
        syncLabels,
        attachContact,
        fetchTeams,
        fetchLabels,
        createLabel,
        setActiveConversation,
        applyIncomingMessage,
        applyAssignmentChange,
    };
});

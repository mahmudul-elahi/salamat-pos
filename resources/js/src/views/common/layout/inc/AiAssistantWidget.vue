<script setup>
import {nextTick, ref} from "vue";
import {useI18n} from "vue-i18n";
import {useToast} from "vue-toastification";
import {Check, Plus, SendDiagonal, Sparks, Xmark} from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import {
    AI_ASSISTANT_APPROVALS,
    AI_ASSISTANT_CONVERSATION,
    AI_ASSISTANT_MESSAGES,
} from "@/services/endpoints/landlord.js";

const STORAGE_KEY = "ai_assistant_conversation_id";
const REQUEST_TIMEOUT = 150000;

const {t} = useI18n();
const toast = useToast();

const isOpen = ref(false);
const isLoaded = ref(false);
const isSending = ref(false);
const conversationId = ref(localStorage.getItem(STORAGE_KEY));
const messages = ref([]);
const pendingApprovals = ref([]);
const draft = ref("");
const messageList = ref(null);

const scrollToBottom = async () => {
    await nextTick();
    if (messageList.value) {
        messageList.value.scrollTop = messageList.value.scrollHeight;
    }
};

const rememberConversation = (id) => {
    conversationId.value = id;
    id ? localStorage.setItem(STORAGE_KEY, id) : localStorage.removeItem(STORAGE_KEY);
};

const loadConversation = async () => {
    isLoaded.value = true;
    if (!conversationId.value) {
        return;
    }
    try {
        const {data} = await Axios.get(AI_ASSISTANT_CONVERSATION(conversationId.value));
        messages.value = data.messages.map(({role, content}) => ({role, content}));
        pendingApprovals.value = data.pending_approvals;
        scrollToBottom();
    } catch (error) {
        rememberConversation(null);
    }
};

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && !isLoaded.value) {
        loadConversation();
    }
    if (isOpen.value) {
        scrollToBottom();
    }
};

const startNewConversation = () => {
    rememberConversation(null);
    messages.value = [];
    pendingApprovals.value = [];
};

const applyReply = (data) => {
    rememberConversation(data.conversation_id);
    if (data.reply) {
        messages.value.push({role: "assistant", content: data.reply});
    }
    pendingApprovals.value = data.pending_approvals ?? [];
    scrollToBottom();
};

const showError = (error) => {
    toast.error(error?.response?.data?.message ?? t("ai_assistant_error"));
};

const send = async () => {
    const message = draft.value.trim();
    if (!message || isSending.value || pendingApprovals.value.length) {
        return;
    }
    messages.value.push({role: "user", content: message});
    draft.value = "";
    isSending.value = true;
    scrollToBottom();
    try {
        const {data} = await Axios.post(
            AI_ASSISTANT_MESSAGES,
            {message, conversation_id: conversationId.value},
            {timeout: REQUEST_TIMEOUT},
        );
        applyReply(data);
    } catch (error) {
        showError(error);
    } finally {
        isSending.value = false;
    }
};

const decide = async (approved) => {
    if (isSending.value || !pendingApprovals.value.length) {
        return;
    }
    const decisions = pendingApprovals.value.map(({id}) => ({id, approved}));
    isSending.value = true;
    try {
        const {data} = await Axios.post(
            AI_ASSISTANT_APPROVALS(conversationId.value),
            {decisions},
            {timeout: REQUEST_TIMEOUT},
        );
        applyReply(data);
    } catch (error) {
        showError(error);
        if (error?.response?.status === 409) {
            loadConversation();
        }
    } finally {
        isSending.value = false;
    }
};

const onKeydown = (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        send();
    }
};
</script>

<template>
    <div class="ai-assistant">
        <div v-if="isOpen" class="ai-assistant__panel shadow-lg">
            <div class="ai-assistant__header">
                <div class="d-flex align-items-center gap-2">
                    <sparks width="18" height="18"/>
                    <strong>{{ $t('ai_assistant') }}</strong>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm ai-assistant__icon-button"
                            :title="$t('ai_assistant_new_chat')" :disabled="isSending"
                            @click="startNewConversation">
                        <plus width="18" height="18"/>
                    </button>
                    <button type="button" class="btn btn-sm ai-assistant__icon-button"
                            :title="$t('close')" @click="toggle">
                        <xmark width="18" height="18"/>
                    </button>
                </div>
            </div>

            <div ref="messageList" class="ai-assistant__messages">
                <p v-if="!messages.length" class="ai-assistant__empty">
                    {{ $t('ai_assistant_intro') }}
                </p>
                <div v-for="(message, index) in messages" :key="index"
                     class="ai-assistant__message"
                     :class="`ai-assistant__message--${message.role}`">
                    {{ message.content }}
                </div>

                <div v-for="approval in pendingApprovals" :key="approval.id" class="ai-assistant__approval">
                    <div class="ai-assistant__approval-title">{{ $t('ai_assistant_approval_needed') }}</div>
                    <div>{{ approval.summary ?? approval.tool }}</div>
                    <details class="mt-1">
                        <summary>{{ $t('ai_assistant_details') }}</summary>
                        <pre class="ai-assistant__arguments">{{ JSON.stringify(approval.arguments, null, 2) }}</pre>
                    </details>
                </div>
                <div v-if="pendingApprovals.length" class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                            :disabled="isSending" @click="decide(true)">
                        <check width="16" height="16"/>
                        {{ $t('ai_assistant_approve') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            :disabled="isSending" @click="decide(false)">
                        {{ $t('ai_assistant_reject') }}
                    </button>
                </div>

                <div v-if="isSending" class="ai-assistant__message ai-assistant__message--assistant ai-assistant__typing">
                    {{ $t('ai_assistant_thinking') }}
                </div>
            </div>

            <form class="ai-assistant__composer" @submit.prevent="send">
                <textarea v-model="draft" rows="3" maxlength="4000" class="form-control"
                          :placeholder="pendingApprovals.length ? $t('ai_assistant_decide_first') : $t('ai_assistant_placeholder')"
                          :disabled="isSending || pendingApprovals.length > 0"
                          @keydown="onKeydown"/>
                <button type="submit" class="btn btn-primary"
                        :title="$t('ai_assistant_send')"
                        :disabled="isSending || !draft.trim() || pendingApprovals.length > 0">
                    <send-diagonal width="18" height="18"/>
                </button>
            </form>
        </div>

        <button type="button" class="btn btn-primary ai-assistant__launcher shadow"
                :title="$t('ai_assistant')" @click="toggle">
            <xmark v-if="isOpen" width="24" height="24"/>
            <sparks v-else width="24" height="24"/>
        </button>
    </div>
</template>

<style scoped>
.ai-assistant {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 1050;
}

.ai-assistant__launcher {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
}

.ai-assistant__panel {
    width: min(520px, calc(100vw - 32px));
    height: min(760px, calc(100vh - 120px));
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
    background: var(--white);
    color: var(--font-color);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
}

.ai-assistant__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-color);
}

.ai-assistant__icon-button {
    color: var(--font-color);
}

.ai-assistant__messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ai-assistant__empty {
    color: var(--primary-paragraph);
    margin: auto 0;
    text-align: center;
}

.ai-assistant__message {
    max-width: 88%;
    padding: 10px 14px;
    border-radius: 12px;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 14px;
}

.ai-assistant__message--user {
    align-self: flex-end;
    background: var(--primary);
    color: #fff;
}

.ai-assistant__message--assistant {
    align-self: flex-start;
    background: var(--base-color);
}

.ai-assistant__typing {
    opacity: 0.7;
    font-style: italic;
}

.ai-assistant__approval {
    border: 1px solid var(--primary);
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 14px;
}

.ai-assistant__approval-title {
    font-weight: 600;
    margin-bottom: 4px;
}

.ai-assistant__arguments {
    max-height: 260px;
    overflow: auto;
    font-size: 12px;
    margin: 6px 0 0;
    white-space: pre-wrap;
}

.ai-assistant__composer {
    display: flex;
    gap: 8px;
    padding: 12px;
    border-top: 1px solid var(--border-color);
}

.ai-assistant__composer textarea {
    resize: none;
}
</style>

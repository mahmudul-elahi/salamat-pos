<script setup>
import {computed, nextTick, onBeforeUnmount, ref, watch} from "vue";
import {storeToRefs} from "pinia";
import {useToast} from "vue-toastification";
import {InfoCircle, Attachment as AttachmentIcon, Microphone, MicrophoneMute, SendDiagonal} from "@iconoir/vue";
import {useChatStore} from "@/stores/chat/chat.js";
import {useChatRealtime} from "@/composable/chat/useChatRealtime.js";
import {useProfileStore} from "@/stores/profile/profile.js";
import usePermission from "@/composable/permission/usePermission.js";
import AssignAgentModal from "./AssignAgentModal.vue";
import TemplatePickerModal from "./TemplatePickerModal.vue";
import EmojiPicker from "./EmojiPicker.vue";
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
    conversation: {type: Object, default: null},
    showInfoPanel: {type: Boolean, default: false},
});

const emit = defineEmits(["toggle-info"]);

const MAX_SIZES_MB = {image: 5, video: 16, audio: 16, document: 100};

const toast = useToast();
const store = useChatStore();
const {messages, loadingMessages} = storeToRefs(store);
const {subscribeToConversation} = useChatRealtime();
const profileStore = useProfileStore();
const {canAccess, isSupperAdmin} = usePermission();

const messageBody = ref("");
const sending = ref(false);
const uploadingAttachment = ref(false);
const scrollContainer = ref(null);
const showAssignModal = ref(false);
const assignMode = ref("assign");
const showTemplateModal = ref(false);
const fileInput = ref(null);

const isRecording = ref(false);
const recordingSeconds = ref(0);
let mediaRecorder = null;
let recordedChunks = [];
let recordingInterval = null;
let mediaStream = null;

const isAssignedToMe = computed(() => props.conversation?.assigned_agent_id === profileStore.profileData?.id);
const canReply = computed(() => isSupperAdmin() || isAssignedToMe.value);
const isWhatsApp = computed(() => props.conversation?.channel === "whatsapp");

const isWithinSessionWindow = computed(() => {
    if (!props.conversation?.last_customer_message_at) return false;
    const last = new Date(props.conversation.last_customer_message_at.replace(' ', 'T'));
    return (Date.now() - last.getTime()) < 24 * 60 * 60 * 1000;
});

function channelBadgeClass(channel) {
    if (channel === "messenger") return "bg-primary";
    if (channel === "instagram") return "bg-info";
    return "bg-success";
}

function channelLabel(channel) {
    if (channel === "messenger") return "Messenger";
    if (channel === "instagram") return "Instagram";
    return "WhatsApp";
}

async function loadMessages() {
    if (!props.conversation) return;
    await Promise.all([
        store.fetchMessages(props.conversation.id),
        store.fetchConversationDetails(props.conversation.id),
    ]);
    subscribeToConversation(props.conversation.id);
    scrollToBottom();
}

function scrollToBottom() {
    nextTick(() => {
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight;
        }
    });
}

async function sendMessage() {
    if (!messageBody.value.trim() || !props.conversation) return;

    sending.value = true;
    try {
        await store.sendMessage(props.conversation.id, {type: "text", body: messageBody.value});
        messageBody.value = "";
        scrollToBottom();
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to send message");
    } finally {
        sending.value = false;
    }
}

function insertEmoji(emoji) {
    messageBody.value += emoji;
}

function triggerFileSelect() {
    fileInput.value?.click();
}

function attachmentTypeFor(file) {
    if (file.type.startsWith("image/")) return "image";
    if (file.type.startsWith("video/")) return "video";
    if (file.type.startsWith("audio/")) return "audio";
    return "document";
}

async function uploadAttachment(file) {
    const type = attachmentTypeFor(file);
    const maxMb = MAX_SIZES_MB[type];

    if (file.size > maxMb * 1024 * 1024) {
        toast.error(`This ${type} exceeds the ${maxMb}MB limit`);
        return;
    }

    uploadingAttachment.value = true;
    try {
        await store.sendAttachment(props.conversation.id, file);
        scrollToBottom();
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to send attachment");
    } finally {
        uploadingAttachment.value = false;
    }
}

async function handleFileSelected(event) {
    const file = event.target.files?.[0];
    event.target.value = "";
    if (file) await uploadAttachment(file);
}

async function startRecording() {
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({audio: true});
    } catch (error) {
        toast.error("Microphone access denied");
        return;
    }

    recordedChunks = [];
    // Without an explicit mimeType, some browsers (Chrome) default
    // MediaRecorder to "video/webm" even for an audio-only stream, which
    // then makes attachmentTypeFor() below misclassify the recording as a
    // video and Messenger rejects/mishandles the resulting attachment.
    // Force an audio-only mimeType, preferring one Messenger's Send API
    // actually documents support for (m4a/mp4) when the browser can
    // encode it, falling back to the webm opus codec Chrome/Firefox support.
    const audioMimeType = ["audio/mp4", "audio/webm;codecs=opus", "audio/webm"]
        .find((candidate) => MediaRecorder.isTypeSupported?.(candidate));
    mediaRecorder = new MediaRecorder(mediaStream, audioMimeType ? {mimeType: audioMimeType} : undefined);
    mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) recordedChunks.push(event.data);
    };
    mediaRecorder.onstop = async () => {
        const blob = new Blob(recordedChunks, {type: mediaRecorder.mimeType || "audio/webm"});
        const extension = (mediaRecorder.mimeType || "audio/webm").includes("mp4") ? "m4a" : "webm";
        const file = new File([blob], `voice-note-${Date.now()}.${extension}`, {type: blob.type});
        mediaStream?.getTracks().forEach((track) => track.stop());
        mediaStream = null;
        await uploadAttachment(file);
    };

    mediaRecorder.start();
    isRecording.value = true;
    recordingSeconds.value = 0;
    recordingInterval = setInterval(() => (recordingSeconds.value += 1), 1000);
}

function stopRecording() {
    mediaRecorder?.stop();
    isRecording.value = false;
    clearInterval(recordingInterval);
}

function formatRecordingTime(seconds) {
    const m = Math.floor(seconds / 60).toString().padStart(2, "0");
    const s = (seconds % 60).toString().padStart(2, "0");
    return `${m}:${s}`;
}

async function claimConversation() {
    try {
        await store.claim(props.conversation.id);
        toast.success("Conversation claimed");
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to claim conversation");
    }
}

async function toggleClose() {
    try {
        if (props.conversation.status === "closed") {
            await store.reopen(props.conversation.id);
        } else {
            await store.close(props.conversation.id);
        }
    } catch (error) {
        toast.error(error?.response?.data?.message || "Action failed");
    }
}

function openAssignModal(mode) {
    assignMode.value = mode;
    showAssignModal.value = true;
}

watch(() => props.conversation?.id, loadMessages, {immediate: true});
watch(messages, scrollToBottom, {deep: true});

onBeforeUnmount(() => {
    clearInterval(recordingInterval);
    mediaStream?.getTracks().forEach((track) => track.stop());
});
</script>

<template>
    <div class="chat-panel d-flex flex-column h-100">
        <template v-if="!conversation">
            <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                Select a conversation to start chatting
            </div>
        </template>

        <template v-else>
            <div class="chat-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <Avatar
                        :image="conversation.contact?.profile_picture"
                        :name="conversation.contact?.name || conversation.profile_name || conversation.external_id"
                    />
                    <div>
                        <div class="fw-semibold d-flex align-items-center gap-2">
                            {{ conversation.contact?.name || conversation.profile_name || conversation.external_id }}
                            <span class="chip" :class="channelBadgeClass(conversation.channel)">
                                {{ channelLabel(conversation.channel) }}
                            </span>
                        </div>
                        <div class="text-muted small">{{ conversation.external_id }}</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted small">
                        {{ conversation.assigned_agent?.name || 'Unassigned' }}
                    </span>

                    <button
                        v-if="!conversation.assigned_agent_id && canAccess('claim_chat_conversations')"
                        type="button"
                        class="btn btn-sm btn-pill-outline"
                        @click="claimConversation"
                    >
                        Claim
                    </button>

                    <button
                        v-if="canAccess('assign_chat_conversations')"
                        type="button"
                        class="btn btn-sm btn-pill-outline"
                        @click="openAssignModal('assign')"
                    >
                        Assign
                    </button>

                    <button
                        v-if="conversation.assigned_agent_id && (isAssignedToMe || isSupperAdmin()) && canAccess('reassign_chat_conversations')"
                        type="button"
                        class="btn btn-sm btn-pill-outline"
                        @click="openAssignModal('reassign')"
                    >
                        Reassign
                    </button>

                    <button
                        v-if="canReply"
                        type="button"
                        class="btn btn-sm btn-pill-outline"
                        @click="toggleClose"
                    >
                        {{ conversation.status === 'closed' ? 'Reopen' : 'Close' }}
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-icon-round"
                        :class="{ 'is-active': showInfoPanel }"
                        title="Conversation details"
                        @click="emit('toggle-info')"
                    >
                        <InfoCircle width="18" height="18" />
                    </button>
                </div>
            </div>

            <div class="flex-grow-1 overflow-auto p-3 chat-thread" ref="scrollContainer">
                <app-loader v-if="loadingMessages" />

                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="d-flex mb-2"
                    :class="message.direction === 'outbound' ? 'justify-content-end' : 'justify-content-start'"
                >
                    <div
                        class="message-bubble"
                        :class="message.direction === 'outbound' ? 'bubble-out' : 'bubble-in'"
                    >
                        <img
                            v-if="message.type === 'image' && message.media_url"
                            :src="message.media_url"
                            class="rounded mb-1"
                            style="max-width: 100%; max-height: 260px; cursor: pointer"
                            @click="window.open(message.media_url, '_blank')"
                            alt=""
                        />
                        <video
                            v-else-if="message.type === 'video' && message.media_url"
                            :src="message.media_url"
                            controls
                            class="rounded mb-1"
                            style="max-width: 100%; max-height: 260px"
                        ></video>
                        <audio
                            v-else-if="message.type === 'audio' && message.media_url"
                            :src="message.media_url"
                            controls
                            class="mb-1"
                        ></audio>
                        <a
                            v-else-if="message.type === 'document' && message.media_url"
                            :href="message.media_url"
                            target="_blank"
                            class="d-block mb-1"
                            :class="message.direction === 'outbound' ? 'text-white' : ''"
                        >
                            📎 Download attachment
                        </a>

                        <div v-if="message.body" style="white-space: pre-wrap">{{ message.body }}</div>

                        <div class="small mt-1 bubble-meta" :class="message.direction === 'outbound' ? 'text-white-50' : 'text-muted'">
                            {{ message.created_at }}
                            <span v-if="message.direction === 'outbound'"> · {{ message.status }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 composer-area">
                <template v-if="!canReply">
                    <div class="alert alert-warning mb-0">
                        You must be assigned to this conversation to reply.
                    </div>
                </template>

                <template v-else-if="!isWithinSessionWindow">
                    <div class="alert alert-warning d-flex justify-content-between align-items-center mb-0">
                        <span v-if="isWhatsApp">The 24-hour customer service window is closed. Send a template message to reopen it.</span>
                        <span v-else>The 24-hour messaging window is closed. The customer needs to message you again before you can reply.</span>
                        <button v-if="isWhatsApp" type="button" class="btn btn-sm btn-primary" @click="showTemplateModal = true">
                            Send Template
                        </button>
                    </div>
                </template>

                <div v-else-if="isRecording" class="d-flex align-items-center gap-2 recording-bar">
                    <span class="recording-dot"></span>
                    <span class="text-danger fw-semibold">Recording {{ formatRecordingTime(recordingSeconds) }}</span>
                    <button type="button" class="btn btn-sm btn-danger ms-auto rounded-pill" @click="stopRecording">
                        <MicrophoneMute width="16" height="16" /> Stop &amp; Send
                    </button>
                </div>

                <div v-else class="composer d-flex gap-1 align-items-center">
                    <EmojiPicker @select="insertEmoji" />

                    <button
                        type="button"
                        class="btn btn-sm btn-icon-round"
                        title="Attach file"
                        :disabled="uploadingAttachment"
                        @click="triggerFileSelect"
                    >
                        <AttachmentIcon width="18" height="18" />
                    </button>
                    <input ref="fileInput" type="file" class="d-none" @change="handleFileSelected" />

                    <button
                        type="button"
                        class="btn btn-sm btn-icon-round"
                        title="Record voice message"
                        :disabled="uploadingAttachment"
                        @click="startRecording"
                    >
                        <Microphone width="18" height="18" />
                    </button>

                    <textarea
                        class="composer-input"
                        rows="1"
                        placeholder="Type a message"
                        v-model="messageBody"
                        @keydown.enter.exact.prevent="sendMessage"
                    ></textarea>
                    <button type="button" class="btn btn-send" :disabled="sending || uploadingAttachment" @click="sendMessage">
                        <app-button-loader v-if="sending || uploadingAttachment" />
                        <SendDiagonal v-else width="18" height="18" />
                    </button>
                </div>
            </div>

            <AssignAgentModal
                v-if="showAssignModal"
                modal-id="chat-assign-agent-modal"
                :conversation-id="conversation.id"
                :mode="assignMode"
                :current-agent-id="conversation.assigned_agent_id"
                @close="showAssignModal = false"
            />

            <TemplatePickerModal
                v-if="showTemplateModal"
                modal-id="chat-template-picker-modal"
                :conversation-id="conversation.id"
                @close="showTemplateModal = false"
            />
        </template>
    </div>
</template>

<style scoped lang="scss">
.chat-panel {
    background: var(--white, #fff);
}

.chat-header {
    padding: 14px 20px;
    background: var(--white, #fff);
    box-shadow: 0 1px 0 var(--border-color, #eceff1), 0 2px 8px rgba(0, 0, 0, 0.03);
    z-index: 2;
}

.chip {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 2px 9px;
    border-radius: 999px;
    color: #fff;
    text-transform: capitalize;
}

.btn-pill-outline {
    border: 1px solid var(--border-color, #dfe3e8);
    background: transparent;
    border-radius: 999px;
    padding: 4px 14px;
    font-size: 0.8125rem;
    color: var(--font-color, #333);
    transition: all 0.15s ease-in-out;

    &:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: rgba(var(--primary-rgb, 43, 135, 104), 0.06);
    }
}

.btn-icon-round {
    width: 34px;
    height: 34px;
    padding: 0;
    border: none;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: var(--muted-color, #8a8a8a);
    transition: all 0.15s ease-in-out;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        background: rgba(0, 0, 0, 0.05);
        color: var(--font-color, #333);
    }

    &.is-active {
        background: rgba(var(--primary-rgb, 43, 135, 104), 0.12);
        color: var(--primary);
    }
}

.chat-thread {
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.015) 0%, rgba(0, 0, 0, 0.025) 100%);
}

.message-bubble {
    padding: 10px 14px;
    max-width: 70%;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
    font-size: 0.9rem;
    line-height: 1.4;
}

.bubble-in {
    background: #fff;
    color: var(--font-color, #333);
    border-radius: 4px 16px 16px 16px;
}

.bubble-out {
    background: var(--primary);
    color: #fff;
    border-radius: 16px 4px 16px 16px;
}

.bubble-meta {
    font-size: 0.6875rem;
    opacity: 0.75;
}

.composer-area {
    background: var(--white, #fff);
    border-top: 1px solid var(--border-color, #eceff1);
}

.composer {
    background: rgba(0, 0, 0, 0.03);
    border-radius: 999px;
    padding: 6px 8px;
}

.composer-input {
    flex-grow: 1;
    border: none;
    background: transparent;
    resize: none;
    padding: 6px 4px;
    font-size: 0.9rem;
    max-height: 120px;

    &:focus {
        outline: none;
        box-shadow: none;
    }
}

.btn-send {
    width: 38px;
    height: 38px;
    padding: 0;
    border: none;
    border-radius: 50%;
    background: var(--primary);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.1s ease-in-out;

    &:hover:not(:disabled) {
        transform: scale(1.05);
    }

    &:disabled {
        opacity: 0.6;
    }
}

.recording-bar {
    padding: 6px 4px;
}

.recording-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--danger, #ff4842);
    display: inline-block;
    animation: pulse 1.2s infinite;
}

@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.35;
    }
    100% {
        opacity: 1;
    }
}
</style>

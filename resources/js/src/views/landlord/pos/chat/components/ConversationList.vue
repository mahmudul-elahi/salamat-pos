<script setup>
import {onMounted, ref} from "vue";
import {useI18n} from "vue-i18n";
import {isToday} from "date-fns";
import {Search} from "@iconoir/vue";
import {useChatStore} from "@/stores/chat/chat.js";
import {storeToRefs} from "pinia";
import {useTimezone} from "@/utils/useTimezone.js";
import Avatar from "@/views/global/avatar/Avatar.vue";

const {t} = useI18n();
const {formatTime, formatDate, toUserTz} = useTimezone();
const store = useChatStore();
const {conversations, loadingConversations, activeConversation} = storeToRefs(store);

const emit = defineEmits(["select"]);

const activeTab = ref("all");
const search = ref("");

const tabs = [
    {key: "all", label: "All"},
    {key: "unassigned", label: "Unassigned"},
    {key: "assigned_to_me", label: "Mine"},
    {key: "closed", label: "Closed"},
];

function applyFilters() {
    const filters = {search: search.value || undefined};

    if (activeTab.value === "unassigned") {
        filters.unassigned = 1;
    } else if (activeTab.value === "assigned_to_me") {
        filters.assigned_to_me = 1;
    } else if (activeTab.value === "closed") {
        filters.status = "closed";
    }

    store.filters = filters;
    store.fetchConversations();
}

function selectConversation(conversation) {
    emit("select", conversation);
}

function statusBadgeClass(status) {
    if (status === "closed") return "bg-secondary";
    if (status === "pending") return "bg-warning";
    return "bg-success";
}

function channelBadgeClass(channel) {
    if (channel === "messenger") return "bg-primary";
    if (channel === "instagram") return "bg-info";
    return "bg-success";
}

function priorityBadgeClass(priority) {
    if (priority === "urgent") return "bg-danger";
    if (priority === "high") return "bg-warning";
    if (priority === "medium") return "bg-info";
    return "bg-secondary";
}

function channelLabel(channel) {
    if (channel === "messenger") return "Messenger";
    if (channel === "instagram") return "Instagram";
    return "WhatsApp";
}

function lastMessageTime(conversation) {
    const raw = conversation.latest_message?.created_at || conversation.last_message_at;
    if (!raw) return "";
    return isToday(toUserTz(raw)) ? formatTime(raw) : formatDate(raw, "dd MMM");
}

onMounted(() => {
    applyFilters();
});
</script>

<template>
    <div class="chat-conversation-list d-flex flex-column h-100">
        <div class="p-3 search-area">
            <div class="search-box">
                <Search width="16" height="16" class="search-icon" />
                <input
                    type="search"
                    class="search-input"
                    :placeholder="t('search') || 'Search by name or phone'"
                    v-model="search"
                    @input="applyFilters"
                />
            </div>
        </div>

        <div class="px-3 py-2 d-flex flex-nowrap gap-1 chat-tabs">
            <button
                type="button"
                class="chat-tab-btn"
                v-for="tab in tabs"
                :key="tab.key"
                :class="{ active: activeTab === tab.key }"
                @click="activeTab = tab.key; applyFilters()"
            >
                {{ tab.label }}
            </button>
        </div>

        <div class="flex-grow-1 overflow-auto">
            <app-loader v-if="loadingConversations" />

            <div v-else-if="!conversations.length" class="text-center text-muted p-4">
                No conversations found
            </div>

            <button
                v-for="conversation in conversations"
                :key="conversation.id"
                type="button"
                class="w-100 text-start border-0 conversation-row p-3 d-flex gap-3"
                :class="{ active: activeConversation?.id === conversation.id }"
                @click="selectConversation(conversation)"
            >
                <div class="avatar-badge-wrapper flex-shrink-0">
                    <Avatar
                        :image="conversation.contact?.profile_picture"
                        :name="conversation.contact?.name || conversation.profile_name || conversation.external_id"
                    />
                    <span
                        class="channel-indicator"
                        :class="channelBadgeClass(conversation.channel)"
                        :title="channelLabel(conversation.channel)"
                    ></span>
                </div>

                <div class="flex-grow-1" style="min-width: 0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="fw-semibold text-truncate d-flex align-items-center gap-2">
                            <span
                                v-if="conversation.priority"
                                class="priority-dot"
                                :class="priorityBadgeClass(conversation.priority)"
                                :title="`Priority: ${conversation.priority}`"
                            ></span>
                            {{ conversation.contact?.name || conversation.profile_name || conversation.external_id }}
                        </div>
                        <div class="d-flex flex-column align-items-end gap-1 ms-2 flex-shrink-0">
                            <span class="last-message-time">{{ lastMessageTime(conversation) }}</span>
                            <span v-if="conversation.unread_count" class="unread-badge">
                                {{ conversation.unread_count }}
                            </span>
                        </div>
                    </div>
                    <div class="text-muted small text-truncate">{{ conversation.external_id }}</div>
                    <div class="text-muted small text-truncate last-message">
                        {{ conversation.latest_message?.body || '—' }}
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="d-flex gap-1">
                            <span class="chip" :class="channelBadgeClass(conversation.channel)">
                                {{ channelLabel(conversation.channel) }}
                            </span>
                            <span class="chip" :class="statusBadgeClass(conversation.status)">
                                {{ conversation.status }}
                            </span>
                        </div>
                        <span class="text-muted small text-truncate">
                            {{ conversation.assigned_agent?.name || 'Unassigned' }}
                        </span>
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>

<style scoped lang="scss">
.chat-conversation-list {
    background: var(--white, #fff);
    border-right: 1px solid var(--border-color, #eceff1);
}

.search-area {
    border-bottom: 1px solid var(--border-color, #eceff1);
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;

    .search-icon {
        position: absolute;
        left: 14px;
        color: var(--muted-color, #b8b8b8);
        pointer-events: none;
    }

    .search-input {
        width: 100%;
        border: 1px solid var(--border-color, #eceff1);
        background: rgba(0, 0, 0, 0.02);
        border-radius: 999px;
        padding: 8px 14px 8px 38px;
        font-size: 0.875rem;
        transition: all 0.15s ease-in-out;

        &:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb, 43, 135, 104), 0.1);
        }
    }
}

.chat-tabs {
    border-bottom: 1px solid var(--border-color, #eceff1);
}

.chat-tab-btn {
    border: 1px solid var(--border-color, #eceff1);
    background: transparent;
    padding: 4px 9px;
    border-radius: 999px;
    font-size: 0.6875rem;
    font-weight: 500;
    color: var(--muted-color, #8a8a8a);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1 1 auto;
    min-width: 0;
    text-align: center;
    transition: all 0.15s ease-in-out;

    &:hover {
        background: rgba(0, 0, 0, 0.04);
        color: var(--font-color, #333);
        border-color: transparent;
    }

    &.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }
}

.conversation-row {
    background: transparent;
    cursor: pointer;
    border-bottom: 1px solid var(--border-color, #f1f1f1);
    transition: background-color 0.15s ease-in-out;
    position: relative;

    &:hover {
        background: rgba(0, 0, 0, 0.025);
    }

    &.active {
        background: rgba(var(--primary-rgb, 43, 135, 104), 0.08);

        &::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary);
        }
    }
}

.avatar-badge-wrapper {
    position: relative;
    height: fit-content;
}

.channel-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.priority-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.last-message {
    opacity: 0.85;
}

.last-message-time {
    font-size: 0.6875rem;
    color: var(--muted-color, #8a8a8a);
    white-space: nowrap;
}

.unread-badge {
    background: var(--primary);
    color: #fff;
    font-size: 0.6875rem;
    font-weight: 600;
    min-width: 20px;
    height: 20px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}

.chip {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 2px 9px;
    border-radius: 999px;
    color: #fff;
    text-transform: capitalize;
}
</style>

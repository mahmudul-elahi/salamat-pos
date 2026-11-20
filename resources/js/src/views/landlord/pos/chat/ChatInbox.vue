<script setup>
import {onBeforeUnmount, onMounted, ref} from "vue";
import {useChatStore} from "@/stores/chat/chat.js";
import {useChatRealtime} from "@/composable/chat/useChatRealtime.js";
import ConversationList from "./components/ConversationList.vue";
import ChatPanel from "./components/ChatPanel.vue";
import ContactInfoPanel from "./components/ContactInfoPanel.vue";

const store = useChatStore();
const {subscribeToInbox, unsubscribeFromInbox, unsubscribeFromConversation} = useChatRealtime();

const showInfoPanel = ref(false);

function selectConversation(conversation) {
    store.setActiveConversation(conversation);
}

onMounted(() => {
    subscribeToInbox();
});

onBeforeUnmount(() => {
    unsubscribeFromInbox();
    unsubscribeFromConversation();
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Chat" />

        <div class="card border-0 chat-shell" style="height: calc(100vh - 220px)">
            <div class="d-flex h-100">
                <div class="chat-column-list h-100">
                    <ConversationList @select="selectConversation" />
                </div>
                <div class="flex-grow-1 h-100" style="min-width: 0">
                    <ChatPanel
                        :conversation="store.activeConversation"
                        :show-info-panel="showInfoPanel"
                        @toggle-info="showInfoPanel = !showInfoPanel"
                    />
                </div>
                <ContactInfoPanel v-if="showInfoPanel && store.activeConversation" :conversation="store.activeConversation" />
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.chat-shell {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
}

.chat-column-list {
    width: 320px;
    flex-shrink: 0;
}
</style>

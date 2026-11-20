<script setup>
import {ref} from "vue";
import {useToast} from "vue-toastification";
import {SELECTED_SELLERS} from "@/services/endpoints/landlord.js";
import {useChatStore} from "@/stores/chat/chat.js";

const props = defineProps({
    modalId: {type: String, required: true},
    conversationId: {type: [Number, String], required: true},
    mode: {type: String, default: "assign"},
    currentAgentId: {type: [Number, String], default: null},
});

const emit = defineEmits(["close"]);

const toast = useToast();
const store = useChatStore();
const agentId = ref(props.currentAgentId);
const preloader = ref(false);

const submit = async () => {
    if (!agentId.value) {
        toast.error("Please choose an agent");
        return;
    }

    preloader.value = true;
    try {
        if (props.mode === "reassign") {
            await store.reassign(props.conversationId, agentId.value);
        } else {
            await store.assign(props.conversationId, agentId.value);
        }

        toast.success("Conversation assigned successfully");
        emit("close");
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to assign conversation");
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <app-modal
        :modal-id="modalId"
        :title="mode === 'reassign' ? 'Reassign Conversation' : 'Assign Conversation'"
        save-button-label="Assign"
        :preloader="preloader"
        @submit="submit"
        @close="$emit('close')"
    >
        <template #body>
            <app-input
                type="advance-search-select"
                id="chat_agent_id"
                label="Agent"
                label-required
                list-key-field="id"
                list-value-field="name"
                v-model="agentId"
                :loading-length="10"
                :fetch-url="`${SELECTED_SELLERS}?active_only=1`"
                choose-label="Choose an agent"
            />
        </template>
    </app-modal>
</template>

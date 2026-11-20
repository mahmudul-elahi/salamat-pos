<script setup>
import {onMounted, ref} from "vue";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import {WHATSAPP_TEMPLATES} from "@/services/endpoints/landlord.js";
import {useChatStore} from "@/stores/chat/chat.js";

const props = defineProps({
    modalId: {type: String, required: true},
    conversationId: {type: [Number, String], required: true},
});

const emit = defineEmits(["close"]);

const toast = useToast();
const store = useChatStore();
const templates = ref([]);
const selectedTemplateName = ref(null);
const bodyParams = ref("");
const preloader = ref(false);
const loadingTemplates = ref(false);

async function loadTemplates() {
    loadingTemplates.value = true;
    try {
        const {data} = await Axios.get(WHATSAPP_TEMPLATES);
        templates.value = data;
    } catch (error) {
        toast.error("Failed to load templates");
    } finally {
        loadingTemplates.value = false;
    }
}

const submit = async () => {
    if (!selectedTemplateName.value) {
        toast.error("Please choose a template");
        return;
    }

    const template = templates.value.find((t) => t.name === selectedTemplateName.value);

    preloader.value = true;
    try {
        await store.sendMessage(props.conversationId, {
            type: "template",
            template_name: selectedTemplateName.value,
            language_code: template?.language || "en_US",
            body_params: bodyParams.value ? bodyParams.value.split(",").map((p) => p.trim()) : [],
        });

        toast.success("Template message sent");
        emit("close");
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to send template message");
    } finally {
        preloader.value = false;
    }
};

onMounted(loadTemplates);
</script>

<template>
    <app-modal
        :modal-id="modalId"
        title="Send Template Message"
        save-button-label="Send"
        :preloader="preloader"
        @submit="submit"
        @close="$emit('close')"
    >
        <template #body>
            <app-loader v-if="loadingTemplates" />

            <template v-else>
                <div v-if="!templates.length" class="alert alert-warning">
                    No approved templates found. Sync templates from Meta once real credentials are configured.
                </div>

                <app-input
                    v-else
                    type="select"
                    id="whatsapp_template_name"
                    label="Template"
                    label-required
                    :options="templates"
                    list-key-field="name"
                    list-value-field="name"
                    choose-label="Choose a template"
                    v-model="selectedTemplateName"
                />

                <app-input
                    type="text"
                    id="whatsapp_template_params"
                    label="Body parameters (comma separated)"
                    placeholder="e.g. John, #12345"
                    v-model="bodyParams"
                    class="mt-3"
                />
            </template>
        </template>
    </app-modal>
</template>

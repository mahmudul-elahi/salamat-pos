<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";

const props = defineProps({
    modalId: String,
    media: Object
})

const emit = defineEmits(['close', 'copy-url'])

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit, 'yes')

</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" title="Media Image Information" @close="closeModal">
        <template #body>
            <div v-if="media">
                <div class="text-center mb-3">
                    <img :src="media.url" class="preview-img" alt="Media" />
                </div>

                <ul class="list-group">
                    <li class="list-group-item"><strong>Name:</strong> {{ media.name }}</li>
                    <li class="list-group-item"><strong>Type:</strong> {{ media.mime_type }}</li>
                    <li class="list-group-item"><strong>Size:</strong> {{ (media.size / 1024).toFixed(2) }} KB</li>
                    <li class="list-group-item"><strong>Uploaded:</strong> {{ media.created_at }}</li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><strong>URL:</strong> {{ media.url }}</span>
                        <!-- <button class="btn btn-sm btn-primary" @click="$emit('copy-url', media.url)">Copy</button> -->
                    </li>
                </ul>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.preview-img {
    width: 200px;
    height: auto;
    border-radius: 8px;
    border: 1px solid #ddd;
}
</style>
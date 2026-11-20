<script setup>
import { ref } from "vue";
import { useToast } from "vue-toastification";
import { Modal } from "bootstrap";
import Axios from "@/services/axios";
import useEmitter from "@/composable/emitter/useEmitter";

// Shared by the offline customer, online contact and supplier lists — they all
// store the file on the same `contacts.price_document` column, only the POST
// url differs.
const props = defineProps({ modalId: String, tableId: String, uploadUrl: String, contact: Object });
const emit = defineEmits(["close"]);

const toast = useToast();
const emitter = useEmitter();

// Matches the size the media library uploads at, and comfortably under the
// default post_max_size so no server tuning is needed.
const CHUNK_SIZE = 2 * 1024 * 1024;

const file = ref(null);
const preloader = ref(false);
const progress = ref(0);
const errors = ref({});

const onFileChange = (event) => {
    file.value = event.target.files[0] || null;
    errors.value = {};
    progress.value = 0;
};

const closeModal = () => {
    Modal.getInstance(document.getElementById(props.modalId))?.hide();
    emit("close");
};

/**
 * Sends the file a slice at a time. The server answers `done: false` until the
 * last slice lands, so nothing is written to the contact until the whole file
 * is there. Sequential, not parallel — the server merges by index and needs
 * every slice, and a serial loop gives an honest progress figure.
 */
const submit = async () => {
    if (!file.value) {
        toast.error("Please choose a file first");
        return;
    }

    const totalChunks = Math.ceil(file.value.size / CHUNK_SIZE) || 1;
    const fileId = `${Date.now()}-${Math.random().toString(36).slice(2)}`;

    preloader.value = true;
    errors.value = {};
    progress.value = 0;

    try {
        for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
            const start = chunkIndex * CHUNK_SIZE;
            const chunk = file.value.slice(start, Math.min(start + CHUNK_SIZE, file.value.size));

            const formData = new FormData();
            formData.append("chunk", chunk);
            formData.append("chunkIndex", chunkIndex);
            formData.append("totalChunks", totalChunks);
            formData.append("fileId", fileId);
            formData.append("fileName", file.value.name);

            const { data } = await Axios.post(props.uploadUrl, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            progress.value = Math.round(((chunkIndex + 1) / totalChunks) * 100);

            // Read the flag from either level, and treat the last chunk as
            // done regardless — the file is stored by then, so the list must
            // refresh even if the flag ever moves again.
            const done = data?.done ?? data?.result?.done ?? (chunkIndex === totalChunks - 1);

            if (done) {
                toast.success(data?.message || "Price document uploaded successfully");
                emitter.emit("reload-" + props.tableId, true);
                closeModal();
                return;
            }
        }
    } catch ({ response }) {
        if (response?.status === 422) {
            errors.value = response?.data?.errors ?? {};
        } else if (response?.status === 413) {
            toast.error("File is too large.");
        } else {
            toast.error(response?.data?.message || "Failed to upload the file");
        }
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <app-modal :modal-id="modalId" :title="$t('price_document')" save-button-label="Upload" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <label class="form-label">{{ $t('price_document') }} (.pdf, .xls, .xlsx, .csv, .png, .jpg)</label>
            <input type="file" class="form-control" accept=".pdf,.xls,.xlsx,.csv,.png,.jpg,.jpeg"
                :disabled="preloader" @change="onFileChange" />

            <div v-if="errors.chunk" class="text-danger fs-6 mt-2">
                {{ errors.chunk[0] }}
            </div>

            <div v-if="preloader || progress > 0" class="mt-3">
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" role="progressbar" :style="{ width: `${progress}%` }"
                        :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="text-muted fs-6 mt-1">{{ progress }}%</div>
            </div>

            <p class="text-muted fs-6 mt-2">
                Maximum 100 MB. Uploading replaces the current price document.
            </p>

            <div v-if="contact?.price_document_url" class="mt-2">
                <a :href="contact.price_document_url" target="_blank" rel="noopener noreferrer">
                    {{ $t('view') }} current document
                </a>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

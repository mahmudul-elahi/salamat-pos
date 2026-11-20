<script setup>
import { ref, watch } from "vue";
import { Camera, Xmark } from "@iconoir/vue";

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    modelValue: { default: null },
    showInput: { type: Boolean, default: true },
    label: { type: String, default: null },
    labelRequired: { type: Boolean, default: false },
    error: { type: String, default: null },
    preview: { type: String, default: null },
    recommendedSize: { type: String, default: null },
    recommendedFileSize: { type: String, default: null },
    accept: { type: String, default: 'image/*' },
});

const emit = defineEmits(['update:modelValue', 'changeFile', 'getFile']);

const imageUrl = ref(null);
const isDragging = ref(false);
const fileInput = ref(null);

// ✅ watch modelValue — string URL or File object
watch(() => props.modelValue, (val) => {
    if (!val) {
        imageUrl.value = null;
        return;
    }
    if (typeof val === 'string') {
        imageUrl.value = val;
    } else if (val instanceof File) {
        imageUrl.value = URL.createObjectURL(val);
    }
}, { immediate: true });

// ✅ watch preview prop — media modal theke URL asle
watch(() => props.preview, (val) => {
    if (val && typeof val === 'string') {
        imageUrl.value = val;
    }
}, { immediate: true });

const getFile = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    imageUrl.value = URL.createObjectURL(file);
    emit('update:modelValue', file);
    emit('changeFile', file);
    emit('getFile', file);
};

const clearImage = () => {
    imageUrl.value = null;
    emit('update:modelValue', null);
    emit('changeFile', null);
    if (fileInput.value) fileInput.value.value = '';
};

// ✅ Drag & Drop
const onDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};
const onDragLeave = () => {
    isDragging.value = false;
};
const onDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    imageUrl.value = URL.createObjectURL(file);
    emit('update:modelValue', file);
    emit('changeFile', file);
    emit('getFile', file);
};

const triggerFileInput = () => {
    if (fileInput.value) fileInput.value.click();
};
</script>

<template>
    <div class="image-uploader-field">

        <!-- Label -->
        <label v-if="label" class="form-label">
            {{ label }}
            <span v-if="labelRequired" class="text-danger ms-1">*</span>
        </label>

        <!-- Upload Area -->
        <div class="image-uploader-wrapper"
            :class="{ 'is-dragging': isDragging, 'has-error': error, 'has-image': imageUrl }" @dragover="onDragOver"
            @dragleave="onDragLeave" @drop="onDrop">
            <!-- Preview -->
            <div v-if="imageUrl" class="image-preview">
                <img :src="imageUrl" alt="preview" />

                <!-- Clear Button -->
                <button type="button" class="clear-btn" @click.stop="clearImage">
                    <Xmark />
                </button>
            </div>

            <!-- Placeholder -->
            <div v-else class="upload-placeholder" @click="triggerFileInput">
                <Camera class="upload-icon" />
                <p class="upload-text">Click or drag image here</p>
                <p v-if="recommendedSize" class="upload-hint">{{ recommendedSize }}</p>
                <p v-if="recommendedFileSize" class="upload-hint">{{ recommendedFileSize }}</p>
            </div>

            <!-- File Input -->
            <input v-if="showInput" ref="fileInput" type="file" :accept="accept" class="d-none" @change="getFile" />
        </div>

        <!-- Error -->
        <div v-if="error" class="invalid-feedback d-block mt-1">
            {{ Array.isArray(error) ? error[0] : error }}
        </div>

    </div>
</template>

<style scoped lang="scss">
.image-uploader-field {
    width: 100%;
}

.image-uploader-wrapper {
    position: relative;
    border: 1.5px dashed #dee2e6;
    border-radius: 10px;
    overflow: hidden;
    min-height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.2s, background 0.2s;
    background: #f8f9fa;
    cursor: pointer;

    &.is-dragging {
        border-color: #0d6efd;
        background: #e8f0fe;
    }

    &.has-error {
        border-color: #dc3545;
    }

    &.has-image {
        border-style: solid;
        border-color: #dee2e6;
        background: #fff;
    }
}

.image-preview {
    width: 100%;
    height: 100%;
    position: relative;

    img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
    }

    .clear-btn {
        position: absolute;
        top: 6px;
        right: 6px;
        background: rgba(0, 0, 0, 0.55);
        border: none;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        cursor: pointer;
        padding: 0;
        transition: background 0.2s;

        &:hover {
            background: rgba(220, 53, 69, 0.85);
        }
    }
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    width: 100%;
    gap: 6px;

    .upload-icon {
        width: 36px;
        height: 36px;
        color: #adb5bd;
    }

    .upload-text {
        font-size: 14px;
        color: #6c757d;
        margin: 0;
    }

    .upload-hint {
        font-size: 12px;
        color: #adb5bd;
        margin: 0;
    }
}
</style>
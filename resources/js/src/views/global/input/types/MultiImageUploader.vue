<script setup>
import { ref, watch } from 'vue';
import { getFileType, getFileSize, getModifiedName } from '@/utils/fileHelpers';
import { CloudUpload, Plus } from "@iconoir/vue";

const props = defineProps({
    maxLength: { type: Number, default: 10 },
    showInput: {
        type: Boolean,
        default: true,
    },
    afterUploadSuccess: { type: Boolean, default: false },
    initialFiles: { type: Array, default: () => [] }
});

const emit = defineEmits(['getFiles', 'click']);

const isDragging = ref(false);
const files = ref(flattenFiles(props.initialFiles));
const file = ref(null);

function flattenFiles(filesArray) {
    const flattened = [];
    filesArray.forEach(f => {
        if (Array.isArray(f)) {
            flattened.push(...flattenFiles(f));
        } else {
            flattened.push(f);
        }
    });
    return flattened;
}

watch(() => props.initialFiles, (val) => {
    files.value = flattenFiles(val);
});

watch(() => props.afterUploadSuccess, (val) => {
    if (val) files.value = [];
});

function onChange() {
    files.value = [...files.value, ...file.value.files];
    emit('getFiles', files.value);
}

function generateThumbnail(file) {
    if (typeof file === 'string') {
        // If initial file is string (URL), just return as is
        return file;
    }
    let url = URL.createObjectURL(file);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    return url;
}

function remove(e, i) {
    e.stopPropagation();
    e.preventDefault();
    files.value.splice(i, 1);
    emit('getFiles', files.value);
}

function dragover(e) {
    e.preventDefault();
    isDragging.value = true;
}

function dragleave() {
    isDragging.value = false;
}

function drop(e) {
    e.preventDefault();
    file.value.files = e.dataTransfer.files;
    onChange();
    isDragging.value = false;
}
function triggerFileInput() {
    if (props.showInput && file.value) {
        file.value.click();
    }

}
const getThumbIcon = (file) => {
    const fileType = getFileType(file);

    switch (fileType) {
        case 'text': return 'file-text';
        case 'pdf': return 'file-text text-danger';
        case 'doc': return 'file-text text-primary';
        case 'spreadsheet': return 'file-text text-info';
        case 'zip': return 'file text-success';
        case 'ppt': return 'file-text text-warning';
        case 'other': return 'file text-secondary';
        default: return 'file text-secondary';
    }
}

const getUrlExtension = (url) => {
    if (typeof url !== 'string') return '';
    return url.split('?')[0].split('#')[0].split('.').pop().toLowerCase();
};

const isImageFile = (file) => {
    if (getFileType(file) === 'image') return true;
    const ext = getUrlExtension(file);
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff'].includes(ext);
};

const isVideoFile = (file) => {
    if (getFileType(file) === 'video') return true;
    const ext = getUrlExtension(file);
    return ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'm4v'].includes(ext);
};
</script>

<template>
    <div class="dropzone multi-image-uploader border border-1" @dragover="dragover" @dragleave="dragleave" @drop="drop"
        @click="showInput ? triggerFileInput() : $emit('click')">
        <input type="file" multiple name="file" v-if="showInput" id="fileInput" class="d-none" @change="onChange"
            ref="file" />
        <div class="py-3 px-4 d-flex flex-wrap justify-content-start align-items-center gap-3" v-if="files.length">
            <div v-for="(file, index) in files" :key="file.name || file"
                class="mt-2 h-100 images-container p-1 rounded-3 position-relative">
                <div class="previews d-flex flex-column justify-content-between align-items-center">
                    <div class="preview-img">
                        <img v-if="isImageFile(file)"
                            :src="generateThumbnail(file)" alt="image" />
                        <video v-else-if="isVideoFile(file)"
                            :src="generateThumbnail(file)" preload="metadata"
                            style="height:50px; width:auto;" />
                        <span v-else>
                            <i class="bi display-5" :class="getThumbIcon(file)"></i>
                        </span>
                    </div>
                    <div>
                        {{ typeof file !== 'string' && file?.name ? getModifiedName(file.name) : '' }}
                    </div>
                    <small>
                        {{ typeof file !== 'string' ? getFileSize(file.size) : '' }}
                    </small>
                </div>
                <button class="clear-file p-0 px-1 border-0 position-absolute fs-4 fw-normal lh-1 rounded-circle"
                    type="button" @click.stop.prevent="remove($event, index)" title="Remove file">
                    <span>&times;</span>
                </button>
            </div>
            <label v-if="files.length < maxLength" @click="showInput ? triggerFileInput() : $emit('click')"
                class="border-2 px-2 rounded upload-icon cursor-pointer add-more-btn"
                :style="isDragging ? 'background-color: #83e1ff29' : ''">
                <Plus />
            </label>
        </div>
        <label v-else @click="$emit('click')" class="py-3 px-4 cursor-pointer w-100 h-100">
            <div class="d-flex justify-content-start align-items-center gap-3">
                <CloudUpload class="fs-1 upload-icon" />
                <div>
                    <p class="m-0 fw-semibold">
                        <span v-if="isDragging">{{ $t('release_to_upload') }}</span>
                        <span v-else>{{ $t('drop_files_here_or_click_to_upload') }}</span>
                    </p>
                    <p class="m-0 instruction-text">
                        {{ $t('upload_files') }}
                    </p>
                </div>
            </div>
        </label>
    </div>
</template>

<style scoped lang="scss">
.multi-image-uploader {
    background-color: var(--dropzone-bg-color);

    &.dropzone {
        --bs-border-color: var(--primary);
        --bs-border-style: dashed;
        width: 100%;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        border-radius: 8px;

        .upload-icon {
            color: var(--primary);
        }

        .instruction-text {
            color: var(--primary);
        }

        .preview-img {
            img {
                height: 50px;
            }
        }

        .clear-file {
            top: -0.5rem;
            right: -0.5rem;
            color: var(--bs-gray-600);
            background-color: var(--card-color);
        }

        .add-more-btn {
            &:hover {
                background-color: #83e1ff29;
            }
        }
    }
}
</style>

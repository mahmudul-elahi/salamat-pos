<script setup>
import { ref, onBeforeUnmount } from "vue";
import { Camera, VideoCameraOff, RefreshDouble } from "@iconoir/vue";

const props = defineProps({
    modelValue: { type: [Blob, null], default: null },
});

const emit = defineEmits(["update:modelValue"]);

const videoEl        = ref(null);
const stream         = ref(null);
const previewUrl     = ref(null);
const cameraError    = ref(null);
const starting       = ref(false);

const stopStream = () => {
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
};

const startCamera = async () => {
    cameraError.value = null;
    starting.value = true;

    try {
        if (!window.isSecureContext) {
            throw new Error("insecure_context");
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error("unsupported");
        }

        stream.value = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } });
        if (videoEl.value) {
            videoEl.value.srcObject = stream.value;
        }
    } catch (err) {
        cameraError.value = err?.message === "insecure_context"
            ? "Camera requires a secure (HTTPS) connection"
            : err?.name === "NotAllowedError"
                ? "Camera permission denied"
                : err?.name === "NotFoundError"
                    ? "No camera found"
                    : err?.name === "NotReadableError"
                        ? "Camera is in use by another app"
                        : "Camera unavailable";
    } finally {
        starting.value = false;
    }
};

const capture = () => {
    if (!videoEl.value) return;

    const canvas = document.createElement("canvas");
    canvas.width  = videoEl.value.videoWidth;
    canvas.height = videoEl.value.videoHeight;
    canvas.getContext("2d").drawImage(videoEl.value, 0, 0, canvas.width, canvas.height);

    canvas.toBlob((blob) => {
        if (!blob) return;
        previewUrl.value = URL.createObjectURL(blob);
        emit("update:modelValue", blob);
        stopStream();
    }, "image/jpeg", 0.85);
};

const retake = () => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
    emit("update:modelValue", null);
    startCamera();
};

onBeforeUnmount(() => {
    stopStream();
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

startCamera();
</script>

<template>
    <div class="selfie-capture mb-4">
        <p class="text-muted mb-2">{{ $t('selfie_hint') }}</p>

        <div class="selfie-capture__frame">
            <img v-if="previewUrl" :src="previewUrl" alt="Selfie preview" class="selfie-capture__media" />
            <video v-else-if="!cameraError" ref="videoEl" autoplay muted playsinline
                class="selfie-capture__media selfie-capture__media--mirrored" />
            <div v-else class="selfie-capture__error">
                <VideoCameraOff width="28" height="28" />
                <span>{{ cameraError }}</span>
            </div>
        </div>

        <div v-if="!cameraError" class="mt-2">
            <button v-if="!previewUrl" type="button" class="btn btn-sm btn-outline-primary"
                :disabled="starting" @click="capture">
                <Camera width="16" height="16" class="me-1" /> {{ $t('take_selfie') }}
            </button>
            <button v-else type="button" class="btn btn-sm btn-outline-secondary" @click="retake">
                <RefreshDouble width="16" height="16" class="me-1" /> {{ $t('retake_selfie') }}
            </button>
        </div>
    </div>
</template>

<style scoped lang="scss">
.selfie-capture {
    &__frame {
        width: 200px;
        height: 150px;
        border-radius: 10px;
        overflow: hidden;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color);
    }

    &__media {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    &__media--mirrored {
        transform: scaleX(-1);
    }

    &__error {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        color: #9ca3af;
        font-size: 12px;
        text-align: center;
        padding: 8px;
    }
}
</style>

<script>
import { ref } from "vue";
import { urlGenerator } from "@/utils/urlGenerator.js";
import { Camera } from "@iconoir/vue";

export default {
    name: "ImageUploader",
    components: {
        Camera,
    },
    props: {
        data: {},
        value: {},
        showInput: {
            type: Boolean,
            default: true,
        },
    },
    watch: {
        value: {
            handler: "initComponent",
            immediate: true,
        },
    },
    methods: {
        urlGenerator,
        initComponent() {
            if (typeof this.value === "string" && this.value) {
                this.imageUrl = this.value;
                this.fieldValue = null;
            } else if (this.value instanceof File) {
                this.fieldValue = this.value;
                this.imageUrl = URL.createObjectURL(this.value);
            } else {
                this.imageUrl = "";
                this.fieldValue = null;
            }
        },
    },
    setup(props, { emit }) {
        const uploadProgress = ref(0);
        const imageUrl = ref(null);
        const fieldValue = ref(null);

        const getFile = (event) => {
            const file = event.target.files[0];
            if (!file) return;

            if (imageUrl.value && imageUrl.value.startsWith("blob:")) {
                URL.revokeObjectURL(imageUrl.value);
            }

            fieldValue.value = file;
            imageUrl.value = URL.createObjectURL(file);
            emit("getFile", file);

            event.target.value = "";
        };

        return { imageUrl, fieldValue, getFile, uploadProgress };
    },
};
</script>

<template>
    <div class="image-uploader-wrapper position-relative" :class="{
        'is-uploading': uploadProgress > 0 && uploadProgress < 100,
    }">
        <div class="uploading flex-column justify-content-center align-items-center overflow-hidden">
            <span class="h-100 upload-percentage position-absolute top-0 left-0" :style="{
                width: `${uploadProgress}%`,
            }" />
            <span class="position-absolute top-50 left-50 translate-middle fw-semibold text-white">
                {{ uploadProgress }}%
            </span>
        </div>
        <div class="image-area d-flex position-relative">
            <img :src="imageUrl || urlGenerator('assets/images/placeholder/image-placeholder.png')" class="img-fluid"
                alt="image" />
            <div class="input-area" v-if="showInput">
                <label id="upload-label" @click="$refs.file.click()">
                    <Camera />
                </label>
                <input ref="file" type="file" accept="image/*" class="form-control d-none" @change="getFile" />
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>
<script setup>

// Destructure the `error` and `validateFileSize` from the `useFile` composable
import ImageUploader from "@/views/global/input/types/ImageUploader.vue";
import {useFile} from "@/composable/file/useFile.js";
import Label from "@/views/global/label/Label.vue";
const { error: localError, validateFileSize } = useFile();

// Define props for the component
const props = defineProps({
    label: String, // Optional label text
    modelValue: {
        require: true, // Required value for the model
    },
    error: {
        type: String, // Optional error message
    },
    labelRequired: Boolean, // Optional boolean to indicate if the label is required
    fileSize: {
        type: Number,
        default: () => 2, // Default file size limit (in MB)
    },
    recommendedSize: {
        type: String, // Optional string for recommended file size
    },
    recommendedFileSize: {
        type: String, // Optional string for recommended file size
    },
});

// Define emits for the component, declaring the event it can emit
const emit = defineEmits(["changeFile", "click"]);

// Method to handle file change
const getFile = (value) => {
    validateFileSize(value, props.fileSize); // Validate file size
    emit("changeFile", value); // Emit the file change event
};
</script>

<template>
    <div @click="$emit('click')">
        <Label :label="label" :label-required="labelRequired" />

        <div class="d-flex align-items-center flex-wrap gap-3">
            <ImageUploader
                :data="$props"
                :value="modelValue"
                @getFile="getFile"
                v-bind="$attrs"
            />
            <slot>
                <p class="mt-4 px-2">
                    {{ recommendedSize }}
                    <br />
                    {{ recommendedFileSize }}
                </p>
            </slot>
        </div>
        <div class="text-danger validation-error mt-2">
            {{ localError || error }}
        </div>
    </div>
</template>


<style scoped lang="scss">

</style>

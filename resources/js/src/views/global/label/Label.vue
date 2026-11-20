<script setup>
import { InfoCircle } from '@iconoir/vue';

const props = defineProps({
    label: String,
    labelClass: {
        type: String,
        default: "mb-2",
    },
    labelRequired: {
        type: Boolean,
        default: false,
    },
    labelHint: {
        type: String,
    },
});
</script>

<template>
    <label v-if="label" :class="labelClass">
        {{ label }} <span v-if="labelRequired" class="text-danger">*</span>
        <span
            v-if="labelHint"
            class="ms-1 cursor-pointer"
            data-bs-toggle="tooltip"
            data-bs-html="true"
            data-bs-placement="top"
            :title="labelHint">
            <InfoCircle />
        </span>

    </label>
</template>


<style scoped lang="scss"></style>
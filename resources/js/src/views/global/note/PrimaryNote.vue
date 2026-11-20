<script setup>
import { MessageAlert } from '@iconoir/vue';

// define props
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="primary-note rounded-default d-flex">
        <span class="text-danger lh-1">
            <MessageAlert />
        </span>

        <div>
            <div class="fw-semibold lh-1">{{ title }}</div>
            <slot>
                <div class="mt-3">{{ description }}</div>
            </slot>
        </div>
    </div>
</template>



<style scoped lang="scss">

</style>

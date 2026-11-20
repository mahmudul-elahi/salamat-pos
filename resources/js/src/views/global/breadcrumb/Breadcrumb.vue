<script setup>
// Define props
const props = defineProps({
    pageTitle: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <!-- Display the page title -->
        <h4 class="mb-0 breadcrumb-custom">{{ pageTitle }}</h4>
        <!-- Slot for additional breadcrumb actions -->
        <slot name="breadcrumb-actions"></slot>
    </div>
</template>

<style scoped lang="scss">

</style>

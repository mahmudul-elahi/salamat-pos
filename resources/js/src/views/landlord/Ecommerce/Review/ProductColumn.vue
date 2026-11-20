<script setup>
import Avatar from "@/views/global/avatar/Avatar.vue";

defineProps({
    value: Object,
});
</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="value?.product_image" :name="value?.product_name" />
        <div class="mx-2 d-flex flex-column align-items-start">
            <div class="fw-semibold">{{ value?.product_name ?? '-' }}</div>
        </div>
    </div>
</template>

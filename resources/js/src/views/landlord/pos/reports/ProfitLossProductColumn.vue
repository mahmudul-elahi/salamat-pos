<script setup>
import Avatar from "@/views/global/avatar/Avatar.vue";

defineProps({
    value: Object,
});
</script>

<style scoped>
.variant-pill {
    display: inline-flex;
    align-items: center;
    padding: 2px 10px;
    border-radius: 999px;
    background: #eef0f4;
    color: #475569;
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
}
</style>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="value?.image" :name="value?.name" />
        <div class="mx-2 d-flex flex-column align-items-start">
            <div class="fw-semibold">{{ value?.name ?? '-' }}</div>
            <div class="d-flex flex-wrap gap-1 mt-1" v-if="value?.variant?.length">
                <span v-for="(attr, idx) in value.variant" :key="idx" class="variant-pill">{{ attr }}</span>
            </div>
        </div>
    </div>
</template>

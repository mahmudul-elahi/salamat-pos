<script setup>
import Avatar from "@/views/global/avatar/Avatar.vue";

defineProps({
    rowData: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="rowData?.thumbnail" :name="rowData?.ad_title" />
        <div class="mx-2 d-flex flex-column align-items-start">
            <div class="fw-semibold">{{ rowData?.ad_title }}</div>
            <div class="small text-muted" v-if="rowData?.brand || rowData?.model">
                {{ [rowData?.brand, rowData?.model].filter(Boolean).join(' — ') }}
            </div>
        </div>
    </div>
</template>

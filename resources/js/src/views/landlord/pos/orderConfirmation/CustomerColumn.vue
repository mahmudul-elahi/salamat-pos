<script setup>
import Avatar from "@/views/global/avatar/Avatar.vue";

// Name and mobile share one column: the mobile is only ever read together with
// the name, and giving it its own column just widened an already wide table.
defineProps({
    tableId: [String],
    rowData: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :name="rowData.customer_name" active="success" />
        <div class="mx-2 d-flex flex-column align-items-start">
            <div class="fw-semibold text-nowrap">{{ rowData.customer_name || '-' }}</div>
            <div class="text-muted fs-6 text-nowrap">{{ rowData.mobile || '-' }}</div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>

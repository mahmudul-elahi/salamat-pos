<script setup>
defineProps({
    rowData: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div v-if="rowData.payment_method" class="d-flex align-items-center">
        <img v-if="rowData.payment_method_logo"
             :src="rowData.payment_method_logo"
             class="payment-method-logo me-1"
             alt="">
        <span>{{ rowData.payment_method }}</span>
    </div>
    <span v-else class="text-muted">—</span>
</template>

<style scoped lang="scss">
.payment-method-logo {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    object-fit: contain;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    flex-shrink: 0;
}
</style>

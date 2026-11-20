<script setup>
import { computed } from 'vue'

const props = defineProps({
    rowData: {
        type: Object,
        required: true,
    }
})

// Prepare the purchase return array
const purchaseReturns = computed(() => {
    if (props.rowData.products && props.rowData.products.length > 0) {
        // Multiple variants
        return props.rowData.products.map(p => p.purchase_return)
    } else {
        // Single variant
        return [props.rowData.purchase_return]
    }
})
</script>

<template>
    <div class="opening-stock-column">
        <div v-for="(purchase_return, idx) in purchaseReturns" :key="idx">{{ purchase_return }}</div>
    </div>
</template>

<style scoped lang="scss">

</style>

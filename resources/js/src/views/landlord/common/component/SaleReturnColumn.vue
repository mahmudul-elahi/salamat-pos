<script setup>
import { computed } from 'vue'

const props = defineProps({
    rowData: {
        type: Object,
        required: true,
    }
})

// Prepare the sale return array
const saleReturns = computed(() => {
    if (props.rowData.products && props.rowData.products.length > 0) {
        // Multiple variants
        return props.rowData.products.map(p => p.sale_return)
    } else {
        // Single variant
        return [props.rowData.sale_return]
    }
})
</script>

<template>
    <div class="opening-stock-column">
        <div v-for="(sale_return, idx) in saleReturns" :key="idx">{{ sale_return }}</div>
    </div>
</template>

<style scoped lang="scss">

</style>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    rowData: {
        type: Object,
        required: true,
    }
})

// Prepare the opening stock array
const closingStocks = computed(() => {
    if (props.rowData.products && props.rowData.products.length > 0) {
        // Multiple variants
        return props.rowData.products.map(p => p.closing_stock)
    } else {
        // Single variant
        return [props.rowData.closing_stock]
    }
})
</script>

<template>
    <div class="opening-stock-column">
        <div v-for="(stock, idx) in closingStocks" :key="idx">{{ stock }}</div>
    </div>
</template>

<style scoped lang="scss">

</style>

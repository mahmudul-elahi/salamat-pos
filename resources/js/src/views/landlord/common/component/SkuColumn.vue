<script setup>
import { computed } from 'vue'
const props = defineProps({
    rowData: {
        type: Object,
        required: true,
    }
})

// Prepare the SKUs array
const skus = computed(() => {
    if (props.rowData.products && props.rowData.products.length > 0) {
        // Multiple variants
        return props.rowData.products.map(p => p.sku)
    } else {
        // Single variant
        return [props.rowData.sku]
    }
})
</script>

<template>
    <div class="sku-column">
        <div v-for="(sku, idx) in skus" :key="idx">{{ sku }}</div>
    </div>
</template>

<style scoped lang="scss">

</style>

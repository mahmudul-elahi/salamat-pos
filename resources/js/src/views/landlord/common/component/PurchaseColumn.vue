<script setup>
import { computed } from 'vue'
const props = defineProps({
    rowData: {
        type: Object,
        required: true,
    }
})

// Prepare the Purchases array
const purchases = computed(() => {
    if (props.rowData.products && props.rowData.products.length > 0) {
        // Multiple variants
        return props.rowData.products.map(p => p.purchase)
    } else {
        // Single variant
        return [props.rowData.purchase]
    }
})
</script>

<template>
    <div class="purchase-column">
        <div v-for="(purchase, idx) in purchases" :key="idx">{{ purchase }}</div>
    </div>
</template>

<style scoped lang="scss">

</style>

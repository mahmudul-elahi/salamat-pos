<script setup>
import { computed } from 'vue'
const props = defineProps({
    rowData: {
        type: Object,
        required: true,
    }
})

// Prepare the Sales array
const sales = computed(() => {
    if (props.rowData.products && props.rowData.products.length > 0) {
        // Multiple variants
        return props.rowData.products.map(p => p.sale)
    } else {
        // Single variant
        return [props.rowData.sale]
    }
})
</script>

<template>
    <div class="sale-column">
        <div v-for="(sale, idx) in sales" :key="idx">{{ sale }}</div>
    </div>
</template>

<style scoped lang="scss">

</style>

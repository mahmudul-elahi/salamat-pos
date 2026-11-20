<script setup>
import { computed } from 'vue'

const props = defineProps({
    rowData: {
        type: Object,
        required: true,
    }
})

const attributeGroups = computed(() => {
    if (props.rowData.attribute_values.length === 0) {
        return []
    }

    return props.rowData.attribute_values.map(product => ({
        attribute_value: product.attribute_values || '-',
        row_span: product.serial_numbers ? product.serial_numbers.length : 1
    }))
})

</script>

<template>
    <div class="attribute-column">
        <div
            v-for="(group, index) in attributeGroups"
            :key="index"
            class="attribute-group"
            :style="{ 'min-height': `${group.row_span * 40}px` }"
        >
            <span class="badge bg-success badge-square-light attribute-badge">
                {{ group.attribute_value }}
            </span>
        </div>
    </div>
</template>

<style scoped lang="scss">
.attribute-column {
    display: flex;
    flex-direction: column;
    height: 100%;

    .attribute-group {
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid var(--border-color);
        padding: 0;
        min-height: 40px;

        &:last-child {
            border-bottom: none;
        }
    }

    .attribute-badge {
        padding: 6px 12px;
        font-size: 0.875rem;
    }
}
</style>

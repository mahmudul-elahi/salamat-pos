<script setup>
import { computed } from 'vue'

const props = defineProps({
  rowData: {
    type: Object,
    required: true,
  }
})

const qtyGroups = computed(() => {
  if (!props.rowData.products || props.rowData.products.length === 0) {
    return []
  }

  return props.rowData.products.map(product => ({
    quantity: product.qty || '0',
    row_span: product.serial_numbers ? product.serial_numbers.length : 1
  }))
})
</script>

<template>
  <div class="qty-column">
<!--    <div-->
<!--      v-for="(group, index) in qtyGroups"-->
<!--      :key="index"-->
<!--      class="qty-group"-->
<!--      :style="{ 'min-height': `${group.row_span * 40}px` }"-->
<!--    >-->
      <span class="quantity-value">
        {{ rowData.in_stock }}
      </span>

<!--      <span class="quantity-value">-->
<!--        Total ={{ rowData.product_total_stock }}-->
<!--      </span>-->
    </div>
<!--  </div>-->
</template>

<style scoped lang="scss">
.qty-column {
  display: flex;
  flex-direction: column;
  height: 100%;

  .qty-group {
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

  .quantity-value {
    font-weight: 600;
    font-size: 0.875rem;
    padding: 6px 12px;
  }
}
</style>

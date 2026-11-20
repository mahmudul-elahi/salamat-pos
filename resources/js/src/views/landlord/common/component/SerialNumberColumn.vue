<script setup>
import { computed } from 'vue'

const props = defineProps({
  rowData: {
    type: Object,
    required: true,
  },
})

const serialGroups = computed(() => {
  return props.rowData.serial_numbers || []
})
</script>

<template>
  <div class="serial-column">
    <div
      v-for="(variant, vIndex) in serialGroups"
      :key="vIndex"
      class="variant-group"
    >
        <p class="ms-3 serial-value">{{ variant}}</p>
<!--      <div-->
<!--        v-for="(serial, sIndex) in variant.serial_numbers"-->
<!--        :key="sIndex"-->
<!--        class="serial-line"-->
<!--      >-->
<!--        {{ serial }}-->
<!--      </div>-->
    </div>
  </div>
</template>

<style scoped lang="scss">
.serial-value {
  white-space: nowrap;
}

//.serial-column {
//  display: flex;
//  flex-direction: column;
//  height: 100%;
//
//  .variant-group {
//    display: flex;
//    flex-direction: column;
//    border-bottom: 1px solid var(--border-color);
//  }
//
//  .variant-group:last-child {
//    border-bottom: none;
//  }
//
//  .serial-line {
//    padding: 8px 12px;
//    border-bottom: 1px solid #eee;
//    display: flex;
//    align-items: center;
//    justify-content: center;
//    text-align: center;
//    min-height: 40px;
//  }
//
//  .serial-line:last-child {
//    border-bottom: none;
//  }
//}
</style>

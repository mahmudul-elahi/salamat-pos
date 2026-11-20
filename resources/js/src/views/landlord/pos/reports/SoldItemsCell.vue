<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Eye } from '@iconoir/vue';
import SaleItemsModal from '@/views/landlord/pos/reports/SaleItemsModal.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    value: [String, Number],
    rowData: Object,
});

const { t } = useI18n();
const isModalActive = ref(false);
</script>

<template>
    <div class="d-flex align-items-center gap-2">
        <span>{{ value }}</span>
        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1"
            :title="t('view_sold_items')" @click="isModalActive = true">
            <Eye width="14" height="14" />
        </button>
    </div>
    <sale-items-modal v-if="isModalActive" :modal-id="`sale-items-modal-${rowData.id}`"
        :sale-id="rowData.id" @close="isModalActive = false" />
</template>

<style scoped lang="scss"></style>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Eye } from '@iconoir/vue';
import SaleItemsModal from '@/views/landlord/pos/reports/SaleItemsModal.vue';
import PurchaseItemsModal from '@/views/landlord/pos/reports/PurchaseItemsModal.vue';
import SaleReturnItemsModal from '@/views/landlord/pos/reports/SaleReturnItemsModal.vue';
import PurchaseReturnItemsModal from '@/views/landlord/pos/reports/PurchaseReturnItemsModal.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    value: [String, Number],
    rowData: Object,
});

const { t } = useI18n();
const isModalActive = ref(false);

const modalTitle = computed(() => {
    switch (props.rowData?.model_type) {
        case 'sale': return t('view_sold_items');
        case 'purchase': return t('view_purchased_items');
        case 'sale_return': return t('view_returned_items');
        case 'purchase_return': return t('view_returned_items');
        default: return '';
    }
});
</script>

<template>
    <div class="d-flex align-items-center gap-2">
        <span>{{ value }}</span>
        <button v-if="rowData?.model_type" type="button" class="btn btn-sm btn-outline-primary py-0 px-1"
            :title="modalTitle" @click="isModalActive = true">
            <Eye width="14" height="14" />
        </button>
    </div>

    <sale-items-modal v-if="isModalActive && rowData?.model_type === 'sale'"
        :modal-id="`daily-closing-sale-items-modal-${rowData.id}`" :sale-id="rowData.id"
        @close="isModalActive = false" />

    <purchase-items-modal v-if="isModalActive && rowData?.model_type === 'purchase'"
        :modal-id="`daily-closing-purchase-items-modal-${rowData.id}`" :purchase-id="rowData.id"
        @close="isModalActive = false" />

    <sale-return-items-modal v-if="isModalActive && rowData?.model_type === 'sale_return'"
        :modal-id="`daily-closing-sale-return-items-modal-${rowData.id}`" :sale-return-id="rowData.id"
        @close="isModalActive = false" />

    <purchase-return-items-modal v-if="isModalActive && rowData?.model_type === 'purchase_return'"
        :modal-id="`daily-closing-purchase-return-items-modal-${rowData.id}`" :purchase-return-id="rowData.id"
        @close="isModalActive = false" />
</template>

<style scoped lang="scss"></style>

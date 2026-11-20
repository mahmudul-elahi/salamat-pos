<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Eye } from '@iconoir/vue';
import SaleReturnItemsModal from '@/views/landlord/pos/reports/SaleReturnItemsModal.vue';

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
            :title="t('view_returned_items')" @click="isModalActive = true">
            <Eye width="14" height="14" />
        </button>
    </div>
    <sale-return-items-modal v-if="isModalActive" :modal-id="`sale-return-items-modal-${rowData.id}`"
        :sale-return-id="rowData.id" @close="isModalActive = false" />
</template>

<style scoped lang="scss"></style>

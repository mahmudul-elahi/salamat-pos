<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    modalId: String,
});

const emit = defineEmits(['close', 'generate']);

const { t } = useI18n();

const toggleableColumns = [
    { key: 'brand', label: t('brand') },
    { key: 'category', label: t('category') },
    { key: 'purchase_date', label: t('purchase_date') },
    { key: 'supplier_name', label: t('supplier') },
    { key: 'full_invoice_number', label: t('invoice_number') },
    { key: 'warehouse_name', label: t('warehouse') },
    { key: 'serial_numbers', label: t('serial_number') },
];

const selected = ref(toggleableColumns.map(c => c.key));

const submit = () => {
    emit('generate', selected.value);
    emit('close');
};
</script>

<template>
    <app-modal :modal-id="modalId" title="Select PDF Columns" save-button-label="Generate PDF"
        @submit="submit" @close="$emit('close')">
        <template #body>
            <p class="text-muted mb-3">SL, Product Name, Quantity &amp; In Stock are always included.</p>
            <div class="row g-2">
                <div class="col-md-6" v-for="col in toggleableColumns" :key="col.key">
                    <div class="form-check column-check">
                        <input type="checkbox" class="form-check-input" :id="`stock-statement-pdf-col-${col.key}`"
                            :value="col.key" v-model="selected" />
                        <label class="form-check-label" :for="`stock-statement-pdf-col-${col.key}`">{{ col.label }}</label>
                    </div>
                </div>
            </div>
        </template>
    </app-modal>
</template>

<style scoped>
.column-check .form-check-label {
    margin-left: 8px;
}
</style>

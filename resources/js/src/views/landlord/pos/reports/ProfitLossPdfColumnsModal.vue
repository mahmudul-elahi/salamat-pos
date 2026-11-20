<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    modalId: String,
});

const emit = defineEmits(['close', 'generate']);

const { t } = useI18n();

const toggleableColumns = [
    { key: 'product', label: t('product') },
    { key: 'seller', label: t('seller') },
    { key: 'created_by', label: t('created_by') },
    { key: 'payment_method', label: t('payment_method') },
    { key: 'status', label: t('status') },
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
            <p class="text-muted mb-3">SL, Date, Invoice Number, Total Sale, Total Purchase &amp; Profit/Loss are always included.</p>
            <div class="row g-2">
                <div class="col-md-6" v-for="col in toggleableColumns" :key="col.key">
                    <div class="form-check column-check">
                        <input type="checkbox" class="form-check-input" :id="`profit-loss-pdf-col-${col.key}`"
                            :value="col.key" v-model="selected" />
                        <label class="form-check-label" :for="`profit-loss-pdf-col-${col.key}`">{{ col.label }}</label>
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

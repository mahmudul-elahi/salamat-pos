<script setup>
import { ref } from 'vue';

const props = defineProps({
    modalId: String,
    title: {
        type: String,
        default: 'Select PDF Columns',
    },
    alwaysIncludedText: {
        type: String,
        default: '',
    },
    columns: {
        type: Array,
        required: true, // [{ key, label }]
    },
});

const emit = defineEmits(['close', 'generate']);

const selected = ref(props.columns.map(c => c.key));

const submit = () => {
    emit('generate', selected.value);
    emit('close');
};
</script>

<template>
    <app-modal :modal-id="modalId" :title="title" save-button-label="Generate PDF"
        @submit="submit" @close="$emit('close')">
        <template #body>
            <p v-if="alwaysIncludedText" class="text-muted mb-3">{{ alwaysIncludedText }}</p>
            <div class="row g-2">
                <div class="col-md-6" v-for="col in columns" :key="col.key">
                    <div class="form-check column-check">
                        <input type="checkbox" class="form-check-input" :id="`pdf-col-${modalId}-${col.key}`"
                            :value="col.key" v-model="selected" />
                        <label class="form-check-label" :for="`pdf-col-${modalId}-${col.key}`">{{ col.label }}</label>
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

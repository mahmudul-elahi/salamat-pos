<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_EMPLOYEE_STATUSES } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

const colorOptions = [
    { id: '#6c757d', name: 'Gray',        hex: '#6c757d' },
    { id: '#0d6efd', name: 'Blue',        hex: '#0d6efd' },
    { id: '#198754', name: 'Green',       hex: '#198754' },
    { id: '#dc3545', name: 'Red',         hex: '#dc3545' },
    { id: '#ffc107', name: 'Yellow',      hex: '#ffc107' },
    { id: '#0dcaf0', name: 'Cyan',        hex: '#0dcaf0' },
    { id: '#fd7e14', name: 'Orange',      hex: '#fd7e14' },
    { id: '#6f42c1', name: 'Purple',      hex: '#6f42c1' },
    { id: '#d63384', name: 'Pink',        hex: '#d63384' },
    { id: '#20c997', name: 'Teal',        hex: '#20c997' },
    { id: '#0dcaf0', name: 'Light Blue',  hex: '#0dcaf0' },
    { id: '#343a40', name: 'Dark',        hex: '#343a40' },
    { id: '#f8f9fa', name: 'Light',       hex: '#f8f9fa' },
    { id: '#e83e8c', name: 'Magenta',     hex: '#e83e8c' },
    { id: '#17a2b8', name: 'Info',        hex: '#17a2b8' },
    { id: '#28a745', name: 'Success',     hex: '#28a745' },
    { id: '#ffc107', name: 'Warning',     hex: '#ffc107' },
    { id: '#ff6b6b', name: 'Coral',       hex: '#ff6b6b' },
    { id: '#4dabf7', name: 'Sky',         hex: '#4dabf7' },
    { id: '#845ef7', name: 'Violet',      hex: '#845ef7' },
];

formData.value = {
    name: null,
    color: '#6c757d',
    description: null,
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_EMPLOYEE_STATUSES, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_employee_status') : $t('add_employee_status')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input id="name" type="text" :label="$t('name')" label-required
                            :placeholder="$t('enter_status_name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">{{ $t('color') }} <span class="text-danger">*</span></label>
                        <div class="color-select-grid">
                            <button v-for="opt in colorOptions" :key="opt.id" type="button"
                                class="color-btn"
                                :class="{ active: formData.color === opt.id }"
                                :style="{ background: opt.hex }"
                                :title="opt.name"
                                @click="formData.color = opt.id">
                                <span v-if="formData.color === opt.id" class="color-check">✓</span>
                            </button>
                        </div>
                        <div class="selected-color-label mt-2">
                            <span class="color-swatch" :style="{ background: formData.color }"></span>
                            <span class="color-name-text">{{ colorOptions.find(c => c.id === formData.color)?.name }} — {{ formData.color }}</span>
                        </div>
                        <div v-if="$errors(errors, 'color')" class="invalid-feedback d-block">
                            {{ $errors(errors, 'color') }}
                        </div>
                    </div>

                    <div class="col-md-12">
                        <app-input id="description" type="editor" :label="$t('description')"
                            :placeholder="$t('write_description')"
                            v-model="formData.description" :errors="$errors(errors, 'description')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.color-select-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 6px;
}

.color-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 2px solid transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s, border-color 0.15s;

    &:hover {
        transform: scale(1.15);
        border-color: rgba(0, 0, 0, 0.25);
    }

    &.active {
        border-color: #000;
        transform: scale(1.15);
    }
}

.color-check {
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.5);
}

.selected-color-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: #6c757d;
}

.color-swatch {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    border: 1px solid rgba(0,0,0,0.15);
    flex-shrink: 0;
}
</style>

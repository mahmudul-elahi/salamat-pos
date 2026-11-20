<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_BENEFICIARIES } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    name: null,
    type: 'allowance',
    is_active: true,
    description: null,
};

const typeOptions = [
    { id: 'allowance', name: 'Allowance' },
    { id: 'deduction', name: 'Deduction' },
];

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_BENEFICIARIES, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_beneficiary') : $t('add_beneficiary')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input id="name" type="text" :label="$t('name')" label-required
                            :placeholder="$t('enter_beneficiary_name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>
                    <div class="col-md-12">
                        <label class="form-label mb-2 d-block">
                            {{ $t('type') }} <span class="text-danger">*</span>
                        </label>
                        <app-input type="radio" id="type" radio-checkbox-name="type"
                            list-value-field="name" v-model="formData.type"
                            :options="typeOptions" :errors="$errors(errors, 'type')" />
                    </div>
                    <div class="col-md-12">
                        <label class="form-label mb-2 d-block">{{ $t('status') }}</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active"
                                v-model="formData.is_active" />
                            <label class="form-check-label" for="is_active">
                                {{ formData.is_active ? $t('active') : $t('inactive') }}
                            </label>
                        </div>
                        <small v-if="$errors(errors, 'is_active')" class="text-danger mt-1 d-block">
                            {{ $errors(errors, 'is_active') }}
                        </small>
                    </div>
                    <div class="col-md-12">
                        <app-input id="description" type="editor" :label="$t('description')"
                            :placeholder="$t('write_beneficiary_description')"
                            v-model="formData.description" :errors="$errors(errors, 'description')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

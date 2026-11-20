<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_DEPARTMENTS, HRM_EMPLOYEES } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    name: null,
    department_head_id: null,
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_DEPARTMENTS, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_department') : $t('add_department')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input id="name" type="text" :label="$t('name')" label-required
                            :placeholder="$t('enter_department_name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>
                    <div class="col-md-12">
                        <app-input type="advance-search-select" id="department_head_id"
                            :label="$t('department_head')" list-value-field="name"
                            v-model="formData.department_head_id" :loading-length="10"
                            :fetch-url="HRM_EMPLOYEES" :choose-label="$t('choose_department_head')"
                            :errors="$errors(errors, 'department_head_id')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_HOLIDAYS, HRM_DEPARTMENTS } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    name: null,
    start_date: null,
    end_date: null,
    department_id: null,
    description: null,
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_HOLIDAYS, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_holiday') : $t('add_holiday')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">

                    <div class="col-md-12">
                        <app-input id="name" type="text" :label="$t('name')" label-required
                            :placeholder="$t('enter_holiday_name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="start_date" type="date" :label="$t('start_date')" label-required
                            :placeholder="$t('start_date')"
                            v-model="formData.start_date"
                            :enable-time-picker="false"
                            auto-apply
                            :errors="$errors(errors, 'start_date')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="end_date" type="date" :label="$t('end_date')" label-required
                            :placeholder="$t('end_date')"
                            v-model="formData.end_date"
                            :enable-time-picker="false"
                            auto-apply
                            :errors="$errors(errors, 'end_date')" />
                    </div>

                    <div class="col-md-12">
                        <app-input type="advance-search-select" id="department_id"
                            :label="$t('department')" list-value-field="name"
                            v-model="formData.department_id" :loading-length="10"
                            :fetch-url="HRM_DEPARTMENTS" :choose-label="$t('all_departments')"
                            :errors="$errors(errors, 'department_id')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="description" type="textarea" :label="$t('description')"
                            :placeholder="$t('write_description')"
                            v-model="formData.description" :errors="$errors(errors, 'description')" />
                    </div>

                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_ATTENDANCES, HRM_WORK_SHIFTS, HRM_EMPLOYEES } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    user_id: null,
    work_shift_id: null,
    in_date: null,
    behavior: 'regular',
    status: 'approved',
};

const behaviorOptions = [
    { id: 'early', name: 'Early' },
    { id: 'late', name: 'Late' },
    { id: 'regular', name: 'Regular' },
];

const statusOptions = [
    { id: 'approved', name: 'Approved' },
    { id: 'rejected', name: 'Rejected' },
    { id: 'cancel', name: 'Cancel' },
    { id: 'pending', name: 'Pending' },
];

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_ATTENDANCES, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_attendance') : $t('add_attendance')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input type="advance-search-select" id="user_id"
                            :label="$t('employee')" label-required list-value-field="name"
                            :loading-length="10" :fetch-url="HRM_EMPLOYEES"
                            :choose-label="$t('choose_employee')"
                            v-model="formData.user_id"
                            :errors="$errors(errors, 'user_id')" />
                    </div>

                    <div class="col-md-12">
                        <app-input type="advance-search-select" id="work_shift_id"
                            :label="$t('work_shift')" label-required list-value-field="shift_name"
                            :loading-length="10" :fetch-url="HRM_WORK_SHIFTS"
                            :choose-label="$t('choose_work_shift')"
                            v-model="formData.work_shift_id"
                            :errors="$errors(errors, 'work_shift_id')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="in_date" type="date" :label="$t('in_date')" label-required
                            v-model="formData.in_date"
                            :enable-time-picker="false" auto-apply
                            :errors="$errors(errors, 'in_date')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="behavior" type="select" :label="$t('behavior')" label-required
                            :options="behaviorOptions" list-value-field="name"
                            :selected-disable="false"
                            v-model="formData.behavior"
                            :errors="$errors(errors, 'behavior')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="status" type="select" :label="$t('status')" label-required
                            :options="statusOptions" list-value-field="name"
                            :selected-disable="false"
                            v-model="formData.status"
                            :errors="$errors(errors, 'status')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_ASSETS, HRM_SUPPORT_EMPLOYEES } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    asset: Object,
});

const emit = defineEmits(["close"]);

// editData "no" - this modal always creates a new assignment record, it
// never edits an existing one, so useSubmitForm shouldn't try to GET
// selectedUrl (which isn't set here) on mount.
const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit, "no");

formData.value = {
    employee_id: null,
    assigned_date: new Date(),
    note: null,
};

const submit = () => {
    save(`${HRM_ASSETS}/${props.asset.id}/assign`, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default" :title="$t('assign_asset')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <form>
                <div class="row g-4">

                    <div class="col-md-12">
                        <div class="p-3 rounded-3 bg-light">
                            <div class="fw-bold">{{ asset?.asset_name }}</div>
                            <div class="text-muted">{{ asset?.asset_code }}</div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <app-input type="advance-search-select" id="employee_id" :label="$t('employee')"
                            label-required list-value-field="name" v-model="formData.employee_id"
                            :loading-length="10" :fetch-url="HRM_SUPPORT_EMPLOYEES"
                            :choose-label="$t('choose_an_employee')"
                            :errors="$errors(errors, 'employee_id')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="assigned_date" type="date" :label="$t('assigned_date')" label-required
                            :placeholder="$t('assigned_date')"
                            v-model="formData.assigned_date"
                            :enable-time-picker="false"
                            auto-apply
                            :errors="$errors(errors, 'assigned_date')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="note" type="textarea" :label="$t('note')" :placeholder="$t('note')"
                            v-model="formData.note" :errors="$errors(errors, 'note')" />
                    </div>

                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

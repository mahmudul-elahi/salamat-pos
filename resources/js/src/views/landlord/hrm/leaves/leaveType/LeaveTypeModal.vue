<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_LEAVE_TYPES } from "@/services/endpoints/hrm.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    name: null,
    type: 'paid',
    day: null,
    enable: true,
};

const leaveTypeOptions = [
    { id: 'paid', name: 'Paid' },
    { id: 'unpaid', name: 'Unpaid' },
];

const enableOptions = [
    { id: true, name: 'Enabled' },
    { id: false, name: 'Disabled' },
];

const submit = () => save(props.selectedUrl ? props.selectedUrl : HRM_LEAVE_TYPES, formData.value);
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_leave_type') : $t('add_leave_type')"
        :preloader="preloader" @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">

                    <div class="col-md-12">
                        <app-input id="name" type="text" :label="$t('name')" label-required
                            :placeholder="$t('enter_leave_type_name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="type" type="select" :label="$t('type')" label-required
                            :options="leaveTypeOptions" list-value-field="name"
                            :selected-disable="false"
                            v-model="formData.type" :errors="$errors(errors, 'type')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="day" type="number" :label="$t('day')" label-required
                            :placeholder="$t('enter_days')" min="0" step="0.5"
                            v-model="formData.day" :errors="$errors(errors, 'day')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="enable" type="select" :label="$t('enable')" label-required
                            :options="enableOptions" list-value-field="name"
                            :selected-disable="false"
                            v-model="formData.enable" :errors="$errors(errors, 'enable')" />
                    </div>

                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

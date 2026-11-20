<script setup>
import { computed, watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_EVENTS, HRM_DEPARTMENTS } from "@/services/endpoints/hrm.js";
import { LANDLORD_USERS } from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    event_type: 'bulk',
    title: null,
    start_datetime: null,
    end_datetime: null,
    location: null,
    description: null,
    departments: [],
    employees: [],
};

const isIndividual = computed(() => formData.value.event_type === 'individual');

// when editing: extract ids from loaded objects
watch(() => pageLoader.value, (loading) => {
    if (!loading) {
        if (Array.isArray(formData.value.departments)) {
            formData.value.departments = formData.value.departments.map(
                d => (typeof d === 'object' ? d.id : d)
            );
        }
        if (Array.isArray(formData.value.employees)) {
            formData.value.employees = formData.value.employees.map(
                e => (typeof e === 'object' ? e.id : e)
            );
        }
    }
});

const eventTypeOptions = [
    { id: 'bulk', name: 'Bulk' },
    { id: 'individual', name: 'Individual' },
];

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_EVENTS, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="selectedUrl ? $t('update_event') : $t('add_event')"
        :preloader="preloader" @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">

                    <!-- Event Type -->
                    <div class="col-md-12">
                        <app-input id="event_type" type="select" :label="$t('event_type')" label-required
                            :options="eventTypeOptions" list-value-field="name" v-model="formData.event_type"
                            :selected-disable="false" :errors="$errors(errors, 'event_type')" />
                    </div>

                    <!-- Title -->
                    <div class="col-md-12">
                        <app-input id="title" type="text" :label="$t('title')" label-required
                            :placeholder="$t('enter_event_title')" v-model="formData.title"
                            :errors="$errors(errors, 'title')" />
                    </div>

                    <!-- Start datetime -->
                    <div class="col-md-6">
                        <app-input id="start_datetime" type="date" :label="$t('start_datetime')" label-required
                            v-model="formData.start_datetime" :enable-time-picker="true" auto-apply
                            :placeholder="$t('enter_start_datetime')" :errors="$errors(errors, 'start_datetime')" />
                    </div>

                    <!-- End datetime -->
                    <div class="col-md-6">
                        <app-input id="end_datetime" type="date" :label="$t('end_datetime')" label-required
                            v-model="formData.end_datetime" :enable-time-picker="true" auto-apply
                            :placeholder="$t('enter_end_datetime')" :errors="$errors(errors, 'end_datetime')" />
                    </div>

                    <!-- Location -->
                    <div class="col-md-12">
                        <app-input id="location" type="text" :label="$t('location')" :placeholder="$t('enter_location')"
                            v-model="formData.location" :errors="$errors(errors, 'location')" />
                    </div>

                    <!-- Individual fields -->
                    <template v-if="isIndividual">
                        <div class="col-md-12">
                            <div class="individual-divider">
                                <span>{{ $t('individual_assignment') }}</span>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <app-input type="advance-multi-select" id="departments" :label="$t('departments')"
                                list-value-field="name" list-key-field="id" :loading-length="10"
                                :fetch-url="HRM_DEPARTMENTS" :choose-label="$t('choose_departments')"
                                v-model="formData.departments" :errors="$errors(errors, 'departments')" />
                        </div>

                        <div class="col-md-12">
                            <app-input type="advance-multi-select" id="employees" :label="$t('employees')"
                                list-value-field="name" list-key-field="id" :loading-length="10"
                                :fetch-url="LANDLORD_USERS" :choose-label="$t('choose_employees')"
                                v-model="formData.employees" :errors="$errors(errors, 'employees')" />
                        </div>
                    </template>

                    <!-- Description -->
                    <div class="col-md-12">
                        <app-input id="description" type="editor" :label="$t('description')"
                            :placeholder="$t('write_description')" v-model="formData.description"
                            :errors="$errors(errors, 'description')" />
                    </div>

                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.individual-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #6c757d;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;

    &::before,
    &::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e8ecf0;
    }
}
</style>

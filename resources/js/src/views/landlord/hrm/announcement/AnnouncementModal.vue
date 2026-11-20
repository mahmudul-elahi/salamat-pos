<script setup>
import { watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_ANNOUNCEMENTS, HRM_DEPARTMENTS } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    title: null,
    start_date: null,
    end_date: null,
    description: null,
    departments: [],
};

// when editing, departments come as objects → extract ids
watch(() => pageLoader.value, (loading) => {
    if (!loading && Array.isArray(formData.value.departments)) {
        formData.value.departments = formData.value.departments.map(
            d => (typeof d === 'object' ? d.id : d)
        );
    }
});

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_ANNOUNCEMENTS, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large"
        :title="selectedUrl ? $t('update_announcement') : $t('add_announcement')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">

                    <div class="col-md-12">
                        <app-input id="title" type="text" :label="$t('title')" label-required
                            :placeholder="$t('enter_announcement_title')"
                            v-model="formData.title" :errors="$errors(errors, 'title')" />
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
                        <app-input type="advance-multi-select" id="departments"
                            :label="$t('departments')" list-value-field="name" list-key-field="id"
                            :loading-length="10" :fetch-url="HRM_DEPARTMENTS"
                            :choose-label="$t('choose_departments')"
                            v-model="formData.departments"
                            :errors="$errors(errors, 'departments')" />
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

<style scoped lang="scss"></style>

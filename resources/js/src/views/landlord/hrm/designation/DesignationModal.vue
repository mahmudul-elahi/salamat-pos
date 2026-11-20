<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_DESIGNATIONS } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    name: null,
    description: null,
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_DESIGNATIONS, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_designation') : $t('add_designation')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input id="name" type="text" :label="$t('name')" label-required
                            :placeholder="$t('enter_designation_name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
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

<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_ASSET_CATEGORIES } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    name: null,
    status: true,
};

const statusOptions = [
    { id: true, name: 'Active' },
    { id: false, name: 'Inactive' },
];

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_ASSET_CATEGORIES, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_asset_category') : $t('add_asset_category')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input id="name" type="text" :label="$t('name')" label-required
                            :placeholder="$t('enter_category_name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>
                    <div class="col-md-12">
                        <app-input id="status" type="select" label-required :label="$t('status')"
                            :options="statusOptions" list-value-field="name" :selected-disable="false"
                            v-model="formData.status" :errors="$errors(errors, 'status')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

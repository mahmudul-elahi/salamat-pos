<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_ASSETS, HRM_ASSET_CATEGORIES } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    asset_category_id: null,
    asset_name: null,
    asset_code: null,
    serial_number: null,
    purchase_date: null,
    purchase_cost: null,
    note: null,
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : HRM_ASSETS, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large"
        :title="selectedUrl ? $t('update_asset') : $t('add_asset')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">

                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="asset_category_id" :label="$t('category')"
                            label-required list-value-field="name" v-model="formData.asset_category_id"
                            :loading-length="10" :fetch-url="HRM_ASSET_CATEGORIES"
                            :choose-label="$t('choose_a_asset_category')"
                            :errors="$errors(errors, 'asset_category_id')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="asset_name" type="text" :label="$t('asset_name')" label-required
                            :placeholder="$t('enter_asset_name')"
                            v-model="formData.asset_name" :errors="$errors(errors, 'asset_name')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="asset_code" type="text" :label="$t('asset_code')" label-required
                            :placeholder="$t('enter_asset_code')"
                            v-model="formData.asset_code" :errors="$errors(errors, 'asset_code')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="serial_number" type="text" :label="$t('serial_number')"
                            :placeholder="$t('serial_number')"
                            v-model="formData.serial_number" :errors="$errors(errors, 'serial_number')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="purchase_date" type="date" :label="$t('purchase_date')"
                            :placeholder="$t('purchase_date')"
                            v-model="formData.purchase_date"
                            :enable-time-picker="false"
                            auto-apply
                            :errors="$errors(errors, 'purchase_date')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="purchase_cost" type="number" :label="$t('purchase_cost')"
                            :placeholder="$t('purchase_cost')"
                            v-model="formData.purchase_cost" :errors="$errors(errors, 'purchase_cost')" />
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

<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { SELECTED_DELIVERY_STATUSES, SELECTED_DELIVERY_BYS } from "@/services/endpoints/landlord.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String, order: Object });
const emit = defineEmits(["close"]);

const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit, "no");

formData.value = {
    delivery_status_id: props.order?.delivery_status?.id ?? null,
    delivery_by_id: props.order?.delivery_by?.id ?? null,
    invoice_no: props.order?.invoice_no ?? "",
    track_url: props.order?.track_url ?? "",
    delivery_note: props.order?.delivery_note ?? "",
};

const submit = () => {
    save(props.selectedUrl, formData.value, "patch");
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="$t('delivery')" save-button-label="Save"
        :preloader="preloader" @submit="submit" @close="closeModal">
        <template #body>
            <form @submit.prevent="submit">
                <div class="row g-4">
                    <div class="col-12" v-if="order">
                        <div class="text-muted fs-6">
                            {{ order.customer_name }} — {{ order.mobile || '-' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="delivery_status_id" :label="$t('delivery_status')"
                            list-key-field="id" list-value-field="name" :loading-length="100"
                            :fetch-url="SELECTED_DELIVERY_STATUSES" v-model="formData.delivery_status_id"
                            :choose-label="$t('delivery_status')" :errors="$errors(errors, 'delivery_status_id')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="delivery_by_id" :label="$t('delivery_by')"
                            list-key-field="id" list-value-field="name" :loading-length="100"
                            :fetch-url="SELECTED_DELIVERY_BYS" v-model="formData.delivery_by_id"
                            :choose-label="$t('delivery_by')" :errors="$errors(errors, 'delivery_by_id')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="text" id="invoice_no" :label="$t('invoice_no')" :placeholder="$t('invoice_no')"
                            v-model="formData.invoice_no" :errors="$errors(errors, 'invoice_no')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="text" id="track_url" :label="$t('track')" placeholder="https://..."
                            v-model="formData.track_url" :errors="$errors(errors, 'track_url')" />
                    </div>

                    <div class="col-12">
                        <app-input type="textarea" id="delivery_note" :label="$t('delivery_note')"
                            :placeholder="$t('delivery_note')" v-model="formData.delivery_note"
                            :errors="$errors(errors, 'delivery_note')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

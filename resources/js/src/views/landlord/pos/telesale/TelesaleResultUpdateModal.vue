<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { SELECTED_TELESALE_RESULTS } from "@/services/endpoints/landlord.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String, telesale: Object });
const emit = defineEmits(["close"]);

const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit, "no");

formData.value = {
    telesale_result_id: props.telesale?.result?.id ?? null,
};

const submit = () => {
    save(props.selectedUrl, formData.value, "patch");
};
</script>

<template>
    <app-modal :modal-id="modalId" :title="$t('update_result')" save-button-label="Save" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <form @submit.prevent="submit">
                <div class="row g-4">
                    <div class="col-12" v-if="telesale">
                        <div class="text-muted fs-6">
                            {{ telesale.customer_name || '-' }} — {{ telesale.customer_number }}
                        </div>
                    </div>

                    <div class="col-12">
                        <app-input type="advance-search-select" id="telesale_result_id" :label="$t('result')"
                            list-key-field="id" list-value-field="name" :loading-length="100"
                            :fetch-url="SELECTED_TELESALE_RESULTS" v-model="formData.telesale_result_id"
                            :choose-label="$t('choose_a_result')" :errors="$errors(errors, 'telesale_result_id')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

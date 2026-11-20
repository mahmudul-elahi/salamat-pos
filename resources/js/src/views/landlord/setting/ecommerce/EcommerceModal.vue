<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {LANDLORD_ECOMMERCES} from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const {preloader,pageLoader, formData, errors, save, closeModal} = useSubmitForm(props, emit)
const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : LANDLORD_ECOMMERCES, formData.value)
}
</script>

<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_ecommerce') : $t('add_ecommerce')"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <form v-else @submit.prevent="submit">
                <div class="mb-4">
                    <app-input type="text"
                               id="name"
                               :label="$t('name')"
                               label-required
                               :placeholder="$t('name')"
                               v-model="formData.name" :errors="$errors(errors, 'name')"/>
                </div>
            </form>

        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {WAREHOUSES} from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const {preloader, formData, errors, save, closeModal} = useSubmitForm(props, emit)
const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : WAREHOUSES, formData.value)
}
</script>
<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_warehouse') : $t('add_warehouse')"
               :preloader="preloader"
               @submit="submit" @close="closeModal">
        <template v-slot:body>
            <div class="mb-4">
                <app-input type="text"
                           id="name"
                           :label="$t('name')"
                           label-required
                           :placeholder="$t('name')"
                           v-model="formData.name"
                           :errors="$errors(errors, 'name')"/>            
            </div>
            <div class="mb-4">
                <app-input type="text"
                           id="phone_country"
                           :label="$t('phone_country')"
                           label-required
                           :placeholder="$t('phone_country')"
                           v-model="formData.phone_country"
                           :errors="$errors(errors, 'phone_country')"/>            
            </div>
            <div class="mb-4">
                <app-input type="number"
                           id="phone_number"
                           :label="$t('phone_number')"
                           label-required
                           :placeholder="$t('phone_number')"
                           v-model="formData.phone_number"
                           :errors="$errors(errors, 'phone_number')"/>            
            </div>
            <div class="mb-4">
                <app-input type="text"
                           id="email"
                           :label="$t('email')"
                           :placeholder="$t('email')"
                           v-model="formData.email"
                           :errors="$errors(errors, 'email')"/>            
            </div>
            <div class="mb-4">
                <app-input type="text"
                           id="address"
                           :label="$t('address')"
                           label-required
                           :placeholder="$t('address')"
                           v-model="formData.address"
                           :errors="$errors(errors, 'address')"/>            
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
</style>
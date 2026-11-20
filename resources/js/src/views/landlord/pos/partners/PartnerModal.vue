<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {CUSTOMERS} from "@/services/endpoints/landlord.js";
import {onMounted} from "vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
    phoneNumber: String,
})
const emit = defineEmits(['close'])
const {preloader, formData, errors, save, closeModal} = useSubmitForm(props, emit)

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : CUSTOMERS, formData.value)
}

onMounted(() => {
    formData.value.phone = props.phoneNumber ?? ''
})
</script>
<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_add_corporate_application') : $t('add_corporate_application')"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <div class="row g-4">
                <div class="col-md-6">
                    <app-input type="text"
                               id="name"
                               :label="$t('name')"
                               label-required
                               :placeholder="$t('name')"
                               v-model="formData.name"
                               :errors="$errors(errors, 'name')"/>
                </div>
                <div class="col-md-6">
                    <app-input type="text"
                               id="email"
                               :label="$t('email')"
                               :placeholder="$t('email')"
                               v-model="formData.email"
                               :errors="$errors(errors, 'email')"/>
                </div>
                <div class="col-md-6">
                    <app-input type="number"
                               id="phone"
                               :label="$t('phone')"
                               label-required
                               :placeholder="$t('phone')"
                               v-model="formData.phone"
                               :errors="$errors(errors, 'phone')"/>
                </div>
                <div class="col-md-6">
                    <app-input type="textarea"
                               id="address"
                               :label="$t('address')"
                               :placeholder="$t('address')"
                               v-model="formData.address"
                               :errors="$errors(errors, 'address')"/>
                </div>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
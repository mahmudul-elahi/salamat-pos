<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {INCOME_CATEGORY} from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const {preloader, formData, errors, save, closeModal} = useSubmitForm(props, emit)
const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : INCOME_CATEGORY, formData.value)
}
</script>
<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_income_category') : $t('add_income_category')"
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
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
</style>
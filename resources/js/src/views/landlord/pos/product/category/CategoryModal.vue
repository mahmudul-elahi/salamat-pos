<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {CATEGORIES} from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const {preloader, pageLoader, formData, errors, save, closeModal} = useSubmitForm(props, emit)
const submit = () => {
    let data = {...formData.value}
    save(props.selectedUrl ? props.selectedUrl : CATEGORIES, data)
}
</script>
<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="selectedUrl ? $t('update_category') : $t('add_category')"
               :preloader="preloader"
               @submit="submit" @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
           <form v-else @submit.prevent="submit">
               <div class="mb-4">
                   <app-input type="text" id="name"
                              :label="$t('name')"
                              label-required
                              :placeholder="$t('name')"
                              v-model="formData.name"
                              :errors="$errors(errors, 'name')"/>
               </div>
           </form>
        </template>
    </app-modal>
</template>
<style scoped lang="scss">
</style>

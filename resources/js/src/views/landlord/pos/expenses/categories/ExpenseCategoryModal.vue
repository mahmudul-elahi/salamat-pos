<script setup>
import { ref } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { EXPENSE_CATEGORY, SELECTED_SHOPS } from "@/services/endpoints/landlord.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit)

const mediaModalActive = ref(false);
const openMediaModal = () => {
    mediaModalActive.value = true;
};
const handleMediaSelected = (url) => {
    formData.value.logo = url;
    mediaModalActive.value = false;
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : EXPENSE_CATEGORY, formData.value)
}
</script>
<template>
    <app-modal :modal-id="modalId"
        modal-size="large"
        :title="selectedUrl ? $t('update_expense_category') : $t('add_expense_category')"
        :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader" />
            <div v-else class="row">
                <div class="col-xl-6">
                    <app-input type="text"
                        id="name"
                        :label="$t('name')"
                        label-required
                        :placeholder="$t('name')"
                        v-model="formData.name"
                        :errors="$errors(errors, 'name')" />
                </div>
                <div class="col-xl-6">
                    <Attachment
                        label="Logo (Optional)"
                        :value="formData.logo"
                        :showInput="false"
                        :error="$errors(errors, 'logo')"
                        @click="openMediaModal" />
                </div>
            </div>
        </template>
    </app-modal>

    <media-modal v-if="mediaModalActive"
        modal-id="expense-category-media-modal"
        @selected="handleMediaSelected"
        @close="mediaModalActive = false" />
</template>

<style scoped lang="scss"></style>
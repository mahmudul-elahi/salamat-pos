<script setup>
import { ref } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { LANDLORD_PAYMENT_METHODS, SELECTED_DIRECTORS } from "@/services/endpoints/landlord.js";
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
const mediaField = ref(null);

const openMediaModal = (field) => {
    mediaField.value = field;
    mediaModalActive.value = true;
};

const handleMediaSelected = (url) => {
    if (mediaField.value) formData.value[mediaField.value] = url;
    mediaModalActive.value = false;
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : LANDLORD_PAYMENT_METHODS, formData.value)
}
</script>

<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_payment_method') : $t('add_payment_method')"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <form v-else @submit.prevent="submit">
                <div class="row">
                    <div class="col-xl-6">
                        <app-input type="text"
                                   id="name"
                                   :label="$t('name')"
                                   label-required
                                   :placeholder="$t('name')"
                                   v-model="formData.name"
                                   :errors="$errors(errors, 'name')"/>
                    </div>
                    <div class="col-xl-6">
                        <app-input type="advance-search-select"
                                   id="director"
                                   label="Directors"
                                   listValueField="name"
                                   list-key-field="id"
                                   v-model="formData.director_id"
                                   :loading-length="10"
                                   :fetch-url="`${SELECTED_DIRECTORS}`"
                                   :choose-label="$t('choose_a_director')"
                                   :errors="$errors(errors, 'director_id')"/>
                    </div>
                    <div class="col-xl-6">
                        <Attachment
                            label="Logo (Optional)"
                            :value="formData.logo"
                            :showInput="false"
                            :error="$errors(errors, 'logo')"
                            @click="openMediaModal('logo')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>

    <media-modal v-if="mediaModalActive"
                 modal-id="payment-method-media-modal"
                 @selected="handleMediaSelected"
                 @close="mediaModalActive = false" />
</template>

<style scoped lang="scss"></style>

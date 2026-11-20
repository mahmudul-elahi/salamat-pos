<script setup>
import { ref } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { LANDLORD_SHOPS } from "@/services/endpoints/landlord.js";
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
    save(props.selectedUrl ? props.selectedUrl : LANDLORD_SHOPS, formData.value)
}
</script>

<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_shop') : $t('add_shop')"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <form v-else @submit.prevent="submit">
                <div class="row g-4">
                    <!-- Name -->
                    <div class="col-md-6">
                        <app-input type="text"
                                   id="name"
                                   :label="$t('name')"
                                   label-required
                                   :placeholder="$t('name')"
                                   v-model="formData.name"
                                   :errors="$errors(errors, 'name')"/>
                    </div>

                    <!-- Primary Phone -->
                    <div class="col-md-6">
                        <app-input type="text"
                                   id="phone"
                                   label="Primary Phone Number"
                                   placeholder="+880 1xxx-xxxxxx"
                                   v-model="formData.phone"
                                   :errors="$errors(errors, 'phone')"/>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <app-input type="text"
                                   id="email"
                                   label="Email"
                                   placeholder="example@email.com"
                                   v-model="formData.email"
                                   :errors="$errors(errors, 'email')"/>
                    </div>

                    <!-- Logo -->
                    <div class="col-md-6">
                        <Attachment
                            label="Logo (Optional)"
                            :value="formData.logo"
                            :showInput="false"
                            :error="$errors(errors, 'logo')"
                            @click="openMediaModal" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>

    <media-modal v-if="mediaModalActive"
                 modal-id="shop-logo-media-modal"
                 @selected="handleMediaSelected"
                 @close="mediaModalActive = false" />
</template>

<style scoped lang="scss"></style>

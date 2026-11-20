<script setup>
import { ref, watch, onMounted } from 'vue';
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import {
    FRANCHISES
} from "@/services/endpoints/landlord.js";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
    phoneNumber: String,
})
const emit = defineEmits(['close'])
const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit)
const showBrandModal = ref(true);

const mediaModalActive = ref(false);
const mediaLibraryModal = (type) => {
    currentImageType.value = type;
    mediaModalActive.value = true;
    showBrandModal.value = false;
}
const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    showBrandModal.value = true;
}
const currentImageType = ref(null);

const handleMediaSelected = (url) => {
    if (currentImageType.value) {
        formData.value[currentImageType.value] = url;
    }
    mediaModalActive.value = false;
    showBrandModal.value = true;
    currentImageType.value = null;
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : FRANCHISES, formData.value)
}

onMounted(() => {
    formData.value.phone = props.phoneNumber ?? ''
})
</script>
<template>
    <app-modal :modal-id="modalId" v-if="showBrandModal"
        modal-size="large"
        :title="selectedUrl ? $t('update_franchise') : $t('add_franchise')"
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
                        :errors="$errors(errors, 'name')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text"
                        id="email"
                        :label="$t('email')"
                        :placeholder="$t('email')"
                        v-model="formData.email"
                        :errors="$errors(errors, 'email')" />
                </div>
                <div class="col-md-6">
                    <app-input type="number"
                        id="primary_phone"
                        :label="$t('primary_phone')"
                        label-required
                        :placeholder="$t('primary_phone')"
                        v-model="formData.primary_phone"
                        :errors="$errors(errors, 'primary_phone')" />
                </div>
                <div class="col-md-6">
                    <app-input type="number"
                        id="secondary_phone"
                        :label="$t('secondary_phone')"
                        label-required
                        :placeholder="$t('secondary_phone')"
                        v-model="formData.secondary_phone"
                        :errors="$errors(errors, 'secondary_phone')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text"
                        id="business_name"
                        :label="$t('business_name')"
                        label-required
                        :placeholder="$t('business_name')"
                        v-model="formData.business_name"
                        :errors="$errors(errors, 'business_name')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text"
                        id="trade_license_number"
                        :label="$t('trade_license_number')"
                        label-required
                        :placeholder="$t('trade_license_number')"
                        v-model="formData.trade_license_number"
                        :errors="$errors(errors, 'trade_license_number')" />
                </div>
                <div class="col-12">
                    <div class="col-md-6">
                        <Attachment
                            :label="$t('business_logo')"
                            id="business_logo"
                            v-model="formData.business_logo"
                            :error="$errors(errors, 'business_logo')"
                            @click="mediaLibraryModal('business_logo')"
                            :showInput="false"
                            :recommended-size="$t('brand_image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')" />
                    </div>
                </div>                
                    <div class="col-xl-12">
                        <app-input
                            type="text"
                            id="street_address"
                            label="Street Address"
                            label-required
                            :placeholder="$t('enter_your_street_address')"
                            v-model="formData.street_address"
                            :errors="$errors(errors, 'street_address')" />
                    </div>
            </div>
        </template>
    </app-modal>
    <media-modal v-if="mediaModalActive" modal-id="media-modal" @selected="handleMediaSelected" @close="closeMediaModalActive" />
</template>

<style scoped lang="scss"></style>
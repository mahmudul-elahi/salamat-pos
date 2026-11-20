<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {BRANDS} from "@/services/endpoints/landlord.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import {ref} from "vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
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

const {preloader, pageLoader, formData, errors, save, closeModal} = useSubmitForm(props, emit)
const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : BRANDS, formData.value)
};

</script>
<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_brand') : $t('add_brand')"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input type="text" id="name"
                                   :label="$t('name')"
                                   label-required
                                   :placeholder="$t('name')"
                                   v-model="formData.name"
                                   :errors="$errors(errors, 'name')"/>
                    </div>
                    <div class="col-md-12">
                        <app-input type="text" id="tagline"
                                   :label="$t('tagline')"
                                   :placeholder="$t('tagline')"
                                   v-model="formData.tagline"
                                   :errors="$errors(errors, 'tagline')"/>
                    </div>
                    <div class="col-md-12">
                        <app-input type="text" id="android_app"
                                   :label="$t('android_app_link')"
                                   :placeholder="$t('android_app_link')"
                                   v-model="formData.android_app"
                                   :errors="$errors(errors, 'android_app')"/>
                    </div>
                    <div class="col-md-12">
                        <app-input type="text" id="ios_app"
                                   :label="$t('ios_app_link')"
                                   :placeholder="$t('ios_app_link')"
                                   v-model="formData.ios_app"
                                   :errors="$errors(errors, 'ios_app')"/>
                    </div>
                    <!-- <div class="col-md-12">
                        <app-input type="editor" id="description"
                            :label="$t('description')"
                            :placeholder="$t('description')"
                            v-model="formData.description"
                            :errors="$errors(errors, 'description')" />
                    </div> -->
                    <div class="col-md-6">
                        <Attachment
                            :label="$t('primary_logo')"
                            id="primary_logo"
                            v-model="formData.primary_logo"
                            :error="$errors(errors, 'primary_logo')"
                            @click="mediaLibraryModal('primary_logo')"
                            :showInput="false"
                            :recommended-size="$t('brand_image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')"/>
                    </div>
                    <div class="col-md-6">
                        <Attachment
                            :label="$t('secondary_logo')"
                            id="secondary_logo"
                            v-model="formData.secondary_logo"
                            @changeFile="changeSecondaryLogo($event)"
                            :error="$errors(errors, 'secondary_logo')"
                            @click="mediaLibraryModal('secondary_logo')"
                            :showInput="false"
                            :recommended-size="$t('brand_image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')"/>
                    </div>
                    <div class="col-md-6">
                        <Attachment
                            :label="$t('shop_banner')"
                            id="shop_banner"
                            v-model="formData.shop_banner"
                            @changeFile="changeShopBanner($event)"
                            :error="$errors(errors, 'shop_banner')"
                            @click="mediaLibraryModal('shop_banner')"
                            :showInput="false"
                            :recommended-size="$t('brand_image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')"/>
                    </div>
                    <div class="col-md-6">
                        <Attachment
                            :label="$t('info_banner')"
                            id="info_banner"
                            v-model="formData.info_banner"
                            @changeFile="changeInfoBanner($event)"
                            :error="$errors(errors, 'info_banner')"
                            @click="mediaLibraryModal('info_banner')"
                            :showInput="false"
                            :recommended-size="$t('brand_image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')"/>
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
    <media-modal v-if="mediaModalActive" modal-id="media-modal" @selected="handleMediaSelected"
                 @close="closeMediaModalActive"/>


</template>

<style scoped lang="scss"></style>

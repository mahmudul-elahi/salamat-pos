<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {BRANDS, SELECTED_ECOMMERCES} from "@/services/endpoints/landlord.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import {onMounted, ref} from "vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
    rowData: {}
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
const {
    pageLoader,
    preloader,
    errors,
    formData,
    closeModal,
    afterSuccess,
    afterError,
    afterFinalResponse
} = useSubmitForm(props, emit)
const submit = () => {
    Axios.post(`/admin/ecommerce/all-pages/${props.rowData.id}/update`, formData.value)
        .then((response) => {
            afterSuccess(response);
        }).catch(({response}) => {
        afterError(response);
    }).finally(() => afterFinalResponse());
}

</script>
<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_pages') : $t('add_brand')"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input type="text"
                                   id="title"
                                   :label="$t('title')"
                                   :placeholder="$t('title')"
                                   v-model="formData.title"
                                   :errors="$errors(errors, 'title')"/>
                    </div>
                    <div class="col-md-6">
                        <Attachment
                            :label="$t('logo')"
                            id="logo"
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

    <media-modal v-if="mediaModalActive"
                 modal-id="media-modal"
                 @selected="handleMediaSelected"
                 @close="closeMediaModalActive"/>


</template>

<style scoped lang="scss"></style>

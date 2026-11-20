<script setup>
import {watch} from "vue";
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {SELECTED_ECOMMERCES} from "@/services/endpoints/landlord.js";
import {SLIDERS} from "@/services/endpoints/Ecommerce.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import Axios from "@/services/axios/index.js";
import {useI18n} from "vue-i18n";
import {useToast} from "vue-toastification";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import {onMounted, ref} from "vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const {t} = useI18n();
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

const {preloader, pageLoader, errors, save, closeModal, formData} = useSubmitForm(props, emit)
formData.value = {
    status: 1
}
const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : SLIDERS, formData.value)
}
const buttonType = [
    {
        id: 1,
        name: t('shop_now')
    },
    {
        id: 2,
        name: t('coming_soon')
    }
];
const bannerFeatureOptions = [
    {
        id: 'flash_sell',
        name: t('flash_sell'),
    },
    {
        id: 'new_feature',
        name: t('new_feature'),
    },
    {
        id: 'coming_soon',
        name: t('coming_soon'),
    },
    {
        id: 'four_card',
        name: t('four_card'),
    },
    {
        id: 'three_card',
        name: t('three_card'),
    },
    {
        id: 'home_large_banner',
        name: t('home_large_banner'),
    },
];

watch(
    () => formData.value.status,
    (newVal) => {
        if (newVal !== 11) {
            formData.value.banner_feature = null;
        }
    }
);
</script>
<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_slider') : $t('add_slider')"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <form v-else>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <app-input
                            type="advance-search-select"
                            id="tenant_id"
                            label-required
                            :label="$t('shop_name')"
                            list-value-field="name"
                            v-model="formData.tenant_id"
                            :loading-length="10"
                            :fetch-url="SELECTED_ECOMMERCES"
                            :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, `tenant_id`)"/>
                    </div>
                    <div class="col-lg-6">
                        <app-input
                            type="select"
                            id="slider_type"
                            label-required
                            :label="$t('slider_type')"
                            list-value-field="name"
                            v-model="formData.status"
                            :options="[{
                                id: 1,
                                name: 'Main Slider'
                            }, {
                                id: 11,
                                name: 'Banner'
                            }]"
                            :selected-disable="false"
                            :choose-label="$t('choose_a_slider_type')"
                            :errors="$errors(errors, `status`)"/>
                    </div>
                    <div class="col-md-12" v-if="formData.status === 11">
                        <app-input
                            type="select"
                            id="banner_feature"
                            :label="$t('banner_feature')"
                            label-required
                            :options="bannerFeatureOptions"
                            list-value-field="name"
                            :choose-label="$t('choose_an_banner')"
                            v-model="formData.banner_feature"
                            :errors="$errors(errors, 'banner_feature')"/>
                    </div>
                    <div class="col-md-12">
                        <Attachment
                            :label="$t('image')"
                            id="image"
                            v-model="formData.image"
                            :error="$errors(errors, 'image')"
                            @click="mediaLibraryModal('image')"
                            :showInput="false"
                            :recommended-size="$t('brand_image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')"/>
                    </div>
                    <div class="col-md-12">
                        <app-input type="text" id="heading"
                                   :label="$t('heading')"
                                   :placeholder="$t('heading')"
                                   v-model="formData.heading"
                                   :errors="$errors(errors, 'heading')"/>
                    </div>

                    <div class="col-md-12">
                        <app-input type="text"
                                   id="url"
                                   :label="$t('url')"
                                   :placeholder="$t('url')"
                                   v-model="formData.url"
                                   :errors="$errors(errors, 'url')"/>
                    </div>
                    <!--                    <div class="col-md-12">-->
                    <!--                        <app-input-->
                    <!--                            type="select"-->
                    <!--                            id="button_type"-->
                    <!--                            :label="$t('button_type')"-->
                    <!--                            :options="buttonType"-->
                    <!--                            list-value-field="name"-->
                    <!--                            :choose-label="$t('choose_a_type')"-->
                    <!--                            v-model="formData.button_type"-->
                    <!--                            :errors="$errors(errors, 'button_type')" />-->
                    <!--                    </div>-->
                </div>
            </form>
        </template>
    </app-modal>
    <media-modal v-if="mediaModalActive" modal-id="media-modal" @selected="handleMediaSelected"
                 @close="closeMediaModalActive"/>


</template>

<style scoped lang="scss"></style>

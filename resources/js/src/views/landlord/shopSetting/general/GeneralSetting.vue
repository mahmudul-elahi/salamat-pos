<script setup>
import { onMounted, ref } from "vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import Axios from "@/services/axios/index.js";
import { LANDLORD_SHOP_SETTINGS } from "@/services/endpoints/landlord.js";
import { useToast } from "vue-toastification";
import { Download } from "@iconoir/vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";

const props = defineProps({
    id: {},
    shopId: {}
});

const toast = useToast();

const pageLoader = ref(false);
const formData = ref({});
const errors = ref({});
const preloader = ref(false);

const mediaModalActive = ref(false);
const currentImageType = ref(null);

const mediaLibraryModal = (type) => {
    currentImageType.value = type;
    mediaModalActive.value = true;
};

const handleMediaSelected = (url) => {
    if (currentImageType.value) {
        formData.value[currentImageType.value] = url;
    }
    mediaModalActive.value = false;
    currentImageType.value = null;
};

const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    currentImageType.value = null;
};

const submit = async () => {
    preloader.value = true;
    errors.value = {};

    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 30000);

    try {
        const response = await Axios.post(
            `${LANDLORD_SHOP_SETTINGS}/${props.shopId}`,
            formData.value,
            { signal: controller.signal }
        );
        toast.success(response.data.message);
    } catch (err) {
        if (err.name === 'CanceledError' || err.code === 'ERR_CANCELED') {
            toast.error('Request timed out. Please try again.');
        } else if (err.response?.status === 422) {
            errors.value = err.response.data.errors;
        } else {
            toast.error(err.response?.data?.message ?? 'Something went wrong.');
        }
    } finally {
        clearTimeout(timeout);
        preloader.value = false;
    }
};

const getServerGeneralSetting = async () => {
    pageLoader.value = true;
    try {
        const { data } = await Axios.get(`${LANDLORD_SHOP_SETTINGS}/${props.shopId}`);
        formData.value = data;
    } catch (error) {
        toast.error('Failed to load settings.');
    } finally {
        pageLoader.value = false;
    }
};

onMounted(() => {
    getServerGeneralSetting();
});
</script>

<template>
    <div class="form-center-loader d-flex justify-content-center" v-if="pageLoader">
        <app-loader />
    </div>
    <form v-else>
        <div class="row g-4">
            <div class="col-md-12">
                <app-input type="text" id="shopName" :label="$t('shop_name')" label-required
                    :placeholder="$t('company_name')" v-model="formData.company_name"
                    :errors="$errors(errors, 'company_name')" />
            </div>
            <div class="col-md-6">
                <app-input type="email" id="business_email" :label="$t('business_email')" :label-required="true"
                    :placeholder="$t('business_email')" v-model="formData.business_email"
                    :errors="$errors(errors, 'business_email')" />
            </div>
            <div class="col-md-6">
                <app-input type="email" id="support_email" :label="$t('support_email')" :label-required="true"
                    :placeholder="$t('support_email')" v-model="formData.support_email"
                    :errors="$errors(errors, 'support_email')" />
            </div>
            <div class="col-md-6">
                <app-input type="text" id="primary_phone_number" :label="$t('primary_phone_number')"
                    :placeholder="$t('primary_phone_number')" v-model="formData.primary_phone_number"
                    :errors="$errors(errors, 'primary_phone_number')" />
            </div>
            <div class="col-md-6">
                <app-input type="text" id="secondary_phone_number" :label="$t('secondary_phone_number')"
                    :placeholder="$t('secondary_phone_number')" v-model="formData.secondary_phone_number"
                    :errors="$errors(errors, 'secondary_phone_number')" />
            </div>
            <div class="col-md-6">
                <app-input type="text" id="primary_address" :label="$t('primary_address')"
                    :placeholder="$t('primary_address')" v-model="formData.primary_address"
                    :errors="$errors(errors, 'primary_address')" />
            </div>
            <div class="col-md-6">
                <app-input type="text" id="secondary_address" :label="$t('secondary_address')"
                    :placeholder="$t('secondary_address')" v-model="formData.secondary_address"
                    :errors="$errors(errors, 'secondary_address')" />
            </div>


            <div class="col-md-4">

                <Attachment :label="$t('company_logo')" id="company_logo" v-model="formData.company_logo"
                    :error="$errors(errors, 'company_logo')" @click="mediaLibraryModal('company_logo')"
                    :showInput="false" :recommended-size="$t('logo_recommended_size')"
                    :recommended-file-size="$t('setting_file_size')" />
            </div>

            <div class="col-md-4">

                <Attachment :label="$t('favicon_icon')" id="favicon_icon" v-model="formData.company_icon"
                    :error="$errors(errors, 'company_icon')" @click="mediaLibraryModal('company_icon')"
                    :showInput="false" :recommended-size="$t('favicon_icon_size')"
                    :recommended-file-size="$t('setting_file_size')" />

            </div>

            <div class="col-md-4">
                <Attachment :label="$t('share_icon')" id="share_icon" v-model="formData.share_icon"
                    :error="$errors(errors, 'share_icon')" @click="mediaLibraryModal('share_icon')" :showInput="false"
                    :recommended-size="$t('share_icon_size')" :recommended-file-size="$t('setting_file_size')" />

            </div>


            <div class="col-md-6">
                <app-input type="text" id="facebook_link" :label="$t('facebook_link')"
                    :placeholder="$t('facebook_link')" v-model="formData.facebook_link"
                    :errors="$errors(errors, 'facebook_link')" />
            </div>

            <div class="col-md-6">
                <app-input type="text" id="twitter_link" :label="$t('twitter_link')" :placeholder="$t('twitter_link')"
                    v-model="formData.twitter_link" :errors="$errors(errors, 'twitter_link')" />
            </div>

            <div class="col-md-6">
                <app-input type="text" id="youtube_link" :label="$t('youtube_link')" :placeholder="$t('youtube_link')"
                    v-model="formData.youtube_link" :errors="$errors(errors, 'youtube_link')" />
            </div>
            <div class="col-md-6">
                <app-input type="text" id="linkedin_link" :label="$t('linkedin_link')"
                    :placeholder="$t('linkedin_link')" v-model="formData.linkedin_link"
                    :errors="$errors(errors, 'linkedin_link')" />
            </div>

            <div class="col-md-6">
                <app-input type="text" id="instagram_link" :label="$t('instagram_link')"
                    :placeholder="$t('instagram_link')" v-model="formData.instagram_link"
                    :errors="$errors(errors, 'instagram_link')" />
            </div>
            <div class="col-md-6">
                <app-input type="text" id="tiktok_link" :label="$t('tiktok_link')" :placeholder="$t('tiktok_link')"
                    v-model="formData.tiktok_link" :errors="$errors(errors, 'tiktok_link')" />
            </div>
            <div class="col-md-6">
                <app-input type="text" id="location_link" :label="$t('location_link')"
                    :placeholder="$t('location_link')" v-model="formData.location_link"
                    :errors="$errors(errors, 'location_link')" />
            </div>

        </div>

        <button type="button" class="btn btn-primary shadow mt-3" :disabled="preloader" @click.prevent="submit">
            <app-button-loader v-if="preloader" />
            <Download />
            {{ $t('update') }}
        </button>
    </form>
    <media-modal v-if="mediaModalActive" modal-id="media-modal" @selected="handleMediaSelected"
        @close="closeMediaModalActive" />


</template>

<style scoped lang="scss"></style>

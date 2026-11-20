<script setup>
import { ref } from 'vue';
import Axios from "@/services/axios/index.js";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import {
    CUSTOMERS,
    SUPPLIERS,
    SELECTED_AREAS,
    SELECTED_DISTRICTS,
    SELECTED_COUNTRIES,
} from "@/services/endpoints/landlord.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";

const props = defineProps({
    modalId: String,
    tableId: String,
});

// 'created' lets a caller that opened this modal inline pick up the new
// record's id right away instead of it being created but left unselected.
const emit = defineEmits(['close', 'created']);

const {
    preloader,
    formData,
    errors,
    closeModal,
    afterSuccess,
    afterError,
    afterFinalResponse,
} = useSubmitForm(props, emit, 'no');

const typeOptions = [
    { id: 'consumer', name: 'Customer' },
    { id: 'supplier', name: 'Supplier' },
];
const type = ref('consumer');

const areaFetchUrl = ref(null);
const mediaModalActive = ref(false);
const currentImageType = ref(null);

const resetAreaFields = () => {
    formData.value.area_id = null;
    formData.value.thana = '';
    formData.value.postal_code = '';
};

const changeDistrict = (event) => {
    resetAreaFields();
    areaFetchUrl.value = event?.id ? SELECTED_AREAS(event.id) : null;
};

const changeArea = (event) => {
    if (event) {
        formData.value.thana = event.thana;
        formData.value.postal_code = event.postcode;
    }
};

const mediaLibraryModal = (imageType) => {
    currentImageType.value = imageType;
    mediaModalActive.value = true;
};

const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
};

const handleMediaSelected = (url) => {
    if (currentImageType.value === 'profile_picture') {
        formData.value.profile_picture = url;
    } else if (currentImageType.value === 'business_logo') {
        formData.value.business_logo = url;
    }
    closeMediaModalActive();
};

const submit = () => {
    const customData = {
        name:             formData.value.name || '',
        business_name:    formData.value.business_name || '',
        email:            formData.value.email || '',
        phone:            formData.value.phone || '',
        secondary_phone:  formData.value.secondary_phone || '',
        country_id:       formData.value.country_id || '',
        new_district_id:  formData.value.new_district_id || '',
        area_id:          formData.value.area_id || '',
        thana:            formData.value.thana || '',
        postal_code:      formData.value.postal_code || '',
        address:          formData.value.address || '',
        profile_picture:  formData.value.profile_picture || '',
        business_logo:    formData.value.business_logo || '',
    };

    preloader.value = true;
    errors.value = {};

    const url = type.value === 'supplier' ? SUPPLIERS : CUSTOMERS;

    Axios.post(url, customData).then((response) => {
        if (response?.data?.result) {
            emit('created', response.data.result);
        }
        afterSuccess(response);
    }).catch(({ response }) => {
        if (response?.data?.errors) {
            errors.value = response.data.errors;
        }
        afterError(response);
    }).finally(() => afterFinalResponse());
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="extra-large" :title="$t('add_people')"
        :preloader="preloader" @submit="submit" @close="closeModal">
        <template #body>
            <form @submit.prevent="submit">
                <div class="row g-4">
                    <div class="col-12">
                        <app-input type="radio" id="people_type" v-model="type"
                            list-key-field="id" list-value-field="name" :options="typeOptions" form-check-inline />
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="text" id="name" :label="$t('name')" label-required
                            :placeholder="$t('name')" v-model="formData.name"
                            :errors="$errors(errors, 'name')" />
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="text" id="business_name" :label="$t('business_name')"
                            :label-required="type === 'supplier'"
                            :placeholder="$t('business_name')" v-model="formData.business_name"
                            :errors="$errors(errors, 'business_name')" />
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="number" id="phone" :label="$t('phone')" label-required
                            :placeholder="$t('phone')" v-model="formData.phone"
                            :errors="$errors(errors, 'phone')" />
                    </div>
                    <div class="col-xl-6 col-md-6">
                        <app-input type="number" id="secondary_phone" :label="$t('secondary_phone')"
                            :placeholder="$t('secondary_phone')" v-model="formData.secondary_phone"
                            :errors="$errors(errors, 'secondary_phone')" />
                    </div>
                    <div class="col-xl-6 col-md-6">
                        <app-input type="email" id="email" :label="$t('email')"
                            :placeholder="$t('email')" v-model="formData.email"
                            :errors="$errors(errors, 'email')" />
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <Attachment :label="$t('profile_picture')" v-model="formData.profile_picture"
                            :preview="formData.profile_picture"
                            @click="mediaLibraryModal('profile_picture')"
                            :error="$errors(errors, 'profile_picture')" :showInput="false"
                            :recommended-size="$t('image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')" />
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <Attachment :label="$t('business_logo')" v-model="formData.business_logo"
                            :preview="formData.business_logo"
                            @click="mediaLibraryModal('business_logo')"
                            :error="$errors(errors, 'business_logo')" :showInput="false"
                            :recommended-size="$t('image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')" />
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-12">
                        <h5 class="title mb-0">{{ $t('address') }}</h5>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="advance-search-select" id="country" :label="$t('country')"
                            :label-required="type === 'supplier'"
                            list-value-field="name" list-key-field="id" v-model="formData.country_id"
                            :loading-length="10" :fetch-url="SELECTED_COUNTRIES"
                            :choose-label="$t('choose_a_country')"
                            :errors="$errors(errors, 'country_id')" />
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="advance-search-select" id="district" :label="$t('district')"
                            list-value-field="name" list-key-field="id" v-model="formData.new_district_id"
                            :loading-length="10" :fetch-url="SELECTED_DISTRICTS"
                            :choose-label="$t('select_district')"
                            :errors="$errors(errors, 'new_district_id')"
                            @searchSelect="changeDistrict($event)" />
                    </div>
                    <div class="col-xl-4 col-md-6" v-if="formData.new_district_id">
                        <app-input type="advance-search-select" id="area" :label="$t('area')"
                            list-value-field="name" list-key-field="id" v-model="formData.area_id"
                            :loading-length="10" :fetch-url="areaFetchUrl"
                            :choose-label="$t('select_area')" :selected-disable="false"
                            :errors="$errors(errors, 'area_id')"
                            @searchSelect="changeArea($event)" />
                    </div>
                    <div v-else class="col-xl-4 col-md-6" style="pointer-events:none;">
                        <app-input type="advance-search-select" id="area" :label="$t('area')"
                            list-value-field="name" list-key-field="id" v-model="formData.area_id"
                            :loading-length="10" :fetch-url="areaFetchUrl"
                            :choose-label="$t('select_area')" :selected-disable="false"
                            :errors="$errors(errors, 'area_id')" />
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="text" id="thana" :label="$t('thana')" readonly
                            :placeholder="$t('enter_thana')" v-model="formData.thana"
                            :errors="$errors(errors, 'thana')" />
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="text" id="postal_code" :label="$t('postal_code')" readonly
                            :placeholder="$t('enter_postal_code')" v-model="formData.postal_code"
                            :errors="$errors(errors, 'postal_code')" />
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="text" id="address" :label="$t('street_address')"
                            :label-required="type === 'supplier'"
                            :placeholder="$t('enter_your_street_address')" v-model="formData.address"
                            :errors="$errors(errors, 'address')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>

    <media-modal v-if="mediaModalActive" modal-id="media-modal"
        @selected="handleMediaSelected" @close="closeMediaModalActive" />
</template>

<style scoped lang="scss"></style>

<script setup>
import {onMounted, ref} from "vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import {SELECTED_AREAS, SELECTED_COUNTRIES, SELECTED_DISTRICTS} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";

const props = defineProps({
    formData: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    preloader: {
        type: Boolean,
        default: false,
    },
    pageTitle: {
        type: String,
        required: true,
    },
    submitText: {
        type: String,
        required: true,
    },
    initialAreaFetchUrl: {type: String, default: null},
});

const emit = defineEmits(['submit']);

const mediaModalActive = ref(false);
const currentImageTarget = ref({field: null, index: null});

const dayOptions = [
    {id: 'monday', name: 'Monday'},
    {id: 'tuesday', name: 'Tuesday'},
    {id: 'wednesday', name: 'Wednesday'},
    {id: 'thursday', name: 'Thursday'},
    {id: 'friday', name: 'Friday'},
    {id: 'saturday', name: 'Saturday'},
    {id: 'sunday', name: 'Sunday'},
];

const statusOptions = [
    {id: 1, name: 'Active'},
    {id: 0, name: 'Inactive'},
];

const imageTypeOptions = [
    {id: 'banner', name: 'Banner'},
    {id: 'gallery', name: 'Gallery'},
    {id: 'thumbnail', name: 'Thumbnail'},
];

const addImageRow = () => {
    props.formData.images.push({
        image_url: '',
        type: 'gallery',
        alt_text: '',
        sort_order: props.formData.images.length,
    });
};

const removeImageRow = (index) => {
    props.formData.images.splice(index, 1);
};

const mediaLibraryModal = (field, index = null) => {
    currentImageTarget.value = {field, index};
    mediaModalActive.value = true;
};

const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    currentImageTarget.value = {field: null, index: null};
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
};

const handleMediaSelected = (url) => {
    if (currentImageTarget.value.field === 'image') {
        props.formData.image = url;
    }

    if (currentImageTarget.value.field === 'images' && currentImageTarget.value.index !== null) {
        props.formData.images[currentImageTarget.value.index].image_url = url;
    }

    closeMediaModalActive();
};

const areaFetchUrl = ref(props.initialAreaFetchUrl);
const resetAreaFields = () => {
    props.formData.area_id = null;
    props.formData.thana = '';
    props.formData.postal_code = '';
};
const changeDistrict = (event) => {
    resetAreaFields();
    areaFetchUrl.value = event?.id ? SELECTED_AREAS(event.id) : null;
};

const changeArea = (event) => {
    if (event) {
        props.formData.thana = event.thana;
        props.formData.postal_code = event.postcode;
    }
};


onMounted(() => {
    if (!Array.isArray(props.formData.images)) {
        props.formData.images = [];
    }
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="pageTitle"/>

        <div class="card">
            <div class="card-body">
                <form @submit.prevent="emit('submit')">
                    <div class="row g-4 mb-4">
                        <div class="col-md-8">
                            <app-input
                                type="text"
                                id="name"
                                :label="$t('name')"
                                label-required
                                :placeholder="$t('name')"
                                v-model="formData.name"
                                :errors="$errors(errors, 'name')"/>
                        </div>

                        <div class="col-md-4">
                            <Attachment
                                :label="$t('image')"
                                id="image"
                                v-model="formData.image"
                                :error="$errors(errors, 'image')"
                                @click="mediaLibraryModal('image')"
                                :showInput="false"
                                :recommended-size="$t('image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')"/>
                        </div>


                        <div class="col-md-6">
                            <app-input
                                type="tel"
                                id="phone"
                                :label="$t('phone')"
                                :placeholder="$t('phone')"
                                v-model="formData.phone"
                                :errors="$errors(errors, 'phone')"/>
                        </div>

                        <div class="col-md-6">
                            <app-input
                                type="email"
                                id="email"
                                :label="$t('email')"
                                :placeholder="$t('email')"
                                v-model="formData.email"
                                :errors="$errors(errors, 'email')"/>
                        </div>

                        <div class="col-md-12">
                            <app-input
                                type="textarea"
                                id="address"
                                :label="$t('address')"
                                :placeholder="$t('address')"
                                v-model="formData.address"
                                :errors="$errors(errors, 'address')"/>
                        </div>

                        <div class="col-md-4">
                            <app-input type="advance-search-select"
                                       id="countries"
                                       :label="$t('country')"
                                       label-required
                                       listValueField="name"
                                       list-key-field="id"
                                       v-model="formData.country_id"
                                       :loading-length="10"
                                       :fetch-url="`${SELECTED_COUNTRIES}`"
                                       :choose-label="$t('choose_a_country')"
                                       :errors="$errors(errors, 'country_id')"/>
                        </div>

                        <div class="col-md-4">
                            <app-input
                                type="advance-search-select"
                                id="district"
                                list-value-field="name"
                                list-key-field="id"
                                :label="$t('district')"
                                v-model="formData.new_district_id"
                                :loading-length="10"
                                :fetch-url="`${SELECTED_DISTRICTS}`"
                                :choose-label="$t('select_district')"
                                :errors="$errors(errors, 'new_district_id')"
                                @searchSelect="changeDistrict($event)"/>
                        </div>

                        <div class="col-md-4" v-if="formData.new_district_id">
                            <app-input
                                type="advance-search-select"
                                id="area"
                                list-value-field="name"
                                list-key-field="id"
                                :label="$t('area')"
                                v-model="formData.area_id"
                                :loading-length="10"
                                :fetch-url="areaFetchUrl"
                                :choose-label="$t('select_area')"
                                :selected-disable="false"
                                :errors="$errors(errors, 'area_id')"
                                @searchSelect="changeArea($event)"/>
                        </div>


                        <div class="col-xl-3 col-md-3">
                            <app-input
                                type="text"
                                id="thana"
                                :label="$t('thana')"
                                readonly
                                :placeholder="$t('enter_thana')"
                                v-model="formData.thana"
                                :errors="$errors(errors, 'thana')"/>
                        </div>
                        <div class="col-xl-3 col-md-3">
                            <app-input
                                type="text"
                                id="postal_code"
                                :label="$t('postal_code')"
                                readonly
                                :placeholder="$t('enter_postal_code')"
                                v-model="formData.postal_code"
                                :errors="$errors(errors, 'postal_code')"/>
                        </div>

                        <div class="col-md-3">
                            <div>
                                <label for="opens" class="mb-2">{{ $t('opens') }} <span
                                    class="text-danger">*</span></label>
                                <input id="opens" v-model="formData.opens" type="time" class="form-control"/>
                                <div v-if="$errors(errors, 'opens')" class="mt-1">
                                    <small class="text-danger validation-error">{{ $errors(errors, 'opens') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div>
                                <label for="closes" class="mb-2">{{ $t('closes') }} <span
                                    class="text-danger">*</span></label>
                                <input id="closes" v-model="formData.closes" type="time" class="form-control"/>
                                <div v-if="$errors(errors, 'closes')" class="mt-1">
                                    <small class="text-danger validation-error">{{ $errors(errors, 'closes') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <app-input
                                type="select"
                                id="close_day"
                                :label="$t('close_day')"
                                label-required
                                list-value-field="name"
                                :options="dayOptions"
                                :choose-label="$t('close_day')"
                                :selected-disable="false"
                                v-model="formData.close_day"
                                :errors="$errors(errors, 'close_day')"/>
                        </div>

                        <div class="col-md-6">
                            <app-input
                                type="text"
                                id="google_map_url"
                                :label="$t('google_map_url')"
                                :placeholder="$t('google_map_url')"
                                v-model="formData.google_map_url"
                                :errors="$errors(errors, 'google_map_url')"/>
                        </div>

                        <div class="col-md-2">
                            <app-input
                                type="select"
                                id="status"
                                :label="$t('status')"
                                list-value-field="name"
                                :options="statusOptions"
                                :selected-disable="false"
                                v-model="formData.status"
                                :errors="$errors(errors, 'status')"/>
                        </div>

                        <div class="col-md-12">
                            <app-input
                                type="editor"
                                id="description"
                                :label="$t('description')"
                                v-model="formData.description"
                                :errors="$errors(errors, 'description')"/>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">{{ $t('outlet_images') }}</h5>
                        <button type="button" class="btn btn-primary btn-sm" @click="addImageRow">
                            {{ $t('add_image') }}
                        </button>
                    </div>

                    <div v-if="!formData.images.length" class="alert alert-light border mb-4">
                        {{ $t('nothing_to_show_here') }}
                    </div>

                    <div v-for="(item, index) in formData.images" :key="index" class="card mb-3 border">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">{{ $t('image') }} #{{ index + 1 }}</h6>
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        @click="removeImageRow(index)">
                                    {{ $t('remove') }}
                                </button>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-4">
                                    <Attachment
                                        :label="$t('image')"
                                        :id="`outlet-image-${index}`"
                                        v-model="item.image_url"
                                        :error="$errors(errors, `images.${index}.image_url`)"
                                        @click="mediaLibraryModal('images', index)"
                                        :showInput="false"
                                        :recommended-size="$t('image_recommended_size')"
                                        :recommended-file-size="$t('setting_file_size')"/>
                                </div>

                                <div class="col-md-3">
                                    <app-input
                                        type="select"
                                        :id="`type-${index}`"
                                        :label="$t('image_type')"
                                        label-required
                                        list-value-field="name"
                                        :options="imageTypeOptions"
                                        v-model="item.type"
                                        :errors="$errors(errors, `images.${index}.type`)"/>
                                </div>

                                <div class="col-md-2">
                                    <app-input
                                        type="number"
                                        :id="`sort_order-${index}`"
                                        :label="$t('sort_order')"
                                        v-model="item.sort_order"
                                        :errors="$errors(errors, `images.${index}.sort_order`)"/>
                                </div>

                                <div class="col-md-3">
                                    <app-input
                                        type="text"
                                        :id="`alt_text-${index}`"
                                        :label="$t('alt_text')"
                                        v-model="item.alt_text"
                                        :errors="$errors(errors, `images.${index}.alt_text`)"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" :disabled="preloader">
                            <span v-if="preloader" class="spinner-border spinner-border-sm me-2"/>
                            {{ submitText }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <media-modal
        v-if="mediaModalActive"
        modal-id="outlet-media-modal"
        @selected="handleMediaSelected"
        @close="closeMediaModalActive"/>
</template>

<style scoped lang="scss"></style>


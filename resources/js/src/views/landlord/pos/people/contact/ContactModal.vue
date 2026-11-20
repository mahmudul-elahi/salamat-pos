<script setup>
import {ref, watch} from 'vue';
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {
    SELECTED_AREAS,
    SELECTED_DISTRICTS,
    SELECTED_COUNTRIES,
    SELECTED_SHOPS, CUSTOMERS
} from "@/services/endpoints/landlord.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import {formDataAssigner} from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
    tenant: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close']);

const {
    preloader,
    pageLoader,
    formData,
    errors,
    closeModal,
    save,
} = useSubmitForm(props, emit);

const areaFetchUrl = ref(null);
const isImage = ref(false);

// -------------------
// Functions
// -------------------

const changeDistrict = (event) => {
    formData.value.area_id = null;
    formData.value.thana = '';
    formData.value.postal_code = '';
    areaFetchUrl.value = event?.id ? SELECTED_AREAS(event.id) : null;
}

const changeArea = (event) => {
    if (event) {
        formData.value.thana = event.thana;
        formData.value.postal_code = event.postcode;
    }
}

const changeCompanyLogo = (file) => {
    isImage.value = true;
    formData.value.image = file;
}

const submit = () => {
    const customData = {
        type: formData.value.type,
        name: formData.value.name || '',
        company_name: formData.value.company_name || '',
        email: formData.value.email || '',
        phone: formData.value.phone || '',
        secondary_phone: formData.value.secondary_phone || '',
        country_id: formData.value.country_id || '',
        new_district_id: formData.value.new_district_id || '',
        area_id: formData.value.area_id || '',
        thana: formData.value.thana || '',
        postal_code: formData.value.postal_code || '',
        address: formData.value.address || '',
        tenant_id: formData.value.tenant_id || ''
    };

    if (isImage.value && formData.value.image) {
        customData.image = formData.value.image;
    }

    const data = formDataAssigner(new FormData(), customData);

    save(props.selectedUrl ?? CUSTOMERS, data);
}

// const save = (url, formData, type = 'patch', event = null) => {
//     preloader.value = true;
//     errors.value = {};
//
//     const axiosCall = props.selectedUrl
//         ? Axios.post(url + '?_method=PATCH', formData, {
//             headers: {'Content-Type': 'multipart/form-data'}
//         })
//         : Axios.post(url, formData, {
//             headers: {'Content-Type': 'multipart/form-data'}
//         });
//
//     axiosCall.then((response) => {
//         afterSuccess(response, event);
//     }).catch(({response}) => {
//         if (response?.data?.errors) {
//             errors.value = response.data.errors;
//             console.error('Validation errors:', errors.value);
//         }
//         afterError(response);
//     }).finally(() => afterFinalResponse());
// }

watch(
    () => formData.value.new_district_id,
    async (newId) => {
        if (newId) {
            areaFetchUrl.value = SELECTED_AREAS(newId);

            try {
                // fetch all areas for the selected district
                const {data} = await Axios.get(areaFetchUrl.value);
                const selectedArea = data.data.find(a => a.id === formData.value.area_id);

                if (selectedArea) {
                    formData.value.thana = selectedArea.thana;
                    formData.value.postal_code = selectedArea.postcode;
                } else {
                    formData.value.thana = '';
                    formData.value.postal_code = '';
                }
            } catch (error) {
                console.error('Failed to fetch areas:', error);
            }
        } else {
            areaFetchUrl.value = null;
            formData.value.thana = '';
            formData.value.postal_code = '';
        }
    },
    {immediate: true}
);

</script>

<template>
    <app-modal :modal-id="modalId"
               modal-size="extra-large"
               :title="selectedUrl ? $t('update_supplier') : $t('add_contact')"
               :preloader="preloader"
               @submit="submit" @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <form v-else @submit.prevent="submit" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-xl-4 col-md-6">
                        <app-input type="select"
                                   label="Contact type"
                                   id="attribute"
                                   label-required
                                   :options="[{
                                       id:'customer',
                                       name:'Customer',
                                   },{
                                        id:'supplier',
                                       name:'Supplier',
                                   }]"
                                   list-value-field="name"
                                   :choose-label="$t('choose_a_contact_type')"
                                   v-model="formData.type"
                                   :errors="$errors(errors, 'type')"/>
                    </div>
                    <div class="col-xl-4 col-md-6" v-if="!tenant && formData.type === 'supplier'">
                        <app-input
                            type="advance-search-select"
                            id="tenant_id"
                            :label="$t('shop_name')"
                            label-required
                            list-value-field="name"
                            v-model="formData.tenant_id"
                            :loading-length="10"
                            :fetch-url="SELECTED_SHOPS"
                            :choose-label="$t('choose_a_shop')"
                            :disabled="!!selectedUrl"
                            :errors="$errors(errors, 'tenant_id')"/>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <app-input type="text"
                                   id="name"
                                   :label="$t('name')"
                                   label-required
                                   :placeholder="$t('name')"
                                   v-model="formData.name"
                                   :errors="$errors(errors, 'name')"/>
                    </div>
                    <div class="col-xl-4 col-md-6" v-if="formData.type === 'supplier'">
                        <app-input type="text"
                                   id="business_name"
                                   :label="$t('business_name')"
                                   label-required
                                   :placeholder="$t('business_name')"
                                   v-model="formData.business_name"
                                   :errors="$errors(errors, 'business_name')"/>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="number"
                                   id="phone"
                                   :label="$t('phone')"
                                   label-required
                                   :placeholder="$t('phone')"
                                   v-model="formData.phone"
                                   :errors="$errors(errors, 'phone')"/>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="number"
                                   id="secondary_phone"
                                   :label="$t('secondary_phone')"
                                   :placeholder="$t('secondary_phone')"
                                   v-model="formData.secondary_phone"
                                   :errors="$errors(errors, 'secondary_phone')"/>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input type="email"
                                   id="email"
                                   :label="$t('email')"
                                   :placeholder="$t('email')"
                                   v-model="formData.email"
                                   :errors="$errors(errors, 'email')"/>
                    </div>
                    <template v-if="formData.type === 'supplier'">
                        <div class="col-xl-4 col-md-6">
                            <app-input type="text"
                                       id="platform_url"
                                       :label="$t('platform_url')"
                                       :placeholder="$t('platform_url')"
                                       v-model="formData.platform_url"
                                       :errors="$errors(errors, 'platform_url')"/>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <app-input type="text"
                                       id="order_channel"
                                       :label="$t('order_channel')"
                                       :placeholder="$t('order_channel')"
                                       v-model="formData.order_channel"
                                       :errors="$errors(errors, 'order_channel')"/>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <app-input type="text"
                                       id="trade_license_number"
                                       :label="$t('trade_license_number')"
                                       :placeholder="$t('trade_license_number')"
                                       v-model="formData.trade_license_number"
                                       :errors="$errors(errors, 'trade_license_number')"/>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <app-input type="text"
                                       id="tin_number"
                                       :label="$t('tin_number')"
                                       :placeholder="$t('tin_number')"
                                       v-model="formData.tin_number"
                                       :errors="$errors(errors, 'tin_number')"/>
                        </div>
                        <!--                        <div class="col-xl-4 col-md-6">-->
                        <!--                            <Attachment-->
                        <!--                                :label="$t('business_logo')"-->
                        <!--                                id="image"-->
                        <!--                                v-model="formData.image"-->
                        <!--                                @changeFile="changeCompanyLogo($event)"-->
                        <!--                                :error="$errors(errors, 'image')"/>-->
                        <!--                        </div>-->
                    </template>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-12">
                        <h5 class="title mb-0">{{ $t('address') }} <span class="text-danger">*</span></h5>
                    </div>
                    <div class="col-xl-4 col-md-6" v-if="formData.type === 'supplier'">
                        <app-input type="advance-search-select"
                                   id="countries"
                                   :label="$t('country')"
                                   listValueField="name"
                                   list-key-field="id"
                                   v-model="formData.country_id"
                                   :loading-length="10"
                                   :fetch-url="`${SELECTED_COUNTRIES}`"
                                   :choose-label="$t('choose_a_country')"
                                   :errors="$errors(errors, 'country_id')"/>
                    </div>
                    <div class="col-xl-4 col-md-6">
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
                    <div class="col-xl-4 col-md-6" v-if="formData.new_district_id">
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
                    <div v-else class="col-xl-4 col-md-6" style="pointer-events: none; cursor: not-allowed;">
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
                            :errors="$errors(errors, 'area')"/>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input
                            type="text"
                            id="thana"
                            :label="$t('thana')"
                            readonly
                            :placeholder="$t('enter_thana')"
                            v-model="formData.thana"
                            :errors="$errors(errors, 'thana')"/>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input
                            type="text"
                            id="postal_code"
                            :label="$t('postal_code')"
                            readonly
                            :placeholder="$t('enter_postal_code')"
                            v-model="formData.postal_code"
                            :errors="$errors(errors, 'postal_code')"/>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <app-input
                            type="text"
                            id="address"
                            :label="$t('street_address')"
                            :label-required="formData.type === 'supplier'"
                            :placeholder="$t('enter_your_street_address')"
                            v-model="formData.address"
                            :errors="$errors(errors, 'address')"/>
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>

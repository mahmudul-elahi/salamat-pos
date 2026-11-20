<script setup>
import {ref} from "vue";
import {useI18n} from "vue-i18n";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import router from "@/router/index.js";
import OutletForm from "@/views/landlord/Ecommerce/outlet/OutletForm.vue";
import {OUTLETS} from "@/services/endpoints/Ecommerce.js";
import {serverDateFormat} from "@/utils/helper.js";

const {t} = useI18n();
const toast = useToast();

const formData = ref({
    name: '',
    slug: '',
    description: '',
    phone: '',
    email: '',
    address: '',
    country_id: null,
    new_district_id: null,
    area_id: null,
    postal_code: '',
    google_map_url: '',
    image: null,
    opens: '09:00',
    closes: '21:00',
    close_day: 'friday',
    status: 1,
    images: [],
});
const formatTime = (time) => {
    if (!time) return null;
    return time.slice(0, 5);
};
const errors = ref({});
const preloader = ref(false);
const areaFetchUrl = ref(null);


const submit = async () => {
    preloader.value = true;
    errors.value = {};

    try {
        const {data} = await Axios.post(OUTLETS, {
            ...formData.value,
            opens: formatTime(formData.value.opens),
            closes: formatTime(formData.value.closes),
        });
        toast.success(data.message || t('outlet_created_successfully'));
        await router.push({name: 'outlets'});
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || t('an_error_occurred'));
        }
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <outlet-form
        :form-data="formData"
        :errors="errors"
        :initial-area-fetch-url="areaFetchUrl"
        :preloader="preloader"
        :page-title="$t('add_outlet')"
        :submit-text="$t('submit')"
        @submit="submit"/>
</template>

<style scoped lang="scss"></style>


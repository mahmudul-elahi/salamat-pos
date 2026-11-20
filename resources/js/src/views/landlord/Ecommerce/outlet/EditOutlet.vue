<script setup>
import {onMounted, ref} from "vue";
import {useI18n} from "vue-i18n";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import router from "@/router/index.js";
import OutletForm from "@/views/landlord/Ecommerce/outlet/OutletForm.vue";
import {OUTLETS} from "@/services/endpoints/Ecommerce.js";
import {serverDateFormat} from "@/utils/helper.js";
import {SELECTED_AREAS} from "@/services/endpoints/landlord.js";

const props = defineProps({
    id: {type: [String, Number], required: true},
});

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
    opens: '',
    closes: '',
    close_day: 'friday',
    status: 1,
    images: [],
});

const errors = ref({});
const preloader = ref(false);
const pageLoader = ref(false);
const areaFetchUrl = ref(null);

const fetchOutlet = async () => {
    pageLoader.value = true;

    try {
        const {data} = await Axios.get(`${OUTLETS}/${props.id}`);
        Object.assign(formData.value, data, {
            status: data.status ? 1 : 0,
            images: data.images || [],
        });
        if (data.new_district_id) {
            areaFetchUrl.value = SELECTED_AREAS(data.new_district_id);
            await resolveAreaFields(data.new_district_id, data.area_id);
        }
    } catch (error) {
        toast.error(error.response?.data?.message || t('failed_to_load_data'));
    } finally {
        pageLoader.value = false;
    }
};
const resolveAreaFields = async (districtId, areaId) => {
    if (!districtId || !areaId) return;
    try {
        // per_page is deliberately generous, not the endpoint's default 10 —
        // some districts (e.g. Dhaka) have 600+ areas, and the saved area_id
        // is rarely on the first page, which silently blanked thana/postal_code.
        const {data} = await Axios.get(SELECTED_AREAS(districtId), { params: { per_page: 1000 } });
        const match = data.data.find(a => a.id == areaId);
        formData.value.thana = match?.thana ?? '';
        formData.value.postal_code = match?.postcode ?? '';
    } catch (e) {
        console.error('Failed to fetch areas:', e);
    }
};

const formatTime = (time) => {
    if (!time) return null;
    return time.slice(0, 5);
};
const submit = async () => {
    preloader.value = true;
    errors.value = {};

    try {
        const {data} = await Axios.put(`${OUTLETS}/${props.id}`, {
            ...formData.value,
            opens: formatTime(formData.value.opens),
            closes: formatTime(formData.value.closes),
        });
        toast.success(data.message || t('outlet_updated_successfully'));
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

onMounted(() => fetchOutlet());
</script>

<template>
    <div>
        <app-loader v-if="pageLoader"/>
        <outlet-form
            v-else
            :form-data="formData"
            :initial-area-fetch-url="areaFetchUrl"
            :errors="errors"
            :preloader="preloader"
            :page-title="$t('edit_outlet')"
            :submit-text="$t('update')"
            @submit="submit"/>
    </div>
</template>

<style scoped lang="scss"></style>


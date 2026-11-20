<script setup>
import { ref, onMounted } from "vue";
import { useToast } from "vue-toastification";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import Axios from "@/services/axios/index.js";
import { MY_PROFILE } from "@/services/endpoints/app.js";
import { Download } from "@iconoir/vue";

const toast = useToast();
const store = useProfileStore();
const { profileData } = storeToRefs(store);

const timezone = ref('Asia/Dhaka');
const timezones = ref([]);
const preloader = ref(false);
const loading = ref(true);

onMounted(async () => {
    timezone.value = profileData.value?.timezone ?? 'Asia/Dhaka';

    try {
        const { data } = await Axios.get(`${MY_PROFILE}/timezones`);
        timezones.value = (data.result ?? []).map(t => ({ id: t.value, name: t.label }));
    } finally {
        loading.value = false;
    }
});

const handleSubmit = async () => {
    preloader.value = true;
    try {
        const { data } = await Axios.patch(`${MY_PROFILE}/timezone`, { timezone: timezone.value });
        toast.success(data.message);
        await store.fetchMyProfile();
    } catch ({ response }) {
        toast.error(response?.data?.message ?? 'Something went wrong');
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <div class="row g-4">
        <div class="col-md-6">
            <app-input type="select" id="timezone" :label="$t('timezone')" label-required list-value-field="name"
                list-key-field="id" :options="timezones" show-search choose-label="Select timezone"
                v-model="timezone" />
        </div>

        <div class="col-12">
            <button type="button" class="btn btn-primary shadow" :disabled="preloader || loading"
                @click.prevent="handleSubmit">
                <app-button-loader v-if="preloader" />
                <Download />
                {{ $t('update') }}
            </button>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>

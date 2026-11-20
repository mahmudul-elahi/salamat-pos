<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import { CheckCircle } from '@iconoir/vue';
import Axios from '@/services/axios/index.js';
import { LANDLORD_ATTENDANCE_SETTINGS } from '@/services/endpoints/landlord.js';

const toast      = useToast();
const pageLoader = ref(false);
const preloader  = ref(false);
const errors     = ref({});

const geoProviders = [
    {
        id: 'google_map',
        name: 'Google map',
        description: 'Google Map is a map and location service provider which is integrated with this app to get the Geolocation data of an employee when Punching In. Create an account to this website for the api key which is must need to get the location data. Otherwise you will get only the IP.',
        apiKey: 'google_map_api_key',
    },
    {
        id: 'mapbox',
        name: 'Mapbox',
        description: 'Mapbox is a map and location service provider which is integrated with this app to get the Geolocation data of an employee when Punching In. Create an account to this website for the api key which is must need to get the location data. Otherwise you will get only the IP.',
        apiKey: 'mapbox_api_key',
    },
    {
        id: 'ip_geolocation',
        name: 'Ip Geolocation',
        description: 'IP Geolocation is an IP API service provider which is integrated with this app to get the IP and Geolocation data of an employee when Punching In. Create an account to this website for the api key which is must need to get the location data. Otherwise you will get only the IP.',
        apiKey: 'ip_geolocation_api_key',
    },
    {
        id: 'none',
        name: 'None',
        description: 'Geolocation is not selected. You can select a provider to get the geolocation data of an employee when Punching In.',
        apiKey: null,
    },
];

const formData = ref({
    geo_provider:           'none',
    google_map_api_key:     null,
    mapbox_api_key:         null,
    ip_geolocation_api_key: null,
});

const fetchSettings = () => {
    pageLoader.value = true;
    Axios.get(LANDLORD_ATTENDANCE_SETTINGS)
        .then(({ data }) => {
            formData.value.geo_provider           = data.geo_provider ?? 'none';
            formData.value.google_map_api_key     = data.google_map_api_key ?? null;
            formData.value.mapbox_api_key         = data.mapbox_api_key ?? null;
            formData.value.ip_geolocation_api_key = data.ip_geolocation_api_key ?? null;
        })
        .finally(() => { pageLoader.value = false; });
};

const submit = () => {
    preloader.value = true;
    errors.value = {};
    Axios.post(LANDLORD_ATTENDANCE_SETTINGS, formData.value)
        .then(({ data }) => toast.success(data.message))
        .catch(({ response }) => {
            if (response?.status === 422) errors.value = response.data.errors ?? {};
            else toast.error(response?.data?.message ?? 'Something went wrong');
        })
        .finally(() => { preloader.value = false; });
};

onMounted(fetchSettings);
</script>

<template>
    <div class="ag-wrap">
        <app-loader v-if="pageLoader" />

        <template v-else>
            <p class="ag-section-title">Choose geolocation service</p>

            <div class="geo-grid">
                <div v-for="provider in geoProviders" :key="provider.id"
                    class="geo-card"
                    :class="{
                        'geo-card--active': formData.geo_provider === provider.id,
                        'geo-card--none': provider.id === 'none',
                    }"
                    @click="formData.geo_provider = provider.id">

                    <div class="geo-card__head">
                        <span class="geo-card__name">{{ provider.name }}</span>
                        <span v-if="formData.geo_provider === provider.id && provider.id !== 'none'"
                            class="geo-card__active-badge">Active</span>
                    </div>

                    <div class="geo-card__desc-wrap">
                        <p class="geo-card__desc">{{ provider.description }}</p>
                    </div>

                    <template v-if="provider.apiKey">
                        <p class="geo-card__api-label">Api Key</p>
                        <input type="text" class="geo-card__api-input form-control form-control-sm"
                            v-model="formData[provider.apiKey]"
                            placeholder="Enter API key"
                            @click.stop />
                    </template>

                    <button type="button" class="geo-card__btn"
                        :class="formData.geo_provider === provider.id ? 'geo-card__btn--selected' : ''"
                        @click.stop="formData.geo_provider = provider.id">
                        {{ formData.geo_provider === provider.id ? 'Selected' : 'Select Provider' }}
                    </button>
                </div>
            </div>

            <div class="ag-footer">
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2"
                    :disabled="preloader" @click="submit">
                    <app-button-loader v-if="preloader" />
                    <CheckCircle v-else width="16" height="16" />
                    Save
                </button>
            </div>
        </template>
    </div>
</template>

<style scoped lang="scss">
.ag-wrap { display: flex; flex-direction: column; }

.ag-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--font-color);
    padding: 20px 28px 0;
    margin: 0;
}

.geo-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    padding: 20px 28px 28px;
}

.geo-card {
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: all .2s;
    background: var(--white);

    &:hover { border-color: #93c5fd; background: #f8fbff; }

    &--active { border-color: #3b82f6; box-shadow: 0 0 0 3px #dbeafe; }

    &--none {
        border-style: dashed;
        background: var(--dashboard-bg);
        &.geo-card--active { border-color: #3b82f6; border-style: dashed; }
    }

    &__head { display: flex; justify-content: space-between; align-items: center; }

    &__name { font-size: 0.875rem; font-weight: 700; color: var(--font-color); }

    &__active-badge {
        font-size: 0.68rem;
        font-weight: 700;
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 100px;
        padding: 2px 8px;
    }

    &__desc-wrap { background: #fffbeb; border-radius: 8px; padding: 10px 12px; flex: 1; }

    &__desc { font-size: 0.72rem; color: #92400e; line-height: 1.55; margin: 0; }

    &--none &__desc-wrap { background: var(--dashboard-bg); border: 1px solid var(--border-color); }
    &--none &__desc { color: var(--secondary-font-color, #6b7280); }

    &__api-label { font-size: 0.75rem; font-weight: 600; color: var(--secondary-font-color, #6b7280); margin: 0; }

    &__api-input { border-radius: 8px; font-size: 0.78rem; }

    &__btn {
        width: 100%;
        padding: 9px;
        border-radius: 8px;
        border: 1.5px solid var(--border-color);
        background: var(--white);
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--secondary-font-color, #6b7280);
        cursor: pointer;
        transition: all .18s;
        margin-top: auto;

        &:hover { border-color: #3b82f6; color: #1d4ed8; }

        &--selected {
            background: #3b82f6;
            border-color: #3b82f6;
            color: #fff;
            &:hover { background: #2563eb; }
        }
    }
}

.ag-footer {
    padding: 20px 28px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    background: var(--dashboard-bg);
}
</style>

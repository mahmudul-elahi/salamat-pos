<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
    PATHAO_STORES,
    PATHAO_CITIES,
    PATHAO_ZONES,
    PATHAO_AREAS,
    PATHAO_CREATE_ORDER,
} from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    sale: { type: Object, required: true },
});

const emit = defineEmits(['close']);

const toast = useToast();
const emitter = useEmitter();
const errors = ref({});
const preloader = ref(false);

const stores = ref([]);
const cities = ref([]);
const zones = ref([]);
const areas = ref([]);

const form = ref({
    store_id: null,
    recipient_name: props.sale.contact?.name || '',
    recipient_phone: props.sale.contact?.phone || '',
    recipient_address: props.sale.address || '',
    recipient_city_id: null,
    recipient_zone_id: null,
    recipient_area_id: null,
    item_type: 2,
    delivery_type: 48,
    item_quantity: 1,
    item_weight: 0.5,
    amount_to_collect: props.sale.due_amount ?? props.sale.grand_total ?? 0,
    special_instruction: '',
});

const itemTypeOptions = [
    { id: 1, name: 'Document' },
    { id: 2, name: 'Parcel' },
];

const deliveryTypeOptions = [
    { id: 48, name: 'Normal Delivery' },
    { id: 12, name: 'On Demand Delivery' },
];

// Pathao only accepts On Demand (same-day) orders before 12:30 PM or after
// 6:00 PM Bangladesh time — checked against their own server clock, not
// ours, so we can't fix the rejection, only warn before the user hits it.
// Using Intl with an explicit timeZone avoids depending on the browser's
// own local timezone, which may not be Asia/Dhaka.
const isWithinOnDemandBlockedWindow = () => {
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: 'Asia/Dhaka',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(new Date());

    const hour = Number(parts.find(p => p.type === 'hour')?.value ?? 0);
    const minute = Number(parts.find(p => p.type === 'minute')?.value ?? 0);
    const minutesSinceMidnight = hour * 60 + minute;

    return minutesSinceMidnight >= (12 * 60 + 30) && minutesSinceMidnight < (18 * 60);
};

const isOnDemandRestricted = computed(() =>
    form.value.delivery_type === 12 && isWithinOnDemandBlockedWindow()
);

const loadStores = async () => {
    try {
        const { data } = await Axios.get(PATHAO_STORES);
        stores.value = data.data || [];
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Failed to load Pathao stores');
    }
};

const loadCities = async () => {
    try {
        const { data } = await Axios.get(PATHAO_CITIES);
        cities.value = data.data || [];
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Failed to load Pathao cities');
    }
};

watch(() => form.value.recipient_city_id, async (cityId) => {
    zones.value = [];
    areas.value = [];
    form.value.recipient_zone_id = null;
    form.value.recipient_area_id = null;

    if (!cityId) return;

    try {
        const { data } = await Axios.get(PATHAO_ZONES(cityId));
        zones.value = data.data || [];
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Failed to load zones');
    }
});

watch(() => form.value.recipient_zone_id, async (zoneId) => {
    areas.value = [];
    form.value.recipient_area_id = null;

    if (!zoneId) return;

    try {
        const { data } = await Axios.get(PATHAO_AREAS(zoneId));
        areas.value = data.data || [];
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Failed to load areas');
    }
});

onMounted(() => {
    loadStores();
    loadCities();
});

const selectedStoreName = computed(() => stores.value.find(s => s.store_id === form.value.store_id)?.store_name || '');
const selectedCityName = computed(() => cities.value.find(c => c.city_id === form.value.recipient_city_id)?.city_name || '');
const selectedZoneName = computed(() => zones.value.find(z => z.zone_id === form.value.recipient_zone_id)?.zone_name || '');
const selectedAreaName = computed(() => areas.value.find(a => a.area_id === form.value.recipient_area_id)?.area_name || '');

const submit = () => {
    if (isOnDemandRestricted.value) {
        toast.error('Pathao On Demand delivery can only be booked before 12:30 PM or after 6:00 PM (Bangladesh time). Please choose Normal Delivery or try again later.');
        return;
    }

    preloader.value = true;
    errors.value = {};

    Axios.post(PATHAO_CREATE_ORDER(props.sale.id), {
        ...form.value,
        store_name: selectedStoreName.value,
        recipient_city_name: selectedCityName.value,
        recipient_zone_name: selectedZoneName.value,
        recipient_area_name: selectedAreaName.value,
    })
        .then(({ data }) => {
            toast.success(data.message || 'Pathao courier order created successfully');
            emitter.emit('reload-' + props.tableId);
            emit('close');
        })
        .catch(({ response }) => {
            if (response?.status === 422) {
                errors.value = response.data.errors || {};
            }
            toast.error(response?.data?.message || 'Failed to create Pathao courier order');
        })
        .finally(() => {
            preloader.value = false;
        });
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" title="Send via Pathao Courier" save-button-label="Send"
        :preloader="preloader" scrollable @submit="submit" @close="$emit('close')">
        <template #body>
            <div class="row g-3">
                <div class="col-md-6">
                    <app-input type="select" id="pathao_store_id" label="Store" label-required list-key-field="store_id"
                        list-value-field="store_name" v-model="form.store_id" :options="stores"
                        choose-label="Choose a store" :errors="$errors(errors, 'store_id')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text" id="pathao_recipient_name" label="Recipient Name" label-required
                        v-model="form.recipient_name" :errors="$errors(errors, 'recipient_name')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text" id="pathao_recipient_phone" label="Recipient Phone" label-required
                        v-model="form.recipient_phone" :errors="$errors(errors, 'recipient_phone')" />
                </div>
                <div class="col-md-6">
                    <app-input type="number" id="pathao_amount_to_collect" label="Amount to Collect" label-required
                        v-model="form.amount_to_collect" :errors="$errors(errors, 'amount_to_collect')" />
                </div>
                <div class="col-md-12">
                    <app-input type="textarea" id="pathao_recipient_address" label="Recipient Address" label-required
                        v-model="form.recipient_address" :errors="$errors(errors, 'recipient_address')" />
                </div>
                <div class="col-md-4">
                    <app-input type="select" id="pathao_city" label="City" label-required list-key-field="city_id"
                        show-search list-value-field="city_name" v-model="form.recipient_city_id" :options="cities"
                        choose-label="Choose a city" :errors="$errors(errors, 'recipient_city_id')" />
                </div>
                <div class="col-md-4">
                    <app-input type="select" id="pathao_zone" label="Zone" label-required list-key-field="zone_id"
                        list-value-field="zone_name" v-model="form.recipient_zone_id" show-search :options="zones"
                        choose-label="Choose a zone" :errors="$errors(errors, 'recipient_zone_id')" />
                </div>
                <div class="col-md-4">
                    <app-input type="select" id="pathao_area" label="Area" list-key-field="area_id"
                        list-value-field="area_name" show-search v-model="form.recipient_area_id" :options="areas"
                        choose-label="Choose an area" :errors="$errors(errors, 'recipient_area_id')" />
                </div>
                <div class="col-md-4">
                    <app-input type="select" id="pathao_item_type" label="Item Type" label-required list-key-field="id"
                        list-value-field="name" v-model="form.item_type" :options="itemTypeOptions"
                        choose-label="Choose item type" :errors="$errors(errors, 'item_type')" />
                </div>
                <div class="col-md-4">
                    <app-input type="select" id="pathao_delivery_type" label="Delivery Type" label-required
                        list-key-field="id" list-value-field="name" v-model="form.delivery_type"
                        :options="deliveryTypeOptions" choose-label="Choose delivery type"
                        :errors="$errors(errors, 'delivery_type')" />
                </div>
                <div class="col-md-12" v-if="isOnDemandRestricted">
                    <div class="alert alert-warning mb-0">
                        Pathao On Demand (same-day) delivery can only be booked before 12:30 PM or after 6:00 PM
                        (Bangladesh time) — this is Pathao's own restriction. Please choose Normal Delivery, or try
                        again after 6:00 PM.
                    </div>
                </div>
                <div class="col-md-4">
                    <app-input type="number" id="pathao_item_weight" label="Item Weight (kg)" label-required
                        v-model="form.item_weight" :errors="$errors(errors, 'item_weight')" />
                </div>
                <div class="col-md-6">
                    <app-input type="number" id="pathao_item_quantity" label="Item Quantity" label-required
                        v-model="form.item_quantity" :errors="$errors(errors, 'item_quantity')" />
                </div>
                <div class="col-md-12">
                    <app-input type="textarea" id="pathao_special_instruction" label="Special Instruction"
                        v-model="form.special_instruction" :errors="$errors(errors, 'special_instruction')" />
                </div>
            </div>
        </template>
    </app-modal>
</template>

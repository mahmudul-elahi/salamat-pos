<script setup>
import { ref, onMounted, watch } from 'vue';
import { STEADFAST_CREATE_ORDER, STEADFAST_DISTRICTS, STEADFAST_THANAS } from '@/services/endpoints/landlord.js';
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

const deliveryTypeOptions = [
    { id: 0, name: 'Home Delivery' },
    { id: 1, name: 'Point Delivery' },
];

const districts = ref([]);
const thanas = ref([]);
const districtId = ref(null);
const thanaId = ref(null);

const form = ref({
    recipient_name: props.sale.contact?.name || '',
    recipient_phone: props.sale.contact?.phone || '',
    recipient_alternative_phone: '',
    recipient_email: '',
    recipient_address: props.sale.address || '',
    recipient_district: '',
    recipient_thana: '',
    delivery_type: 0,
    amount_to_collect: props.sale.due_amount ?? props.sale.grand_total ?? 0,
    item_description: '',
    item_weight: 0,
    is_exchange: false,
    special_instruction: '',
});

const loadDistricts = async () => {
    try {
        const { data } = await Axios.get(STEADFAST_DISTRICTS);
        districts.value = data.data || [];
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Failed to load districts');
    }
};

onMounted(() => {
    loadDistricts();
});

watch(districtId, async (newDistrictId) => {
    thanas.value = [];
    thanaId.value = null;
    form.value.recipient_thana = '';
    form.value.recipient_district = districts.value.find(d => d.id === newDistrictId)?.name || '';

    if (!newDistrictId) return;

    try {
        const { data } = await Axios.get(STEADFAST_THANAS(newDistrictId));
        thanas.value = data.data || [];
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Failed to load thanas');
    }
});

watch(thanaId, (newThanaId) => {
    form.value.recipient_thana = thanas.value.find(t => t.id === newThanaId)?.name || '';
});

const submit = () => {
    preloader.value = true;
    errors.value = {};

    Axios.post(STEADFAST_CREATE_ORDER(props.sale.id), form.value)
        .then(({ data }) => {
            toast.success(data.message || 'Steadfast courier order created successfully');
            emitter.emit('reload-' + props.tableId);
            emit('close');
        })
        .catch(({ response }) => {
            if (response?.status === 422) {
                errors.value = response.data.errors || {};
            }
            toast.error(response?.data?.message || 'Failed to create Steadfast courier order');
        })
        .finally(() => {
            preloader.value = false;
        });
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" title="Send via Steadfast Courier" save-button-label="Send"
        :preloader="preloader" scrollable @submit="submit" @close="$emit('close')">
        <template #body>
            <div class="row g-3">
                <div class="col-md-12">
                    <app-input type="radio" id="steadfast_delivery_type" v-model="form.delivery_type"
                        list-key-field="id" list-value-field="name" :options="deliveryTypeOptions" form-check-inline />
                </div>
                <div class="col-md-6">
                    <app-input type="text" id="steadfast_recipient_phone" label="Phone#" label-required
                        placeholder="Type Phone Number" v-model="form.recipient_phone"
                        :errors="$errors(errors, 'recipient_phone')" />
                </div>
                <div class="col-md-6">
                    <app-input type="number" id="steadfast_amount_to_collect" label="COD Amount" label-required
                        placeholder="Type Cash on Delivery Amount" v-model="form.amount_to_collect"
                        :errors="$errors(errors, 'amount_to_collect')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text" id="steadfast_recipient_name" label="Name" label-required
                        placeholder="Type Recipient Name" v-model="form.recipient_name"
                        :errors="$errors(errors, 'recipient_name')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text" id="steadfast_recipient_alternative_phone" label="Alternative Phone"
                        placeholder="Type Alternative Phone" v-model="form.recipient_alternative_phone"
                        :errors="$errors(errors, 'recipient_alternative_phone')" />
                </div>
                <div class="col-md-12">
                    <app-input type="textarea" id="steadfast_recipient_address" label="Address" label-required
                        placeholder="Type Address" v-model="form.recipient_address"
                        :errors="$errors(errors, 'recipient_address')" />
                </div>
                <div class="col-md-6">
                    <app-input type="select" id="steadfast_recipient_district" label="District" list-key-field="id"
                        list-value-field="name" v-model="districtId" show-search :options="districts"
                        choose-label="Choose a district" :errors="$errors(errors, 'recipient_district')" />
                </div>
                <div class="col-md-6">
                    <app-input type="select" id="steadfast_recipient_thana" label="Thana" list-key-field="id"
                        list-value-field="name" v-model="thanaId" show-search :options="thanas"
                        choose-label="Choose a thana" :errors="$errors(errors, 'recipient_thana')" />
                </div>
                <div class="col-md-6">
                    <app-input type="email" id="steadfast_recipient_email" label="Recipient Email"
                        placeholder="Type Recipient Email" v-model="form.recipient_email"
                        :errors="$errors(errors, 'recipient_email')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text" id="steadfast_item_description" label="Item Description"
                        placeholder="Type Item description (max. 400 Chars)" maxlength="400"
                        v-model="form.item_description" :errors="$errors(errors, 'item_description')" />
                </div>
                <div class="col-md-12">
                    <app-input type="textarea" id="steadfast_special_instruction" label="Note"
                        placeholder="Type Note (max. 400 Chars)" maxlength="400" v-model="form.special_instruction"
                        :errors="$errors(errors, 'special_instruction')" />
                </div>
                <div class="col-md-6">
                    <app-input type="number" id="steadfast_item_weight" label="Weight (KG)" placeholder="0"
                        v-model="form.item_weight" :errors="$errors(errors, 'item_weight')" />
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="steadfast_is_exchange"
                            v-model="form.is_exchange" />
                        <label class="form-check-label" for="steadfast_is_exchange">Exchange</label>
                    </div>
                </div>
            </div>
        </template>
    </app-modal>
</template>

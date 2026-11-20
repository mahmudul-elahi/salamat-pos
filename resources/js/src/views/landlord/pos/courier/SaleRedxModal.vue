<script setup>
import { ref, onMounted, watch } from 'vue';
import { REDX_CREATE_ORDER, REDX_AREAS, REDX_STORES } from '@/services/endpoints/landlord.js';
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

// REDX has no district/thana split like Steadfast — one flat area list, where
// each entry already carries its district and division.
const areas = ref([]);
const stores = ref([]);
const areaId = ref(null);

const form = ref({
    recipient_name: props.sale.contact?.name || '',
    recipient_phone: props.sale.contact?.phone || '',
    recipient_address: props.sale.address || '',
    delivery_area_id: null,
    delivery_area: '',
    pickup_store_id: null,
    amount_to_collect: props.sale.due_amount ?? props.sale.grand_total ?? 0,
    value: props.sale.grand_total ?? 0,
    item_description: '',
    item_weight: 0.5,
    is_closed_box: true,
    special_instruction: '',
});

const load = async (url, target, label) => {
    try {
        const { data } = await Axios.get(url);
        target.value = data.data || [];
    } catch (error) {
        toast.error(error?.response?.data?.message || `Failed to load ${label}`);
    }
};

onMounted(async () => {
    await Promise.all([
        load(REDX_AREAS, areas, 'areas'),
        load(REDX_STORES, stores, 'pickup stores'),
    ]);

    // Most merchants have exactly one pickup point; preselect it.
    if (stores.value.length === 1) {
        form.value.pickup_store_id = stores.value[0].id;
    }
});

// The API wants both the id and the name, so keep them in step.
watch(areaId, (id) => {
    const area = areas.value.find((item) => item.id === id);

    form.value.delivery_area_id = area?.id ?? null;
    form.value.delivery_area = area?.name ?? '';
});

const submit = () => {
    preloader.value = true;
    errors.value = {};

    Axios.post(REDX_CREATE_ORDER(props.sale.id), form.value)
        .then(({ data }) => {
            toast.success(data.message || 'REDX courier order created successfully');
            emitter.emit('reload-' + props.tableId, true);
            emit('close');
        })
        .catch(({ response }) => {
            if (response?.status === 422) {
                errors.value = response.data.errors || {};
            }
            toast.error(response?.data?.message || 'Failed to create REDX courier order');
        })
        .finally(() => {
            preloader.value = false;
        });
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" title="Send via REDX Courier" save-button-label="Send"
        :preloader="preloader" scrollable @submit="submit" @close="$emit('close')">
        <template #body>
            <div class="row g-3">
                <div class="col-md-6">
                    <app-input type="text" id="redx_recipient_name" label="Name" label-required
                        placeholder="Type Recipient Name" v-model="form.recipient_name"
                        :errors="$errors(errors, 'recipient_name')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text" id="redx_recipient_phone" label="Phone#" label-required
                        placeholder="Type Phone Number" v-model="form.recipient_phone"
                        :errors="$errors(errors, 'recipient_phone')" />
                </div>
                <div class="col-md-12">
                    <app-input type="textarea" id="redx_recipient_address" label="Address" label-required
                        placeholder="Type Address" v-model="form.recipient_address"
                        :errors="$errors(errors, 'recipient_address')" />
                </div>
                <div class="col-md-6">
                    <app-input type="select" id="redx_delivery_area" label="Delivery Area" label-required
                        list-key-field="id" list-value-field="name" v-model="areaId" show-search :options="areas"
                        choose-label="Choose a delivery area" :errors="$errors(errors, 'delivery_area_id')" />
                </div>
                <div class="col-md-6">
                    <app-input type="select" id="redx_pickup_store" label="Pickup Store" list-key-field="id"
                        list-value-field="name" v-model="form.pickup_store_id" show-search :options="stores"
                        choose-label="Choose a pickup store" :errors="$errors(errors, 'pickup_store_id')" />
                </div>
                <div class="col-md-6">
                    <app-input type="number" id="redx_amount_to_collect" label="COD Amount" label-required
                        placeholder="Type Cash on Delivery Amount" v-model="form.amount_to_collect"
                        :errors="$errors(errors, 'amount_to_collect')" />
                </div>
                <div class="col-md-6">
                    <app-input type="number" id="redx_value" label="Parcel Value" placeholder="0" v-model="form.value"
                        :errors="$errors(errors, 'value')" />
                </div>
                <div class="col-md-6">
                    <app-input type="text" id="redx_item_description" label="Item Description"
                        placeholder="Type Item description" maxlength="255" v-model="form.item_description"
                        :errors="$errors(errors, 'item_description')" />
                </div>
                <div class="col-md-6">
                    <app-input type="number" id="redx_item_weight" label="Weight (KG)" placeholder="0.5"
                        v-model="form.item_weight" :errors="$errors(errors, 'item_weight')" />
                </div>
                <div class="col-md-12">
                    <app-input type="textarea" id="redx_special_instruction" label="Note" placeholder="Type Note"
                        maxlength="400" v-model="form.special_instruction"
                        :errors="$errors(errors, 'special_instruction')" />
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="redx_is_closed_box"
                            v-model="form.is_closed_box" />
                        <label class="form-check-label" for="redx_is_closed_box">Closed Box</label>
                    </div>
                </div>
            </div>
        </template>
    </app-modal>
</template>

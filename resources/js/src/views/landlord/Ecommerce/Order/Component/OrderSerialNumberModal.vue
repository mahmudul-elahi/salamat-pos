<script setup>
import { ref, watch } from 'vue';
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import { SELECTED_SHOPS } from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    order: Object,
});

const emit = defineEmits(['close', 'submitted']);

const preloader = ref(false);
const serialInput = ref({});    // { [itemId]: [{ serial_number, tenant_id }, ...] }
const giftSerialInput = ref({}); // { [itemId]: [{ serial_number, tenant_id }, ...] }
const toast = useToast();
const errors = ref({});

watch(() => props.order, (order) => {
    if (order && order.items) {
        const initialSerials = {};
        const initialGiftSerials = {};
        order.items.forEach((item) => {
            if (item.id) {
                const qty = item.quantity || 1;
                initialSerials[item.id] = Array.from({ length: qty }, () => ({
                    serial_number: '',
                    tenant_id: null,
                }));
                if (item.gift_product_variant) {
                    initialGiftSerials[item.id] = Array.from({ length: qty }, () => ({
                        serial_number: '',
                        tenant_id: null,
                    }));
                }
            }
        });
        serialInput.value = initialSerials;
        giftSerialInput.value = initialGiftSerials;
    }
}, { immediate: true, deep: true });

const submit = async () => {
    errors.value = {};
    preloader.value = true;
    try {
        const orders = Object.entries(serialInput.value).map(([itemId, serials]) => ({
            order_list_id: parseInt(itemId),
            serials: serials.map((s) => ({
                serial_number: s.serial_number,
                tenant_id: s.tenant_id,
            })),
        }));

        const giftOrders = Object.entries(giftSerialInput.value)
            .filter(([, serials]) => serials.length > 0)
            .map(([itemId, serials]) => ({
                order_list_id: parseInt(itemId),
                serials: serials.map((s) => ({
                    serial_number: s.serial_number,
                    tenant_id: s.tenant_id,
                })),
            }));

        await Axios.post(`admin/ecommerce/orders/order-status/${props.order.id}/approved`, {
            orders,
            ...(giftOrders.length > 0 ? { gift_orders: giftOrders } : {}),
        });

        toast.success("Serial numbers submitted and order approved successfully.");
        preloader.value = false;
        emit('submitted');
        emit('close');
    } catch (e) {
        preloader.value = false;

        if (e.response?.status === 422) {
            const apiErrors = e.response.data.errors;
            const mappedErrors = {};

            Object.keys(apiErrors).forEach((key) => {
                // orders.0.serials.1.serial_number
                const match = key.match(/^orders\.(\d+)\.serials\.(\d+)\.(.+)$/);
                if (match) {
                    const orderIndex = parseInt(match[1]);
                    const serialIndex = parseInt(match[2]);
                    const field = match[3];
                    const itemIds = Object.keys(serialInput.value);
                    const itemId = itemIds[orderIndex];
                    if (itemId) {
                        const clean = apiErrors[key].map((m) => m.replace(/_/g, ' '));
                        mappedErrors[`${field}_${itemId}_${serialIndex}`] = clean;
                    }
                }

                // gift_orders.0.serials.1.serial_number
                const giftMatch = key.match(/^gift_orders\.(\d+)\.serials\.(\d+)\.(.+)$/);
                if (giftMatch) {
                    const giftIndex = parseInt(giftMatch[1]);
                    const serialIndex = parseInt(giftMatch[2]);
                    const field = giftMatch[3];
                    const giftItemIds = Object.keys(giftSerialInput.value);
                    const itemId = giftItemIds[giftIndex];
                    if (itemId) {
                        const clean = apiErrors[key].map((m) => m.replace(/_/g, ' '));
                        mappedErrors[`gift_${field}_${itemId}_${serialIndex}`] = clean;
                    }
                }
            });

            errors.value = mappedErrors;
            toast.error("Please fix the errors before submitting.");
        } else {
            toast.error(e.response?.data?.message ?? "An error occurred while submitting.");
        }
    }
};

const closeModal = () => {
    errors.value = {};
    emit('close');
}
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :preloader="preloader" :title="$t('serial_number')"
        @submit="submit" @close="closeModal">
        <template v-slot:body>
            <div class="row">
                <template v-for="item in order.items" :key="item.id">

                    <!-- Product header -->
                    <div class="col-12 mb-2" :class="{ 'mt-3': true }">
                        <div style="background: #f5f7fa; border-radius: 6px; padding: 8px 12px; font-weight: 600; font-size: 0.9rem;">
                            {{ item.variant?.product?.product_name }}
                            <span class="text-muted" style="font-weight: 400; font-size: 0.8rem;">
                                ({{ item.variant?.sku }}) — Qty: {{ item.quantity }}
                            </span>
                        </div>
                    </div>

                    <!-- Serial inputs per quantity -->
                    <template v-if="serialInput[item.id]" v-for="(serial, sIdx) in serialInput[item.id]" :key="sIdx">
                        <div class="col-md-6 mb-3">
                            <app-input type="text" :id="`serial-${item.id}-${sIdx}`"
                                :label="`Serial #${sIdx + 1}`"
                                v-model="serial.serial_number" :placeholder="$t('enter_serial_number')"
                                :errors="$errors(errors, `serial_number_${item.id}_${sIdx}`)" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <app-input type="advance-search-select" :id="`shop-${item.id}-${sIdx}`"
                                :label="`${$t('warehouse_or_store_name')} #${sIdx + 1}`"
                                label-required list-value-field="name"
                                v-model="serial.tenant_id" :loading-length="10" :fetch-url="SELECTED_SHOPS"
                                :choose-label="$t('choose_a_shop')"
                                :errors="$errors(errors, `tenant_id_${item.id}_${sIdx}`)" />
                        </div>
                    </template>

                    <!-- Gift Item Serials -->
                    <template v-if="item.gift_product_variant && giftSerialInput[item.id]">
                        <div class="col-12 mb-2">
                            <div style="border-left: 3px solid #f39233; padding: 6px 10px; background: #fff8f0; border-radius: 4px;">
                                <div style="font-size: 0.75rem; color: #888; margin-bottom: 2px;">
                                    Gift with <strong>{{ item.variant?.product?.product_name }}</strong>
                                </div>
                                <div style="font-size: 0.85rem; font-weight: 600; color: #333;">
                                    🎁 {{ item.gift_product_variant.product?.product_name }}
                                    <span style="font-weight: 400; color: #666;">({{ item.quantity }} serial{{ item.quantity > 1 ? 's' : '' }} required)</span>
                                </div>
                            </div>
                        </div>
                        <template v-for="(giftSerial, gIdx) in giftSerialInput[item.id]" :key="gIdx">
                            <div class="col-md-6 mb-3">
                                <app-input type="text" :id="`gift-serial-${item.id}-${gIdx}`"
                                    :label="`Gift #${gIdx + 1} Serial — ${item.gift_product_variant.product?.product_name}`"
                                    v-model="giftSerial.serial_number" :placeholder="$t('enter_serial_number')"
                                    :errors="$errors(errors, `gift_serial_number_${item.id}_${gIdx}`)" />
                            </div>
                            <div class="col-md-6 mb-3">
                                <app-input type="advance-search-select" :id="`gift-shop-${item.id}-${gIdx}`"
                                    :label="`Gift #${gIdx + 1} Warehouse`"
                                    label-required list-value-field="name"
                                    v-model="giftSerial.tenant_id" :loading-length="10" :fetch-url="SELECTED_SHOPS"
                                    :choose-label="$t('choose_a_shop')"
                                    :errors="$errors(errors, `gift_tenant_id_${item.id}_${gIdx}`)" />
                            </div>
                        </template>
                    </template>

                </template>
            </div>
        </template>
    </app-modal>
</template>

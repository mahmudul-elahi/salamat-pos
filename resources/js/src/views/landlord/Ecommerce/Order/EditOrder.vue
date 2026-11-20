<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import {
    SELECTED_CUSTOMERS, SELECTED_SHOPS, SALES, SALE_SEARCH_PRODUCTS, SELECTED_PAYMENT_METHODS
} from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { serverDateFormat } from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
import { useToast } from "vue-toastification";
import { Page, Search, Trash } from '@iconoir/vue';
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import router from "@/router/index.js";
import { formDataAssigner } from "@/utils/helper.js";
import { ORDERS } from '@/services/endpoints/Ecommerce';

const store = useProfileStore();
const { profileData } = storeToRefs(store);

const { canAccess } = usePermission();
const formData = ref({
    discount_amount: '',
    date: new Date(),
    additional_charge: 0,
    additional_charge_name: ''
});
const props = defineProps({
    id: {},
});

const toast = useToast();
const searchQuery = ref("");
const products = ref([]);
const { isModalActive, closeModal } = useOpenModal()
const productList = ref([])
const selectedProductIndex = ref(null);
const openNoteModal = (index) => {
    selectedProductIndex.value = index;
    isModalActive.value = true;
};
const removeProduct = (index) => {
    productList.value.splice(index, 1);
}

// blocked invalid keys
const blockInvalidKeys = (e) => {
    const invalidKeys = ['e', 'E', '+', '-', 'Minus', 'Equal'];
    if (
        invalidKeys.includes(e.key) ||
        invalidKeys.includes(e.code)
    ) {
        e.preventDefault();
    }
};
const blockNegativePaste = (e) => {
    const pasted = (e.clipboardData || window.clipboardData).getData('text');
    if (/[eE\-+]/.test(pasted)) {
        e.preventDefault();
    }
};
const changeDiscountType = (event, product) => {
    product.discount_type = event.target.name;
    product.discount_amount = '';
}

const payments = ref([
    { payment_method_id: null, amount: 0 }
]);

const addPayment = () => {
    payments.value.push({ payment_method_id: null, amount: 0 });
};

const removePayment = (index) => {
    payments.value.splice(index, 1);
};
const availablePaymentMethods = (currentIndex) => {
    if (!paymentMethodList.value?.length) return [];
    return paymentMethodList.value.filter(pm => {
        return !payments.value.some((p, i) => i !== currentIndex && p.payment_method_id === pm.id);
    });
};
const totalReceivedAmount = computed(() => {
    return payments.value.reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const validateDiscountAmount = (product) => {
    let value = Number(product.discount_value) || 0;

    if (product.discount_type === 'fixed') {
        product.discount_value = Math.min(value, product.price);
    }

    if (product.discount_type === 'percent') {
        product.discount_value = Math.min(value, 100);
    }
};
// Function to calculate total per product considering discount
const calculateProductTotal = (product) => {
    const price = Number(product.purchase_price) || 0;
    console.log(price);

    const qty = Number(product.quantity) || 1;
    let total = price * qty;

    if (product.discount_type === 'fixed') {
        total -= Number(product.discount_value || 0);
    }

    if (product.discount_type === 'percent') {
        total -= total * (Number(product.discount_value || 0) / 100);
    }

    total = Math.max(total, 0);
    return Math.round(total * 100) / 100;
};


// Subtotal for all products
const subtotal = computed(() =>
    productList.value.reduce((sum, p) => sum + Number(p.purchase_price) * Number(p.quantity), 0)
);

// Function to calculate discount amount for a single product
const calculateDiscount = (product) => {
    const price = Number(product.purchase_price) || 0;
    const qty = Number(product.quantity) || 1;
    let discount = 0;

    if (product.discount_type === 'fixed') {
        discount = Number(product.discount_value || 0) * qty;
    } else if (product.discount_type === 'percent') {
        discount = price * (Number(product.discount_value || 0) / 100) * qty;
    }

    return discount;
};

// Total discount for all products
const totalDiscount = computed(() => {
    const total = productList.value.reduce((sum, p) => sum + calculateDiscount(p), 0);
    return Number(total.toFixed(2));
});
// Subtotal after discount

const discountedSubtotal = computed(() =>
    productList.value.reduce(
        (sum, p) => sum + calculateProductTotal(p),
        0
    )
);


// Clamp order_tax between 0 and 100
const clampedOrderTax = computed(() => {
    const tax = Number(formData.value.order_tax) || 0;
    return Math.min(Math.max(tax, 0), 100);
});
const changeOrderTax = (event) => {
    let value = Number(event.target.value) || 0;

    // Clamp between 0 and 100
    if (value < 0) value = 0;
    if (value > 100) value = 100;

    formData.value.order_tax = value;
    event.target.value = value; // update input box
};

// Calculate tax amount based on discounted subtotal
const taxAmount = computed(() => {
    return (discountedSubtotal.value * clampedOrderTax.value) / 100;
});

// Additional Charge
const additionalChargeAmount = computed(() => {
    return Number(formData.value.additional_charge) || 0;
});

// Shipping
const shippingAmount = computed(() => {
    if (Number(formData.value.shipping_cost)) {
        return Number(formData.value.shipping_cost);
    }
    return 0;
});

// Grand total
const grandTotal = computed(() => {
    return discountedSubtotal.value + taxAmount.value + shippingAmount.value + additionalChargeAmount.value;
});
// Received Amount
const totalDeuAmount = computed(() => {
    const totalReceived = payments.value.reduce((sum, p) => sum + Number(p.amount || 0), 0);
    const due = grandTotal.value - totalReceived;
    return due < 0 ? 0 : due;
});

const errors = ref({});
const preloader = ref(false);
const pageLoader = ref(false);
const isCustomerModalActive = ref(false)

const fetchOrderData = async () => {
    pageLoader.value = true;
    try {
        const { data } = await Axios.get(`${ORDERS}/${props.id}`);

        const order = data.order;

        formData.value = {
            ...formData.value,
            customer_id: order.customer_id,
            date: order.created_at,
            discount_amount: order.discount_amount || 0,
            shipping_cost: order.shipping_charge || 0,
        };

        productList.value = (order.items ?? []).map(item => ({
            id: item.id,
            product_id: item.product_id,
            product_variant_id: item.product_variant_id,
            name: item.product?.product_name,
            sku: item.variant?.sku || item.product?.sku,
            price: Number(item.product_price),
            quantity: Number(item.quantity),
            purchase_price: Number(item.product_price),
            price: Number(item.final_price),
            discount_type: item.discount_type,
            discount_value: Number(item.discount_value),
            discount_amount: Number(item.discount_amount),
            attribute_values: item.variant?.attribute_values
                ?.map(v => v.value)
                .join('/'),
            note: item.note || '',
            status: item.status === '1' ? '' : 'sale',
        }));

    } catch (err) {
        console.error(err);
        toast.error("Failed to load order data");
    } finally {
        pageLoader.value = false;
    }
};


const submit = async () => {
    if (!productList.value.length) {
        toast.error("Please add at least one product");
        return;
    }
    const invalidPayment = payments.value.find(
        (p) => Number(p.amount) > 0 && !p.payment_method_id
    );

    if (invalidPayment) {
        toast.error("Please select a payment method for each entered amount");
        preloader.value = false;
        return;
    }
    preloader.value = true;
    errors.value = {};
    const calculateDiscountAmount = (product) => {
        const price = Number(product.purchase_price) || 0;
        const qty = Number(product.quantity) || 1;
        let discount = 0;

        if (product.discount_type === 'fixed') {
            discount = Number(product.discount_value || 0) * qty;
        } else if (product.discount_type === 'percent') {
            discount = price * (Number(product.discount_value || 0) / 100) * qty;
        }

        return Math.round(discount * 100) / 100; // rounding to 2 decimals
    };

    // Build plain object first
    const payload = {
        ...formData.value,
        date: formData.value.date ? serverDateFormat(formData.value.date) : '',
        discount_type: formData.value.discount_type || '',
        discount_amount: formData.value.discount_amount || 0,
        order_tax: formData.value.order_tax || 0,
        shipping_cost: formData.value.shipping_cost || 0,
        additional_charge_name: formData.value.additional_charge_name || null,
        additional_charge: formData.value.additional_charge || 0,
        sub_total: subtotal.value,
        grand_total: grandTotal.value,
        payments: payments.value
            .filter(p => p.payment_method_id && Number(p.amount) > 0)
            .map((payment) => ({
                payment_method_id: payment.payment_method_id,
                amount: payment.amount,
            })),
        items: productList.value.map((product) => {
            return {
                product_id: product.product_id,
                variant_id: product.product_variant_id,
                // serial_number: product.serial_number,
                quantity: product.quantity,
                unit_price: product.price,
                discount_type: product.discount_type || '',
                discount_value: product.discount_value || 0,
                discount_amount: calculateDiscountAmount(product),
                note: product.note,
            }
        })
    };

    const data = formDataAssigner(new FormData(), payload);
    data.append('_method', 'PATCH');

    try {
        await Axios.post(`${ORDERS}/${props.id}`, data, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        toast.success('Order discount updated successfully');
        await router.push({ name: 'orders' });
    } catch (error) {
        if (error.response?.status === 422) errors.value = error.response.data.errors;
        else toast.error(error.response?.data?.message || 'Error submitting form');
    } finally {
        preloader.value = false;
    }
};

const paymentMethodList = ref([])
const getPaymentMethod = () => {
    Axios.get(SELECTED_PAYMENT_METHODS).then((response) => {
        paymentMethodList.value = response.data.data
    })
}
const validatePaymentAmount = (payment, index) => {
    const remaining = grandTotal.value - payments.value.reduce((sum, p, i) => {
        if (i !== index) return sum + Number(p.amount || 0);
        return sum;
    }, 0);

    if (payment.amount > remaining) {
        payment.amount = remaining;
    }
};

watch(
    payments,
    (newPayments) => {
        const total = newPayments.reduce((sum, p) => sum + Number(p.amount || 0), 0);

        // If total received exceeds the grand total
        if (total > grandTotal.value) {
            let excess = total - grandTotal.value;

            // Start subtracting the excess from the last payment backwards
            for (let i = newPayments.length - 1; i >= 0 && excess > 0; i--) {
                const payment = newPayments[i];
                const amount = Number(payment.amount || 0);

                if (amount > 0) {
                    const newAmount = Math.max(0, amount - excess);
                    excess -= amount - newAmount;
                    payment.amount = newAmount;
                }
            }
        }
    },
    { deep: true }
);


onMounted(() => {
    getPaymentMethod()
    fetchOrderData()
})
const getItemsError = (field, index) => errors.value[`items.${index}.${field}`]?.[0] || null;

const customerPhoneNumber = ref('')
const searchCustomer = (event) => {
    customerPhoneNumber.value = event;
}
const isCustomerCloseModal = () => {
    isCustomerModalActive.value = false
    customerPhoneNumber.value = ''
}
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('update_sale')" />
        <div class="card">
            <app-laoder v-if="pageLoader" />
            <div class="card-body" v-else>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex gap-2 align-items-end">
                            <div class="flex-grow-1">
                                <app-input
                                    type="advance-search-select"
                                    id="customer"
                                    :label="$t('customer')"
                                    label-required
                                    list-value-field="name"
                                    v-model="formData.customer_id"
                                    :loading-length="10"
                                    :fetch-url="SELECTED_CUSTOMERS"
                                    :choose-label="$t('choose_a_customer')"
                                    :errors="$errors(errors, 'customer_id')"
                                    @searchValue="searchCustomer($event)" />
                            </div>
                            <button class="btn btn-primary" v-if="canAccess('create_customers')"
                                @click="isCustomerModalActive = true">{{ $t('add_customer') }}
                            </button>

                        </div>
                    </div>
                    <div class="col-md-6" v-if="!profileData?.tenant">
                        <app-input
                            type="advance-search-select"
                            id="shop_id"
                            :label="$t('warehouse_or_store_name')"
                            label-required
                            list-value-field="name"
                            v-model="formData.tenant_id"
                            :loading-length="10"
                            :fetch-url="SELECTED_SHOPS"
                            :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, 'tenant_id')" />
                    </div>
                    <div class="col-md-6">
                        <app-input
                            type="date"
                            id="name"
                            :label="$t('date')"
                            label-required
                            v-model="formData.date"
                            :errors="$errors(errors, 'date')"
                            auto-apply
                            :enable-time-picker="false"
                            :placeholder="$t('dd/mm/yy')" />
                    </div>
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table purchase-table">
                                <thead>
                                    <tr>
                                        <th>{{ $t('sl') }}</th>
                                        <th>{{ $t('product_name') }}</th>
                                        <th>{{ $t('sku') }}</th>
                                        <th>{{ $t('p_price') }}</th>
                                        <th>{{ $t('mrp') }}</th>
                                        <th>{{ $t('quantity') }}</th>
                                        <th>{{ $t('discount') }}</th>
                                        <th>{{ $t('total_price') }}</th>
                                        <th>{{ $t('action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="productList.length" v-for="(product, index) in productList" :key="product.id" :class="{ 'table-disabled-row': product.status === 'sale' }">
                                        <td>{{ index + 1 }}</td>
                                        <td>
                                            {{ product.name }} <span v-if="product.status === 'sale'" class="badge-square-light bg-danger">Sold</span>
                                            <div v-if="product.attribute_values" class="badge-square-light bg-success">
                                                {{ product.attribute_values }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-square-light bg-success">{{ product.sku }}</span>
                                        </td>
                                        <td>
                                            {{ product.purchase_price }}
                                        </td>
                                        <td>
                                            <app-input type="number"
                                                class="w-70"
                                                :id="`mrp_${index}`"
                                                v-model="product.price"
                                                placeholder="MRP"
                                                @keydown="blockInvalidKeys"
                                                @paste="blockNegativePaste"
                                                min="1"
                                                :errors="getItemsError('price', index)" />

                                        </td>
                                        <td>
                                            <b>{{ product.quantity }}</b>
                                        </td>
                                        <td>

                                            <div class="d-flex">
                                                <div class="form-check">
                                                    <label class="form-check-label mx-2" :for="`${index}_fixed`">
                                                        {{ $t("fixed") }}
                                                    </label>
                                                    <input :id="`${index}_fixed`"
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        :checked="product.discount_type === 'fixed'"
                                                        @click="changeDiscountType($event, product)"
                                                        name="fixed">
                                                </div>

                                                <div class="form-check">
                                                    <label class="form-check-label mx-2"
                                                        :for="`${index}_percent`">{{ $t("percent") }}</label>
                                                    <input :id="`${index}_percent`"
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        :checked="product.discount_type === 'percent'"
                                                        @click="changeDiscountType($event, product)"
                                                        name="percent">
                                                </div>

                                            </div>


                                            <app-input type="number"
                                                class="w-70"
                                                :id="`discount_${index}`"
                                                v-model="product.discount_value"
                                                placeholder="Amount"
                                                @keydown="blockInvalidKeys"
                                                @paste="blockNegativePaste"
                                                @input="validateDiscountAmount(product)"
                                                :readonly="!product.discount_type"
                                                min="1"
                                                :errors="getItemsError('discount_value', index)" />


                                        </td>
                                        <td>
                                            <span>{{ calculateProductTotal(product) }}</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" @click="openNoteModal(index)">
                                                <Page />
                                            </button>
                                            <button @click="removeProduct(index)" class="del-btn">
                                                <Trash />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-else>
                                        <td colspan="10" class="text-center">No products added</td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-xl-7">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <app-input
                                    type="number"
                                    id="order_tax"
                                    v-model="formData.order_tax"
                                    :disabled="!productList.length"
                                    :label="$t('order_tax')"
                                    :placeholder="$t('order_tax')"
                                    min="0"
                                    max="100"
                                    @keydown="blockInvalidKeys"
                                    @paste="blockNegativePaste"
                                    @input="changeOrderTax" />

                            </div>
                            <div class="col-md-6">
                                <app-input
                                    type="number"
                                    id="shipping"
                                    :label="$t('shipping')"
                                    v-model="formData.shipping_cost"
                                    min="1"
                                    @keydown="blockInvalidKeys" @paste="blockNegativePaste"
                                    :disabled="!productList.length"
                                    :placeholder="$t('shipping_cost')" />
                            </div>
                            <div class="col-md-6">
                                <app-input
                                    type="text"
                                    id="additional_charge_name"
                                    :label="$t('additional_charge_name')"
                                    v-model="formData.additional_charge_name"
                                    min="0"
                                    @keydown="blockInvalidKeys"
                                    @paste="blockNegativePaste"
                                    :disabled="!productList.length"
                                    :placeholder="$t('additional_charge_name')" />
                            </div>
                            <div class="col-md-6">
                                <app-input
                                    type="number"
                                    id="additional_charge"
                                    :label="$t('additional_charge')"
                                    v-model="formData.additional_charge"
                                    min="0"
                                    @keydown="blockInvalidKeys"
                                    @paste="blockNegativePaste"
                                    :disabled="!productList.length"
                                    :placeholder="$t('enter_additional_charge')" />
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-5">
                        <table class="table summary-table">
                            <tbody>
                                <tr>
                                    <td class="px-4 py-3">{{ $t('sub_total') }}</td>
                                    <td class="px-4 py-3">{{ numberWithCurrencySymbol(subtotal) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">{{ $t('discount') }}</td>
                                    <td class="px-4 py-3">{{ totalDiscount }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">{{ $t('order_tax') }}</td>
                                    <td class="px-4 py-3">
                                        {{ clampedOrderTax }} (%)
                                    </td>
                                </tr>


                                <tr>
                                    <td class="px-4 py-3">{{ $t('shipping') }}</td>
                                    <td class="px-4 py-3">{{ numberWithCurrencySymbol(shippingAmount) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">{{ $t('additional_charge') }}</td>
                                    <td class="px-4 py-3">{{ numberWithCurrencySymbol(additionalChargeAmount) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-primary">{{ $t('grand_total') }}</td>
                                    <td class="px-4 py-3 text-primary">{{ numberWithCurrencySymbol(grandTotal) }}</td>
                                </tr>

                                <tr>
                                    <td class="px-4 py-3 text-primary align-top">{{ $t('received_amount') }}</td>
                                    <td class="px-4 py-3 text-primary">
                                        <div class="d-flex flex-column gap-2 payment-wrapper mb-2"
                                            v-for="(payment, index) in payments" :key="index">

                                            <!-- Received Amount -->
                                            <app-input
                                                type="number"
                                                :id="`received_amount_${index}`"
                                                v-model="payment.amount"
                                                :placeholder="$t('received_amount')"
                                                @keydown="blockInvalidKeys"
                                                @paste="blockNegativePaste"
                                                @input="validatePaymentAmount(payment, index)"
                                                min="0"
                                                :errors="$errors(errors, `payments.${index}.amount`)" />

                                            <!-- Payment Method -->
                                            <app-input
                                                type="select"
                                                :id="`payment_method_${index}`"
                                                :options="availablePaymentMethods(index)"
                                                list-value-field="name"
                                                :choose-label="$t('choose_a_payment_method')"
                                                v-model="payment.payment_method_id"
                                                :errors="$errors(errors, `payments.${index}.payment_method_id`)" />

                                            <!-- Remove Button -->
                                            <div class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1"
                                                    @click="removePayment(index)"
                                                    v-if="payments.length > 1">
                                                    <i class="ri-close-line"></i> {{ $t('remove') }}
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Add Payment Button -->
                                <tr v-if="paymentMethodList?.length && payments.length < paymentMethodList.length && totalDeuAmount > 0">
                                    <td colspan="2">
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm d-block ml-auto"
                                            @click="addPayment">
                                            + {{ $t('add_payment_method') }}
                                        </button>

                                    </td>
                                </tr>

                                <tr>
                                    <td class="px-4 py-3 text-primary">{{ $t('total_received') }}</td>
                                    <td class="px-4 py-3 text-primary">{{
                                        numberWithCurrencySymbol(totalReceivedAmount)
                                        }}
                                    </td>
                                </tr>


                                <tr>
                                    <td class="px-4 py-3 text-primary">{{ $t('due_amount') }}</td>
                                    <td class="px-4 py-3 text-primary">{{
                                        numberWithCurrencySymbol(totalDeuAmount)
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <div class="col-12 d-flex justify-content-end gap-3">
                            <button class="btn btn-success text-white" @click.prevent="submit">
                                <app-button-loader v-if="preloader" />
                                {{ $t("save") }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped lang="scss"></style>

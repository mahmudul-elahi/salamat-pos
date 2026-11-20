<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import {
    SELECTED_SHOPS, SALES, SALE_SEARCH_PRODUCTS, SELECTED_PAYMENT_METHODS, SELECTED_CONTACTS, SELECTED_SELLERS
} from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { serverDateFormat } from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
import { useToast } from "vue-toastification";
import { Page, Search, Trash } from '@iconoir/vue';
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import PurchaseNoteModal from "@/views/landlord/pos/purchases/PurchaseNoteModal.vue";
import CustomerModal from "@/views/landlord/pos/people/customers/CustomerModal.vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import router from "@/router/index.js";
import Avatar from "@/views/global/avatar/Avatar.vue";

const store = useProfileStore();
const { profileData } = storeToRefs(store);

const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();
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

// Search Product
const searchProducts = async () => {
    if (!profileData?.value?.tenant && !formData.value.tenant_id) {
        toast.error("Please select warehouse/store")
        return;
    }
    if (!searchQuery.value.trim()) {
        products.value = [];
        return;
    }
    const url = !profileData?.value?.tenant ? `${SALE_SEARCH_PRODUCTS}?tenant_id=${formData.value.tenant_id}` : SALE_SEARCH_PRODUCTS
    preloader.value = true;
    try {
        const response = await Axios.get(url, {
            params: { query: searchQuery.value }
        });
        if (response.data && response.data.result) {
            products.value = response.data.result
        } else {
            products.value = []
        }
    } catch (error) {

    } finally {
        preloader.value = false;
    }
};

watch(searchQuery, searchProducts);
watch(products, (newProducts) => {
    if (newProducts.length === 1 && searchQuery.value.trim().toLowerCase() === newProducts[0].serial_number.toLowerCase()) {
        addProduct(newProducts[0]);
    }
});
const productList = ref([])
const selectedProductIndex = ref(null);
const openNoteModal = (index) => {
    selectedProductIndex.value = index;
    isModalActive.value = true;
};
const addProduct = (product) => {
    const exists = productList.value.some(
        (p) => p.serial_number === product.serial_number
    );

    if (!exists) {
        productList.value.unshift({
            ...product,
            product_id: product.id,
            price: product.max_retail_price,
            quantity: 1,
            discount_type: '',
            discount_amount: '',
            note: '',
            total: calculateProductTotal(product),
        });
    } else {
        toast.error("Product already added")
    }

    searchQuery.value = "";
    products.value = [];
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

// Discount with validation

const validateDiscountAmount = (product) => {
    let salePrice = Number(product.sale_price) || 0;
    const unitPrice = Number(product.price) || 0;

    // negative block
    if (salePrice < 0) {
        salePrice = 0;
    }

    // sale price cannot be greater than unit price
    if (salePrice > unitPrice) {
        salePrice = unitPrice;
    }

    product.sale_price = salePrice;
};
// Function to calculate total per product considering discount
const calculateProductTotal = (product) => {
    const salePrice = product.sale_price
        ? Number(product.sale_price)
        : 0;

    return salePrice * product.quantity;
};


// Subtotal for all products
const subtotal = computed(() => {
    return productList.value.reduce((sum, product) => {
        return sum + calculateProductTotal(product);
    }, 0);
});

// Function to calculate discount amount for a single product
const calculateDiscount = (product) => {
    const totalMrp = product.price
        ? Number(product.price)
        : 0;

    const salePrice = product.sale_price
        ? Number(product.sale_price)
        : 0;

    return (totalMrp - salePrice) * product.quantity;
};

// Total discount for all products
const totalDiscount = computed(() => {
    return productList.value.reduce((sum, product) => {
        return sum + calculateDiscount(product);
    }, 0);
});

// Subtotal after discount
const discountedSubtotal = computed(() => {
    return productList.value.reduce((sum, product) => {
        return sum + calculateProductTotal(product);
    }, 0);
});


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

const changeReceivedAmount = (event) => {
    let value = Number(event.target.value) || 0;

    if (value < 0) value = 0;
    if (value > grandTotal.value) value = grandTotal.value; // clamp to grand total

    formData.value.received_amount = value;
    event.target.value = value;
};


const errors = ref({});
const preloader = ref(false);
const pageLoader = ref(false);
const isCustomerModalActive = ref(false)

const fetchSaleData = async () => {
    pageLoader.value = true;
    try {
        const { data } = await Axios.get(`${SALES}/${props.id}`);

        formData.value = {
            ...formData.value,
            contact_id: data.contact_id,
            tenant_id: data.tenant_id,
            seller_id: data.seller_id,
            address: data.address,
            date: data.date,
            discount_type: data.discount_type || '',
            discount_amount: data.discount_amount || 0,
            order_tax: data.order_tax || 0,
            additional_charge: data.additional_charge || 0,
            additional_charge_name: data.additional_charge_name || '',
            shipping_cost: data.shipping_cost || 0,
        };
        productList.value = data.items.map(item => ({
            id: item.id,
            product_id: item.product_id,
            product_variant_id: item.variant_id,
            name: item.name,
            attribute_values: item.attribute_values,
            sku: item.sku,
            serial_number: item.serial_number,
            quantity: item.quantity,
            price: item.price,
            purchase_price: item.purchase_price,
            discount_type: item.discount_type,
            sale_price: item.discount_type && item.discount_type === 'percent' ? 0 : item.price - item.discount_amount || 0,
            discount_amount: item.discount_amount,
            note: item.note || '',
            status: item.status || '',
        }));
    } catch (err) {
        toast.error("Failed to load sale data");
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
                serial_number: product.serial_number,
                quantity: product.quantity,
                unit_price: product.price,
                discount_type: 'fixed',
                discount_amount: calculateDiscount(product),
                note: product.note,
            }
        })
    };

    try {
        await Axios.patch(`${SALES}/${props.id}`, payload);
        toast.success('Sale updated successfully');
        await router.push({ name: 'sales' });
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
    fetchSaleData()
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
            <app-loader v-if="pageLoader" />
            <div class="card-body" v-else>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex gap-2 align-items-end">
                            <div class="flex-grow-1">
                                <label for="customer" class="form-label">{{ $t('customer_info') }}</label>
                                <app-input type="advance-search-select" id="customer" list-key-field="id"
                                    list-value-field="name" v-model="formData.contact_id" :loading-length="10"
                                    :fetch-url="`${SELECTED_CONTACTS}`">
                                    <template #option="{ item }">
                                        <div class="supplier-option">
                                            <div class="supplier-option__avatar">
                                                <avatar :image="item.profile_picture" :name="item.name"
                                                    active="success" />
                                                <img v-if="item.business_logo" :src="item.business_logo"
                                                    class="supplier-option__badge" alt="" />
                                            </div>

                                            <div class="supplier-option__info">
                                                <strong class="supplier-option__name">{{ item.name }}</strong>
                                                <small class="supplier-option__phone">{{ item.phone }}</small>
                                                <small v-if="item.business_name" class="supplier-option__business">
                                                    <i class="bi bi-building"></i> {{ item.business_name }}
                                                </small>
                                            </div>
                                        </div>
                                    </template>
                                </app-input>
                            </div>
                            <button class="btn btn-primary" v-if="canAccess('create_customers')"
                                @click="isCustomerModalActive = true">{{
                                    $t('add_customer') }}
                            </button>

                        </div>
                    </div>
                    <div class="col-md-6" v-if="!profileData?.tenant">
                        <app-input type="advance-search-select" id="shop_id" :label="$t('warehouse_or_store_name')"
                            label-required list-value-field="name" v-model="formData.tenant_id" :loading-length="10"
                            :fetch-url="SELECTED_SHOPS" :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, 'tenant_id')" />
                    </div>
                    <div class="col-md-6">
                        <div class="col-12 col-md-6">
                            <app-input type="advance-search-select" id="seller_id" :key="formData.tenant_id"
                                :label="$t('seller')" list-key-field="id" list-value-field="name"
                                v-model="formData.seller_id" :loading-length="10" :fetch-url="`${SELECTED_SELLERS}?active_only=1`"
                                :choose-label="$t('choose_a_seller')" :errors="$errors(errors, 'seller_id')">
                                <template #option="{ item }">
                                    <div class="d-flex align-items-center gap-2">
                                        <avatar :image="item.profile_picture" :name="item.name" active="success" />
                                        <div class="d-flex flex-column">
                                            <strong>{{ item.name }}</strong>
                                            <small class="text-muted">{{ item.phone }}</small>
                                        </div>
                                    </div>
                                </template>
                            </app-input>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <app-input type="date" id="name" :label="$t('date')" label-required v-model="formData.date"
                            :errors="$errors(errors, 'date')" auto-apply :enable-time-picker="false"
                            :placeholder="$t('dd/mm/yy')" />
                    </div>
                    <div class="col-12">
                        <app-input type="textarea" id="address" :label="$t('address')" v-model="formData.address"
                            :errors="$errors(errors, 'address')" :placeholder="$t('address')" />
                    </div>
                    <div class="col-12">
                        <label for="search" class="mb-2">{{ $t('product') }} </label>
                        <div class="search-input position-relative">
                            <span class="search-icon">
                                <Search />
                            </span>

                            <app-input type="text" class="form-control rounded-default px-3 ps-5" id="search"
                                v-model="searchQuery" :placeholder="$t('search_product_by_code')" autofocus />

                            <div v-if="searchQuery.trim().length > 0" class="product-dropdown-list shadow-sm">
                                <ul v-if="products.length > 0">
                                    <li class="product-list" v-for="product in products" :key="product.id">
                                        <div class="fw-bold" @click="addProduct(product)">{{
                                            product.name
                                        }}({{ product.serial_number }})
                                        </div>
                                    </li>
                                </ul>
                                <div v-else>
                                    <p class="para">No product found</p>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table purchase-table">
                                <thead>
                                    <tr>
                                        <th>{{ $t('sl') }}</th>
                                        <th>{{ $t('product_name') }}</th>
                                        <th>{{ $t('serial_imei_no') }}</th>
                                        <th>{{ $t('p_price') }}</th>
                                        <th>{{ $t('mrp') }}</th>
                                        <th>{{ $t('sale_price') }}</th>
                                        <th>{{ $t('discount') }}</th>
                                        <th>{{ $t('total_price') }}</th>
                                        <th>{{ $t('action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="productList.length" v-for="(product, index) in productList"
                                        :key="product.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>
                                            {{ product.name }}
                                            <div v-if="product.attribute_values" class="badge-square-light bg-success">
                                                {{ product.attribute_values }}
                                            </div>
                                        </td>

                                        <td>
                                            <span class="badge-square-light bg-success">{{ product.serial_number
                                                }}</span>
                                            <div v-if="getItemsError('serial_number', index)" class="text-danger">
                                                <small>{{ getItemsError('serial_number', index) }}</small>
                                            </div>
                                        </td>

                                        <td>
                                            {{ product.purchase_price }}
                                        </td>
                                        <td>
                                            <app-input type="number" class="w-70" :id="`mrp_${index}`"
                                                v-model="product.price" placeholder="MRP" @keydown="blockInvalidKeys"
                                                @paste="blockNegativePaste" min="1"
                                                :errors="getItemsError('price', index)" />

                                        </td>
                                        <td>
                                            <app-input type="number" class="w-70" :id="`sale_price_${index}`"
                                                v-model="product.sale_price" placeholder="Sale Price"
                                                @keydown="blockInvalidKeys" @paste="blockNegativePaste" min="1"
                                                @input="validateDiscountAmount(product)"
                                                :errors="getItemsError('sale_price', index)" />
                                        </td>
                                        <td>
                                            {{ calculateDiscount(product) }}
                                        </td>
                                        <td>
                                            {{ calculateProductTotal(product) }}
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" @click="openNoteModal(index)">
                                                <Page />
                                            </button>
                                            <button v-if="isSupperAdmin()" @click="removeProduct(index)"
                                                class="del-btn">
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
                                <app-input type="number" id="order_tax" v-model="formData.order_tax"
                                    :disabled="!productList.length" :label="$t('order_tax')"
                                    :placeholder="$t('order_tax')" min="0" max="100" @keydown="blockInvalidKeys"
                                    @paste="blockNegativePaste" @input="changeOrderTax" />

                            </div>
                            <div class="col-md-6">
                                <app-input type="number" id="shipping" :label="$t('shipping')"
                                    v-model="formData.shipping_cost" min="1" @keydown="blockInvalidKeys"
                                    @paste="blockNegativePaste" :disabled="!productList.length"
                                    :placeholder="$t('shipping_cost')" />
                            </div>
                            <div class="col-md-6">
                                <app-input type="text" id="additional_charge_name" :label="$t('additional_charge_name')"
                                    v-model="formData.additional_charge_name" :disabled="!productList.length"
                                    :placeholder="$t('additional_charge_name')" />
                            </div>
                            <div class="col-md-6">
                                <app-input type="number" id="additional_charge" :label="$t('additional_charge')"
                                    v-model="formData.additional_charge" min="0" @keydown="blockInvalidKeys"
                                    @paste="blockNegativePaste" :disabled="!productList.length"
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
                                    <td class="px-4 py-3">{{ numberWithCurrencySymbol(totalDiscount) }}</td>
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
                                            <app-input type="number" :id="`received_amount_${index}`"
                                                v-model="payment.amount" :placeholder="$t('received_amount')"
                                                @keydown="blockInvalidKeys" @paste="blockNegativePaste"
                                                @input="validatePaymentAmount(payment, index)" min="0"
                                                :errors="$errors(errors, `payments.${index}.amount`)" />

                                            <!-- Payment Method -->
                                            <app-input type="select" :id="`payment_method_${index}`"
                                                :options="availablePaymentMethods(index)" list-value-field="name"
                                                :choose-label="$t('choose_a_payment_method')"
                                                v-model="payment.payment_method_id"
                                                :errors="$errors(errors, `payments.${index}.payment_method_id`)" />

                                            <!-- Remove Button -->
                                            <div class="text-end">
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1"
                                                    @click="removePayment(index)" v-if="payments.length > 1">
                                                    <i class="ri-close-line"></i> {{ $t('remove') }}
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Add Payment Button -->
                                <tr
                                    v-if="paymentMethodList?.length && payments.length < paymentMethodList.length && totalDeuAmount > 0">
                                    <td colspan="2">
                                        <button type="button" class="btn btn-primary btn-sm d-block ml-auto"
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
            <purchase-note-modal v-if="isModalActive" modal-id="purchase-note-modal"
                :note="selectedProductIndex !== null ? productList[selectedProductIndex].note : ''"
                @update:note="val => productList[selectedProductIndex].note = val" @close="closeModal" />

            <customer-modal v-if="isCustomerModalActive" modal-id="customer-modal" :phone-number="customerPhoneNumber"
                @close="isCustomerCloseModal" />

        </div>
    </div>
</template>

<style scoped lang="scss">
.supplier-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 2px;
    white-space: normal;

    &__avatar {
        position: relative;
        flex-shrink: 0;
    }

    &__badge {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        background: #fff;
        box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08);
    }

    &__info {
        display: flex;
        flex-direction: column;
        min-width: 0;
        gap: 2px;
    }

    &__name {
        font-size: 0.9rem;
        color: #1e293b;
        line-height: 1.2;
    }

    &__phone {
        font-size: 0.78rem;
        color: #94a3b8;
        line-height: 1.2;
    }

    &__business {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        width: fit-content;
        margin-top: 2px;
        padding: 2px 8px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 600;

        i {
            font-size: 0.7rem;
        }
    }
}

.barcode-toggle-btn {
    width: 100%;
    border: none;
    outline: none;
    background: #f0f2f5;
    border-radius: 12px;
    padding: 8px 20px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.25s ease;

    &:hover:not(.active) {
        color: var(--primary);
        background: #e5e8ec;
    }

    &.active {
        background: var(--primary);
        color: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        font-weight: 600;
    }
}
</style>

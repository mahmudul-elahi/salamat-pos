<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import {
    SELECTED_SHOPS,
    SELECTED_PAYMENT_METHODS,
    SALE_RETURN_SEARCH_PRODUCTS,
    SALES_RETURNS
} from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { serverDateFormat } from "@/utils/helper.js";
import { useToast } from "vue-toastification";
import { Page, Search, Trash } from '@iconoir/vue';
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import PurchaseNoteModal from "@/views/landlord/pos/purchases/PurchaseNoteModal.vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import usePermission from "@/composable/permission/usePermission.js";


const { isSupperAdmin } = usePermission();
const store = useProfileStore();
const { profileData } = storeToRefs(store);

const formData = ref({
    discount_amount: '',
    date: new Date()
});
const props = defineProps({
    id: {},
});

const toast = useToast();
const searchQuery = ref("");
const products = ref([]);
const { isModalActive, closeModal } = useOpenModal()
const customerInfo = ref({})

// Search Product
const searchProducts = async () => {
    if (!searchQuery.value.trim()) {
        products.value = [];
        return;
    }
    if (isSupperAdmin() && !formData.value.tenant_id) {
        toast.error("Please select a warehouse");
        return;
    }

    preloader.value = true;
    try {
        const params = {
            query: searchQuery.value,
        };

        if (isSupperAdmin()) {
            params.tenant_id = formData.value.tenant_id;
        }
        const response = await Axios.get(SALE_RETURN_SEARCH_PRODUCTS, {
            params: params
        });

        if (response.data && response.data.result) {
            customerInfo.value = response.data?.result?.contact
            products.value = response.data.result?.items
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
watch(customerInfo, (newCustomer, oldCustomer) => {
    if (oldCustomer?.id && newCustomer?.id !== oldCustomer.id) {
        productList.value = [];
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
            unit_price: product.unit_price,
            quantity: product.quantity,
            discount_type: product.discount_type || '',
            discount_amount: product.discount_amount || '',
            note: product.note || '',
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



// Function to calculate total per product considering discount
const calculateProductTotal = (product) => {
    let total = product.unit_price * product.quantity;

    if (product.discount_type === 'fixed') {
        total -= Number(product.discount_amount || 0);
    } else if (product.discount_type === 'percent') {
        total -= (total * Number(product.discount_amount || 0) / 100);
    }

    return total < 0 ? 0 : total;
};

// Subtotal for all products
const subtotal = computed(() => {
    return productList.value.reduce((sum, product) => {
        return sum + calculateProductTotal(product);
    }, 0);
});

// Function to calculate discount amount for a single product
const calculateDiscount = (product) => {
    const totalPrice = Number(product.unit_price) * Number(product.quantity);

    if (product.discount_type === 'fixed') {
        return Math.min(Number(product.discount_amount || 0), totalPrice);
    } else if (product.discount_type === 'percent') {
        return totalPrice * (Number(product.discount_amount || 0) / 100);
    } else {
        return 0; // none
    }
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

// Shipping
const shippingAmount = computed(() => {
    if (Number(formData.value.shipping_cost)) {
        return Number(formData.value.shipping_cost);
    }
    return 0;
});

// Grand total
const grandTotal = computed(() => {
    return discountedSubtotal.value + taxAmount.value + shippingAmount.value;
});
// Received Amount
const totalDeuAmount = computed(() => {
    if (grandTotal.value < formData.value.return_amount) {
        return 0;
    }
    if (!formData.value.return_amount) {
        return grandTotal.value;
    }
    return grandTotal.value - formData.value.return_amount;
});
const changeReceivedAmount = (event) => {
    let value = Number(event.target.value) || 0;

    if (value < 0) value = 0;
    if (value > grandTotal.value) value = grandTotal.value;

    formData.value.return_amount = value;
    event.target.value = value;
};

const errors = ref({});
const preloader = ref(false);
const submit = async () => {
    if (!productList.value.length) {
        toast.error("Please add at least one product");
        return;
    }
    preloader.value = true;
    errors.value = {};

    const data = {
        ...formData.value,
        contact_id: customerInfo.value?.id,
        date: formData.value.date ? serverDateFormat(formData.value.date) : '',
        discount_type: formData.value.discount_type || '',
        discount_amount: formData.value.discount_amount || 0,
        order_tax: formData.value.order_tax || 0,
        shipping_cost: formData.value.shipping_cost || 0,
        sub_total: subtotal.value,
        grand_total: grandTotal.value,
        items: productList.value.map((product) => {
            return {
                sale_id: product.sale_id,
                product_id: product.product_id,
                variant_id: product.product_variant_id,
                serial_number: product.serial_number,
                quantity: product.quantity,
                unit_price: product.unit_price,
                discount_type: product.discount_type || '',
                discount_amount: product.discount_amount || '',
                note: product.note || '',
            }
        })
    };


    try {
        await Axios.post(SALES_RETURNS, data).then((response) => {
            toast.success(response.data?.message);
            formData.value = {}
            productList.value = [];
        });
    } catch (error) {
        if (error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message);
        }
    } finally {
        preloader.value = false
    }
};
const paymentMethodList = ref([])
const getPaymentMethod = () => {
    Axios.get(SELECTED_PAYMENT_METHODS).then((response) => {
        paymentMethodList.value = response.data.data
    })
}
onMounted(() => {
    getPaymentMethod()
})
const getItemsError = (field, index) => errors.value[`items.${index}.${field}`]?.[0] || null;


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('create_sale_return')" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label for="search" class="mb-2">{{ $t('customer') }} </label>
                        <h6>{{ customerInfo.name }}</h6>
                        <small v-if="errors.contact_id">{{ errors.contact_id[0] }}</small>
                    </div>
                    <div class="col-md-6" v-if="!profileData?.tenant">
                        <app-input type="advance-search-select" id="shop_id" :label="$t('warehouse_or_store_name')"
                            label-required list-value-field="name" v-model="formData.tenant_id" :loading-length="10"
                            :fetch-url="SELECTED_SHOPS" :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, 'tenant_id')" />
                    </div>
                    <div class="col-md-6">
                        <app-input type="date" id="name" :label="$t('date')" label-required v-model="formData.date"
                            :errors="$errors(errors, 'date')" auto-apply :enable-time-picker="false"
                            :placeholder="$t('dd/mm/yy')" />
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
                                        <th>{{ $t('sku') }}</th>
                                        <th>{{ $t('serial_imei_no') }}</th>
                                        <th>{{ $t('unit_price') }}</th>
                                        <th>{{ $t('received_amount') }}</th>
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
                                            <span class="badge-square-light bg-success">{{ product.sku }}</span>
                                        </td>
                                        <td>
                                            <span class="badge-square-light bg-success">{{ product.serial_number
                                                }}</span>
                                            <div v-if="getItemsError('serial_number', index)" class="text-danger">
                                                <small>{{ getItemsError('serial_number', index) }}</small>
                                            </div>
                                        </td>

                                        <td>
                                            {{ product.unit_price }}
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
                                        <td colspan="7" class="text-center">No products added</td>
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


                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="table-responsive">
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
                                        <td class="px-4 py-3 text-primary">{{ $t('grand_total') }}</td>
                                        <td class="px-4 py-3 text-primary">{{ numberWithCurrencySymbol(grandTotal) }}
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
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

        </div>
    </div>
</template>

<style scoped lang="scss"></style>

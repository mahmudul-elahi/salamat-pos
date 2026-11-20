<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import {
    SELECTED_SHOPS,
    SELECTED_PAYMENT_METHODS,
    SALE_RETURN_SEARCH_PRODUCTS,
    SALES_RETURNS
} from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { serverDateFormat, formDataAssigner } from "@/utils/helper.js";
import { useToast } from "vue-toastification";
import { Page, Search, Trash } from '@iconoir/vue';
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import PurchaseNoteModal from "@/views/landlord/pos/purchases/PurchaseNoteModal.vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import router from "@/router/index.js";

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
    preloader.value = true;
    try {
        const response = await Axios.get(SALE_RETURN_SEARCH_PRODUCTS, {
            params: { query: searchQuery.value }
        });
        if (response.data && response.data.result) {
            customerInfo.value = response.data?.result?.contact
            products.value = response.data.result?.items
        } else {
            products.value = []
        }
    } catch (error) {
        console.log("Error", error)
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

    if (formData.value?.contact && formData.value?.contact.id !== customerInfo.value?.id) {
        toast.error("Different customer detected. Please clear the current customer first")
        return
    }

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
const changeDiscountType = (event, product) => {
    product.discount_type = event.target.name;
    product.discount_amount = '';
}
// Discount with validation

const validateDiscountAmount = (product) => {
    let value = Number(product.discount_amount) || 0;
    const unitPrice = Number(product.unit_price) || 0;

    if (product.discount_type === 'fixed') {
        // Min 0, max unit price
        if (value < 0) value = 0;
        if (value > unitPrice) value = unitPrice;
    }

    if (product.discount_type === 'percent') {
        // Min 0, max 100
        if (value < 0) value = 0;
        if (value > 100) value = 100;
    }

    product.discount_amount = value;
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


const errors = ref({});
const preloader = ref(false);
const submit = async () => {
    if (!productList.value.length) {
        toast.error("Please add at least one product");
        return;
    }
    preloader.value = true;
    errors.value = {};

    const payload = {
        ...formData.value,
        contact_id: formData.value.contact_id,
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


    const data = formDataAssigner(new FormData(), payload);
    data.append('_method', 'PATCH');

    try {
        await Axios.post(`${SALES_RETURNS}/${props.id}`, data, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        toast.success('Sale Return updated successfully');
        await router.push({ name: 'salesReturn' });
    } catch (error) {
        if (error.response?.status === 422) errors.value = error.response.data.errors;
        else toast.error(error.response?.data?.message || 'Error submitting form');
    } finally {
        preloader.value = false;
    }
};
const paymentMethodList = ref([])
const pageLoader = ref(false);
const getPaymentMethod = () => {
    Axios.get(SELECTED_PAYMENT_METHODS).then((response) => {
        paymentMethodList.value = response.data.data
    })
}
const fetchSaleReturnData = async () => {
    pageLoader.value = true;
    try {
        const { data } = await Axios.get(`${SALES_RETURNS}/${props.id}`);
        formData.value = {
            ...formData.value,
            contact: data.contact,
            contact_id: data.contact_id,
            tenant_id: data.tenant_id,
            date: data.date,
            order_tax: data.order_tax || 0,
            shipping_cost: data.shipping_cost || 0,
        };


        productList.value = data.items.map(item => ({
            id: item.id,
            product_id: item.product_id,
            product_variant_id: item.variant_id,
            sale_id: item.sale_id,
            name: item.name,
            attribute_values: item.attribute_values,
            sku: item.sku,
            serial_number: item.serial_number,
            quantity: item.quantity,
            unit_price: item.price,
            purchase_price: item.purchase_price,
            discount_type: item.discount_type,
            discount_amount: item.discount_amount,
            note: item.note || '',
            status: item.status || '',
        }));
    } catch (err) {
        console.log(err);

        toast.error("Failed to load sale return data");
    } finally {
        pageLoader.value = false;
    }
};
onMounted(() => {
    if (props.id) {
        fetchSaleReturnData();
    }
    getPaymentMethod()
})
const getItemsError = (field, index) => errors.value[`items.${index}.${field}`]?.[0] || null;


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('update_sale_return')" />
        <div class="card">
            <app-loader v-if="pageLoader" />
            <div class="card-body" v-else>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label for="search" class="mb-2">{{ $t('customer') }} </label>
                        <h6>{{ formData.contact?.name }}</h6>
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
                                        <th>{{ $t('quantity') }}</th>
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
                                            <b>{{ product.quantity }}</b>
                                        </td>
                                        <td>

                                            <div class="d-flex">
                                                <div class="form-check">
                                                    <label class="form-check-label mx-2" :for="`${index}_fixed`">
                                                        {{ $t("fixed") }}
                                                    </label>
                                                    <input :id="`${index}_fixed`" class="form-check-input"
                                                        type="checkbox" :checked="product.discount_type === 'fixed'"
                                                        @click="changeDiscountType($event, product)" name="fixed">
                                                </div>

                                                <div class="form-check">
                                                    <label class="form-check-label mx-2" :for="`${index}_percent`">{{
                                                        $t("percent") }}</label>
                                                    <input :id="`${index}_percent`" class="form-check-input"
                                                        type="checkbox" :checked="product.discount_type === 'percent'"
                                                        @click="changeDiscountType($event, product)" name="percent">
                                                </div>

                                            </div>


                                            <app-input type="number" class="w-70" :id="`discount_${index}`"
                                                v-model="product.discount_amount" placeholder="Amount"
                                                @keydown="blockInvalidKeys" @paste="blockNegativePaste"
                                                @input="validateDiscountAmount(product)"
                                                :readonly="!product.discount_type" min="1"
                                                :errors="getItemsError('discount_amount', index)" />


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

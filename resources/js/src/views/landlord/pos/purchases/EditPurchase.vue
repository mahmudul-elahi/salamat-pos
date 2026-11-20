<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import {
    SEARCH_PRODUCTS,
    SELECTED_SHOPS, PURCHASES, SELECTED_CONTACTS
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
import router from "@/router/index.js";
import Avatar from "@/views/global/avatar/Avatar.vue";

const formData = ref({
    discount_amount: '',
});
const props = defineProps({
    id: {},
});

const store = useProfileStore();
const { profileData } = storeToRefs(store);

const toast = useToast();
const searchQuery = ref("");
const products = ref([]);
const { isModalActive, closeModal } = useOpenModal()


// Search Product
const searchProducts = async () => {
    if (!searchQuery.value.trim()) {
        products.value = [];
        return;
    }
    preloader.value = true;
    try {
        const response = await Axios.get(SEARCH_PRODUCTS, {
            params: { query: searchQuery.value }
        });

        if (response.data && response.data.result) {
            products.value = response.data.result;
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
            price: '',
            quantity: 1,
            note: '',
        });
    } else {
        toast.error("Product already added")
    }

    searchQuery.value = "";
    products.value = [];
};

const removeProduct = (index) => {
    const product = productList.value[index];

    if (product.status === 'sale') {
        toast.error("You can't remove this item because it has been sold.");
        return;
    }

    productList.value.splice(index, 1);
}

// Subtotal calculation
const subtotal = computed(() => {
    return productList.value.reduce((sum, product) => {
        const price = parseFloat(product.price) || 0;
        const qty = parseInt(product.quantity) || 0;
        return sum + price * qty;
    }, 0);
});

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
    if (/[eE\-\+]/.test(pasted)) {
        e.preventDefault();
    }
};
const changeDiscountAmount = (event) => {
    let value = Number(event.target.value) || 0;

    if (formData.value.discount_type === 'fixed') {
        // Clamp fixed discount between 1 and subtotal
        if (value < 1) value = 1;
        if (value > subtotal.value) value = subtotal.value;
    }

    if (formData.value.discount_type === 'percent') {
        // Clamp percent discount between 1% and 100%
        if (value < 1) value = 1;
        if (value > 100) value = 100;
    }

    formData.value.discount_amount = value;
    event.target.value = value;
};
// Discount with validation
const discountAmount = computed(() => {
    const type = formData.value.discount_type;
    let value = parseFloat(formData.value.discount_amount) || 0;

    if (!type || value <= 0) return 0;

    if (type === "fixed") {
        // Fixed discount: min 1, max subtotal
        if (value < 1) value = 1;
        if (value > subtotal.value) value = subtotal.value; // clamp to subtotal
        return value;
    }

    if (type === "percent") {
        // Percent discount: min 1%, max 100%
        if (value < 1) value = 1;
        if (value > 100) value = 100;
        return (subtotal.value * value) / 100;
    }

    return 0;
});

// Subtotal after discount
const discountedSubtotal = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value);
});


// Clamp order_tax between 0 and 100
const clampedOrderTax = computed(() => {
    const tax = Number(formData.value.order_tax) || 0;
    if (tax < 0) return 0;
    if (tax > 100) return 100;
    return tax;
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

const changeInvoiceBillImage = (file) => {
    if (!file) return;
    formData.value.invoice_bill = file;
}
const errors = ref({});
const preloader = ref(false);
const pageLoader = ref(false);
const submit = async () => {
    if (!productList.value.length) {
        toast.error("Please add at least one product");
        return;
    }

    preloader.value = true;
    errors.value = {};
    const data = {
        ...formData.value,
        date: formData.value.date ? serverDateFormat(formData.value.date) : '',
        discount_type: formData.value.discount_type || '',
        discount_amount: formData.value.discount_amount || 0,
        order_tax: formData.value.order_tax || 0,
        shipping_cost: formData.value.shipping_cost || 0,
        sub_total: subtotal.value,
        grand_total: grandTotal.value.toFixed(2),
        items: productList.value.map((product) => {
            return {
                product_id: product.product_id,
                variant_id: product.product_variant_id,
                serial_number: product.serial_number,
                quantity: product.quantity,
                unit_price: product.price,
                note: product.note,
            }
        })
    };


    //data.append('_method', 'PATCH');
    try {
        await Axios.patch(`${PURCHASES}/${props.id}`, data);
        toast.success('Purchase updated successfully');
        await router.push({ name: 'purchases' });
    } catch (error) {
        if (error.response?.status === 422) errors.value = error.response.data.errors;
        else toast.error(error.response?.data?.message || 'Error submitting form');
    } finally {
        preloader.value = false;
    }
};

const resetData = () => {
    formData.value = {};
    searchQuery.value = "";
    products.value = [];
    productList.value = [];
    errors.value = {};
    formData.value.invoice_bill = '';

};

const getItemsError = (field, index) => errors.value[`items.${index}.${field}`]?.[0] || null;

const fetchPurchaseData = async () => {
    pageLoader.value = true;
    try {
        const { data } = await Axios.get(`${PURCHASES}/${props.id}`);

        formData.value = {
            ...formData.value,
            tenant_id: data.tenant_id,
            contact_id: data.contact_id,
            date: data.date,
            discount_type: data.discount_type || '',
            discount_amount: data.discount_amount || 0,
            order_tax: data.order_tax || 0,
            shipping_cost: data.shipping_cost || 0,
        };

        // if (data.tenant_id) {
        //     supplierFetchUrl.value = `/admin/selected/suppliers?shop_id=${data.tenant_id}`;
        // }

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
            note: item.note || '',
            status: item.status || '',
        }));
    } catch (err) {
        toast.error("Failed to load purchase data");
    } finally {
        pageLoader.value = false;
    }
};


onMounted(() => {

    if (props.id) {
        fetchPurchaseData();
    }
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('update_purchase')" />
        <div class="card">
            <app-loader v-if="pageLoader" />
            <div class="card-body" v-else>
                <div class="row g-4">
                    <div class="col-md-6" v-if="!profileData?.tenant">
                        <app-input type="advance-search-select" id="shop_id" :label="$t('warehouse_or_store_name')"
                            label-required list-value-field="name" v-model="formData.tenant_id" :loading-length="10"
                            :fetch-url="SELECTED_SHOPS" :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, 'tenant_id')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="supplier" :label="$t('supplier')"
                            list-key-field="id" list-value-field="name" :loading-length="10"
                            v-model="formData.contact_id" :fetch-url="SELECTED_CONTACTS"
                            :choose-label="$t('choose_a_supplier')" :errors="$errors(errors, 'contact_id')"
                            :row-per-load="10">

                            <template #option="{ item }">
                                <div class="supplier-option">
                                    <div class="supplier-option__avatar">
                                        <avatar :image="item.profile_picture" :name="item.name" active="success" />
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
                                        <th>{{ $t('total_price') }}</th>
                                        <th>{{ $t('action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="productList.length" v-for="(product, index) in productList"
                                        :key="product.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>

                                            {{ product.name }} <span v-if="product.status === 'sale'"
                                                class="badge-square-light bg-danger">Sold</span>
                                            <div v-if="product.attribute_values" class="badge-square-light bg-success">
                                                {{ product.attribute_values }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-square-light bg-success">{{ product.sku }}</span>
                                        </td>
                                        <td>
                                            <span class="badge-square-light bg-success">
                                                {{ product.serial_number }}
                                            </span>

                                            <div v-if="getItemsError('serial_number', index)" class="text-danger">
                                                <small>{{ getItemsError('serial_number', index) }}</small>
                                            </div>
                                        </td>

                                        <td>
                                            <app-input type="number" class="w-60" id="price" min="1"
                                                :placeholder="$t('unit_price')" v-model="product.price"
                                                @keydown="blockInvalidKeys" @paste="blockNegativePaste"
                                                :errors="getItemsError('unit_price', index)" />

                                        </td>
                                        <td>
                                            <b>{{ product.quantity }}</b>
                                        </td>
                                        <td>
                                            <span>{{ product.price * product.quantity }}</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" @click="openNoteModal(index)">
                                                <Page />
                                            </button>

                                            <button @click="removeProduct(index)" class="del-btn"
                                                :class="{ 'table-disabled-row': product.status === 'sale' }">
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
                            <div class="col-md-6"
                                :style="!productList.length ? 'pointer-events: none; cursor: not-allowed;' : ''">
                                <app-input type="select" id="discount_type" :label="$t('discount_type')"
                                    v-model="formData.discount_type" :options="[
                                        { id: 'fixed', value: 'Fixed' },
                                        { id: 'percent', value: 'Percent' }
                                    ]" :choose-label="$t('choose_a_discount_type')" :disabled="!productList.length"
                                    :errors="$errors(errors, 'discount_type')" />
                            </div>
                            <div class="col-md-6">
                                <app-input type="number" id="discount_amount" :label="$t('discount_amount')"
                                    :label-required="formData.discount_type === 'percent' || formData.discount_type === 'fixed'"
                                    v-model.number="formData.discount_amount" :placeholder="$t('discount_amount')"
                                    :disabled="!(formData.discount_type === 'percent' || formData.discount_type === 'fixed')"
                                    min="1" :max="formData.discount_type === 'percent' ? 100 : subtotal"
                                    @keydown="blockInvalidKeys" @paste="blockNegativePaste"
                                    :errors="$errors(errors, 'discount_amount')" @input="changeDiscountAmount" />
                            </div>

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
                                        <td class="px-4 py-3">{{ (discountAmount) }}
                                            <span
                                                v-if="formData.discount_type && formData.discount_type === 'percent'">(%)</span>
                                        </td>
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
</style>

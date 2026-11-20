<script setup>
import { computed, ref, watch } from 'vue';
import {
    SEARCH_PRODUCTS,
    SELECTED_SHOPS, PURCHASES, SELECTED_CUSTOMERS, SELECTED_CONTACTS,
    SELECTED_PRODUCT, SELECTED_PRODUCT_ATTRIBUTE, PRODUCT_BARCODE_GENERATE
} from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { formDataAssigner, serverDateFormat } from "@/utils/helper.js";
import { useToast } from "vue-toastification";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import { Page, Search, Trash } from '@iconoir/vue';
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import PurchaseNoteModal from "@/views/landlord/pos/purchases/PurchaseNoteModal.vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import Avatar from "@/views/global/avatar/Avatar.vue";
import SupplierModal from "@/views/landlord/pos/people/suppliers/SupplierModal.vue";
import usePermission from "@/composable/permission/usePermission.js";


const formData = ref({
    discount_amount: '',
    date: new Date(),
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
const { canAccess } = usePermission();
const isSupplierModalActive = ref(false);

// The supplier field's advance-search-select only resolves its display
// label once, on mount — setting formData.contact_id alone after a supplier
// is created inline wouldn't refresh what's shown, so the field is remounted
// (via :key below) whenever this changes.
const onSupplierCreated = (supplier) => {
    formData.value.contact_id = supplier.id;
};

// Create Barcode (inline product/variant/serial add, mirrors CreateSale.vue's Product mode)
const isBarcodeModeActive = ref(false);
const barcodeProductId = ref(null);
const barcodeVariantOptions = ref([]);
const barcodeVariantId = ref(null);
const barcodeSerial = ref('');
const barcodeVariantLoading = ref(false);
const barcodeApplying = ref(false);
const barcodeErrors = ref({});

// Suggest a unique 10-digit random serial (mirrors BarcodePrint.vue's generateRandomSerial)
const generatedBarcodeSerials = new Set();
const suggestBarcodeSerial = () => {
    let serial;
    do {
        serial = Math.floor(1000000000 + Math.random() * 9000000000).toString();
    } while (generatedBarcodeSerials.has(serial));
    generatedBarcodeSerials.add(serial);
    barcodeSerial.value = serial;
};

watch(barcodeProductId, async (productId) => {
    barcodeVariantId.value = null;
    barcodeVariantOptions.value = [];
    if (!productId) return;
    barcodeVariantLoading.value = true;
    try {
        const { data } = await Axios.get(`${SELECTED_PRODUCT_ATTRIBUTE}/${productId}`);
        barcodeVariantOptions.value = Array.isArray(data) ? data : (data?.data ?? []);
    } catch (e) {
        console.error(e);
    } finally {
        barcodeVariantLoading.value = false;
    }
});

const applyBarcode = async () => {
    barcodeErrors.value = {};

    if (!barcodeProductId.value) { toast.error("Please select a product"); return; }
    if (!barcodeVariantId.value) { toast.error("Please select a variant"); return; }
    if (!barcodeSerial.value.trim()) { toast.error("Please enter serial / IMEI"); return; }

    const variant = barcodeVariantOptions.value.find(v => v.id == barcodeVariantId.value);
    barcodeApplying.value = true;

    try {
        await Axios.post(PRODUCT_BARCODE_GENERATE, {
            items: [{
                serial_number: barcodeSerial.value.trim(),
                max_retail_price: variant?.max_retail_price || 0,
                variant_id: barcodeVariantId.value,
                product_id: barcodeProductId.value,
            }],
            tenant_id: formData.value.tenant_id,
        });

        addProduct({
            id: barcodeProductId.value,
            name: variant?.product_name ?? '',
            product_variant_id: barcodeVariantId.value,
            serial_number: barcodeSerial.value.trim(),
            sku: variant?.sku ?? '',
            attribute_values: variant?.attributes ?? '',
        });

        barcodeSerial.value = '';
    } catch ({ response }) {
        if (response?.status === 422) {
            barcodeErrors.value = response.data.errors || {};
        }
        toast.error(response?.data?.message || 'Failed to create barcode');
    } finally {
        barcodeApplying.value = false;
    }
};


// Search Product
const searchPreloader = ref(false)
const searchProducts = async () => {
    if (!searchQuery.value.trim()) {
        products.value = [];
        return;
    }
    searchPreloader.value = true;
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
        searchPreloader.value = false;
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
const submit = async () => {
    if (!productList.value.length) {
        toast.error("Please add at least one product");
        return;
    }
    preloader.value = true;
    errors.value = {};

    const data = formDataAssigner(new FormData(), {
        ...formData.value,
        date: formData.value.date ? serverDateFormat(formData.value.date) : '',
        discount_type: formData.value.discount_type || '',
        discount_amount: formData.value.discount_amount || 0,
        order_tax: formData.value.order_tax || 0,
        shipping_cost: formData.value.shipping_cost || 0,
        sub_total: subtotal.value,
        grand_total: grandTotal.value,
        items: productList.value.map((product) => {
            return {
                product_id: product.id,
                variant_id: product.product_variant_id,
                serial_number: product.serial_number,
                quantity: product.quantity,
                unit_price: product.price,
                note: product.note,
            }
        })
    });


    try {
        await Axios.post(PURCHASES, data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        }).then((response) => {
            toast.success(response.data?.message);
            resetData();
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

const resetData = () => {
    formData.value = {
        date: new Date()
    };
    searchQuery.value = "";
    products.value = [];
    productList.value = [];
    errors.value = {};
    formData.value.invoice_bill = '';

};
const getItemsError = (field, index) => errors.value[`items.${index}.${field}`]?.[0] || null;


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('create_purchase')" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6" v-if="!profileData?.tenant">

                        <app-input type="advance-search-select" id="shop_id" :label="$t('warehouse_or_store_name')"
                            label-required list-value-field="name" v-model="formData.tenant_id" :loading-length="10"
                            :fetch-url="SELECTED_SHOPS" :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, 'tenant_id')" />
                    </div>


                    <div class="col-md-6">
                        <div class="d-flex flex-wrap gap-2 align-items-stretch">
                            <div class="flex-grow-1 min-w-0">
                                <app-input :key="formData.contact_id" type="advance-search-select" id="supplier"
                                    :label="$t('supplier')"
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
                            <div v-if="canAccess('create_suppliers')" class="flex-shrink-0 d-flex flex-column">
                                <label class="mb-2 invisible" aria-hidden="true">_</label>
                                <button class="btn btn-primary flex-grow-1" type="button"
                                    @click="isSupplierModalActive = true">
                                    {{ $t('add_supplier') }}
                                </button>
                            </div>
                        </div>


                    </div>

                    <div class="col-md-6">
                        <app-input type="date" id="name" :label="$t('date')" label-required v-model="formData.date"
                            :errors="$errors(errors, 'date')" auto-apply :enable-time-picker="false"
                            :placeholder="$t('dd/mm/yy')" />
                    </div>
                    <div class="col-md-6">
                        <Attachment :label="$t('invoice_bill')" id="invoice_bill" v-model="formData.invoice_bill"
                            @changeFile="changeInvoiceBillImage($event)" :error="$errors(errors, 'invoice_bill')"
                            :recommended-size="$t('logo_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')" />
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="barcode-toggle-btn" :class="{ active: isBarcodeModeActive }"
                            @click="isBarcodeModeActive = !isBarcodeModeActive">
                            {{ $t('create_barcode') }}
                        </button>
                    </div>

                    <div v-if="isBarcodeModeActive" class="col-12">
                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-md-4">
                                <app-input type="advance-search-select" id="barcode_product" :label="$t('product')"
                                    label-required list-value-field="name" :loading-length="10"
                                    v-model="barcodeProductId" :fetch-url="SELECTED_PRODUCT"
                                    :choose-label="$t('choose_a_product')"
                                    :errors="$errors(barcodeErrors, 'items.0.product_id')" />
                            </div>
                            <div class="col-12 col-md-4">
                                <app-input type="select" id="barcode_variant" label-required :label="$t('variant')"
                                    :options="barcodeVariantOptions" list-value-field="attributes"
                                    :choose-label="barcodeVariantLoading ? $t('loading') : $t('choose_a_variant')"
                                    :disabled="!barcodeVariantOptions.length" v-model="barcodeVariantId"
                                    :errors="$errors(barcodeErrors, 'items.0.variant_id')" />
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="mb-2">{{ $t('serial_imei_no') }}</label>
                                <div class="d-flex gap-2 align-items-start">
                                    <div class="flex-grow-1 min-w-0">
                                        <app-input type="text" id="barcode_serial" label-required
                                            v-model="barcodeSerial" :placeholder="$t('serial_imei_no')"
                                            @keyup.enter="applyBarcode"
                                            :errors="$errors(barcodeErrors, 'items.0.serial_number')" />
                                    </div>

                                    <button type="button" class="btn btn-warning flex-shrink-0 px-3"
                                        @click="suggestBarcodeSerial">
                                        {{ $t('suggest') }}
                                    </button>

                                    <button type="button" class="btn btn-primary flex-shrink-0 px-3"
                                        :disabled="barcodeApplying" @click="applyBarcode">
                                        {{ $t('apply') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="search" class="mb-2">{{ $t('search_by_barcode') }}</label>
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
                                            {{ product.name }}
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
                                    :disabled="!productList.length" :label="$t('order_tax')" :placeholder="$t('0.00')"
                                    min="0" max="100" @keydown="blockInvalidKeys" @paste="blockNegativePaste"
                                    @input="changeOrderTax" />

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

            <supplier-modal v-if="isSupplierModalActive" modal-id="purchase-supplier-modal"
                @created="onSupplierCreated" @close="isSupplierModalActive = false" />

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

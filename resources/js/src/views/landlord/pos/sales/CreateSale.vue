<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import {
    SELECTED_SHOPS, SALES, SALE_SEARCH_PRODUCTS, SELECTED_PAYMENT_METHODS, SALE_INVOICE_DOWNLOAD,
    SELECTED_CONTACTS, SELECTED_PRODUCT, SELECTED_PRODUCT_ATTRIBUTE, SELECTED_SELLERS, CUSTOMERS
} from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { serverDateFormat } from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
import { useToast } from "vue-toastification";
import { Page, Search, Trash, Eye, EyeClosed } from '@iconoir/vue';
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import PurchaseNoteModal from "@/views/landlord/pos/purchases/PurchaseNoteModal.vue";
import CustomerModal from "@/views/landlord/pos/people/customers/CustomerModal.vue";
import Avatar from "@/views/global/avatar/Avatar.vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";


const props = defineProps({
    id: {},
});
const store = useProfileStore();
const { profileData } = storeToRefs(store);

const { canAccess } = usePermission();
const formData = ref({
    discount_amount: '',
    date: new Date(),
    additional_charge: 0,
    additional_charge_name: ''
});


const toast = useToast();
const saleType = ref(null);

// Pre-fill the sale address from the chosen customer, without overwriting anything typed by hand.
const prefilledAddress = ref('');

watch(() => formData.value.contact_id, async (contactId) => {
    const addressIsUntouched = !formData.value.address || formData.value.address === prefilledAddress.value;
    if (!contactId || !addressIsUntouched) return;

    try {
        const { data } = await Axios.get(`${CUSTOMERS}/${contactId}`);
        if (formData.value.contact_id !== contactId) return;

        prefilledAddress.value = data.invoice_address || '';
        formData.value.address = prefilledAddress.value;
    } catch {
        // The address stays editable; the invoice falls back to the customer's address.
    }
});


// Structured product add (Product mode)
const addProductId = ref(null);
const addVariantOptions = ref([]);
const addVariantId = ref(null);
const addSerial = ref('');
const variantLoading = ref(false);

watch(addProductId, async (productId) => {
    addVariantId.value = null;
    addVariantOptions.value = [];
    if (!productId) return;
    variantLoading.value = true;
    try {
        const { data } = await Axios.get(`${SELECTED_PRODUCT_ATTRIBUTE}/${productId}`);
        addVariantOptions.value = Array.isArray(data) ? data : (data?.data ?? []);
    } catch (e) {
        console.error(e);
    } finally {
        variantLoading.value = false;
    }
});

const applyProduct = () => {
    if (!addProductId.value) { toast.error("Please select a product"); return; }
    if (!addVariantId.value) { toast.error("Please select a variant"); return; }
    if (!addSerial.value.trim()) { toast.error("Please enter serial / IMEI"); return; }

    const variant = addVariantOptions.value.find(v => v.id == addVariantId.value);
    addProduct({
        id: addProductId.value,
        name: variant?.product_name ?? '',
        product_variant_id: addVariantId.value,
        serial_number: addSerial.value.trim(),
        sku: variant?.sku ?? '',
        max_retail_price: variant?.max_retail_price ?? 0,
        sale_price: variant?.max_retail_price ?? 0,
        purchase_price: null,
        attribute_values: variant?.attributes ?? '',
        sale_type: saleType.value,
    });
    addSerial.value = '';
    addProductId.value = null;
    addVariantId.value = null;
    addVariantOptions.value = [];
};

const searchQuery = ref("");
const products = ref([]);
const { isModalActive, closeModal } = useOpenModal()


// Search Product
const searchPreloader = ref(false)
const searchProducts = async () => {
    if (!profileData?.value?.tenant && !formData.value.tenant_id) {
        toast.error("Please select warehouse/store")
        searchPreloader.value = false;
        return;
    }
    const url = !profileData?.value?.tenant ? `${SALE_SEARCH_PRODUCTS}?tenant_id=${formData.value.tenant_id}` : SALE_SEARCH_PRODUCTS
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
        searchPreloader.value = false;
    }
};

const debounceTimer = ref(null);
watch(searchQuery, () => {
    clearTimeout(debounceTimer.value);
    if (!searchQuery.value.trim()) {
        products.value = [];
        searchPreloader.value = false;
        return;
    }
    searchPreloader.value = true;
    debounceTimer.value = setTimeout(searchProducts, 400);
});
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
            price: product.max_retail_price,
            quantity: 1,
            discount_type: '',
            discount_amount: '',
            selected_care_id: null,
            cares: product.cares || [],
            note: '',
            sale_type: product.sale_type,
            showPurchasePrice: false,
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

    if (salePrice < 0) {
        salePrice = 0;
    }

    // exchange items have no MRP constraint
    if (product.sale_type !== 'exchange') {
        const unitPrice = Number(product.price) || 0;
        if (salePrice > unitPrice) {
            salePrice = unitPrice;
        }
    }

    product.sale_price = salePrice;
};

const calculateProductTotal = (product) => {
    const salePrice = product.sale_price
        ? Number(product.sale_price)
        : 0;

    const amount = salePrice * product.quantity;
    return product.sale_type === 'exchange' ? -amount : amount;
};

// Subtotal for all products
const subtotal = computed(() => {
    return productList.value.reduce((sum, product) => {
        return sum + calculateProductTotal(product);
    }, 0);
});


const calculateDiscount = (product) => {
    if (product.sale_type === 'exchange') return 0;

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

const syncCareCharges = () => {
    let totalCare = 0;
    const careNames = [];

    for (const product of productList.value) {
        if (product.sale_type === 'exchange' || !product.selected_care_id || !Array.isArray(product.cares)) {
            continue;
        }
        const selected = product.cares.find((c) => Number(c.id) === Number(product.selected_care_id));
        if (selected) {
            const basePrice = Number(product.sale_price ?? product.price) || 0;
            const careAmount = Number((((basePrice * (Number(product.quantity) || 1)) * Number(selected.value || 0)) / 100).toFixed(2));
            totalCare += careAmount;
            product.note = `Product Care: ${selected.name} (৳${careAmount.toFixed(2)})`;
            if (!careNames.includes(selected.name)) {
                careNames.push(selected.name);
            }
        }
    }

    formData.value.additional_charge = Number(totalCare.toFixed(2));
    formData.value.additional_charge_name = careNames.join(', ').slice(0, 250);
};

const onCareChange = (product) => {
    if (!product.selected_care_id && product.note && product.note.startsWith('Product Care:')) {
        product.note = '';
    }
    syncCareCharges();
};

// Grand total
const grandTotal = computed(() => {
    return discountedSubtotal.value + taxAmount.value + shippingAmount.value + additionalChargeAmount.value;
});

const isExchangeOverpaid = computed(() => grandTotal.value < 0);
const sentPaymentAmount = computed(() => Math.abs(grandTotal.value));

watch(isExchangeOverpaid, (overpaid) => {
    if (overpaid) {
        payments.value = [{ payment_method_id: null, amount: 0 }];
    }
});

// Received Amount
const totalDeuAmount = computed(() => {
    const totalReceived = payments.value.reduce((sum, p) => sum + Number(p.amount || 0), 0);
    const due = grandTotal.value - totalReceived;
    return due < 0 ? 0 : due;
});


const errors = ref({});
const preloader = ref(false);
const isCustomerModalActive = ref(false)
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

    const data = {
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
                product_id: product.id,
                variant_id: product.product_variant_id,
                serial_number: product.serial_number,
                quantity: product.quantity,
                unit_price: product.sale_type === 'exchange' ? product.sale_price : product.price,
                discount_type: 'fixed',
                discount_amount: calculateDiscount(product),
                note: product.note,
                sale_type: product.sale_type ?? null,
            }
        })
    };


    try {
        const response = await Axios.post(SALES, data);
        toast.success(response.data?.message);

        // Clear form data after successful save
        formData.value = {
            date: new Date()
        };
        productList.value = [];
        payments.value = [{ payment_method_id: null, amount: 0 }];

        const saleId = response.data.id; // ensure your API returns sale ID
        if (saleId) {
            // Fetch the PDF as blob from the server
            const pdfResponse = await Axios.get(`${SALE_INVOICE_DOWNLOAD}/${saleId}?preview=preview`, { responseType: 'arraybuffer' });

            // Create a blob URL for the PDF data
            const blob = new Blob([pdfResponse.data], { type: 'application/pdf' });
            const blobUrl = window.URL.createObjectURL(blob);

            // Create an invisible iframe
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = 'none';
            iframe.src = blobUrl;

            document.body.appendChild(iframe);

            iframe.onload = () => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();

            };
        }

        // if (saleId) {
        //     // Fetch the PDF as blob from the server
        //     const pdfResponse = await Axios.get(`${SALE_INVOICE_DOWNLOAD}/${saleId}?preview=preview`, { responseType: 'arraybuffer' });

        //     // Create a blob URL for the PDF data
        //     const blob = new Blob([pdfResponse.data], { type: 'application/pdf' });
        //     const blobUrl = window.URL.createObjectURL(blob);

        //     // Open a new window with the blob URL
        //     const printWindow = window.open(blobUrl, '_blank');

        //     if (printWindow) {
        //         printWindow.onload = () => {
        //             // printWindow.focus();
        //             printWindow.print();
        //             setTimeout(() => {
        //                 window.URL.revokeObjectURL(blobUrl);
        //             }, 1000);
        //         };
        //     } else {
        //         toast.error("Unable to open print window. Please allow popups for this site.");
        //     }
        // }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || "Failed to save sale.");
        }
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
    const cap = isExchangeOverpaid.value ? sentPaymentAmount.value : grandTotal.value;
    const remaining = cap - payments.value.reduce((sum, p, i) => {
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
        const cap = isExchangeOverpaid.value ? sentPaymentAmount.value : grandTotal.value;

        if (total > cap) {
            let excess = total - cap;

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
        <app-breadcrumb :page-title="$t('create_sale')" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="d-flex flex-wrap gap-2 align-items-stretch">
                            <div class="flex-grow-1 min-w-0">
                                <app-input type="advance-search-select" id="customer" :label="$t('customer_info')"
                                    label-required list-key-field="id" list-value-field="name" :loading-length="10"
                                    v-model="formData.contact_id" :fetch-url="SELECTED_CONTACTS"
                                    :choose-label="$t('choose_a_customer')" :errors="$errors(errors, 'contact_id')"
                                    @searchValue="searchCustomer($event)">
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
                            <div v-if="canAccess('create_customers')" class="flex-shrink-0 d-flex flex-column">
                                <label class="mb-2 invisible" aria-hidden="true">_</label>
                                <button class="btn btn-primary flex-grow-1" @click="isCustomerModalActive = true">{{
                                    $t('add_customer') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6" v-if="!profileData?.tenant">
                        <app-input type="advance-search-select" id="shop_id" :label="$t('warehouse_or_store_name')"
                            label-required list-value-field="name" v-model="formData.tenant_id" :loading-length="10"
                            :fetch-url="SELECTED_SHOPS" :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, 'tenant_id')" />
                    </div>
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
                    <div class="col-12 col-md-6">
                        <app-input type="date" id="date" :label="$t('date')" label-required v-model="formData.date"
                            :errors="$errors(errors, 'date')" auto-apply :enable-time-picker="false"
                            :placeholder="$t('dd/mm/yy')" />
                    </div>
                    <div class="col-12">
                        <app-input type="textarea" id="address" :label="$t('address')" v-model="formData.address"
                            :errors="$errors(errors, 'address')" :placeholder="$t('address')" />
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-end">
                        <div class="sale-type-toggle">
                            <button type="button" :class="['toggle-btn', { active: saleType === 'product' }]"
                                @click="saleType = 'product'">
                                {{ $t('product') }}
                            </button>
                            <button type="button" :class="['toggle-btn', { active: saleType === 'exchange' }]"
                                @click="saleType = 'exchange'">
                                {{ $t('exchange') }}
                            </button>
                        </div>
                    </div>
                    <div v-if="saleType" class="col-12">
                        <h6 class="mb-3 fw-semibold text-primary">
                            {{ saleType === 'product' ? $t('product') : $t('exchange') }}
                        </h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <app-input type="advance-search-select" id="add_product" :label="$t('product')"
                                    label-required list-value-field="name" :loading-length="10" v-model="addProductId"
                                    :fetch-url="SELECTED_PRODUCT" :choose-label="$t('choose_a_product')" />
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="mb-2">{{ $t('variant') }}</label>
                                <app-input type="select" id="add_variant" :options="addVariantOptions"
                                    list-value-field="attributes"
                                    :choose-label="variantLoading ? $t('loading') : $t('choose_a_variant')"
                                    :disabled="!addVariantOptions.length" v-model="addVariantId" />
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="mb-2">{{ $t('serial_imei_no') }}</label>
                                <div class="d-flex gap-2">
                                    <app-input type="text" id="add_serial" v-model="addSerial"
                                        :placeholder="$t('serial_imei_no')" @keyup.enter="applyProduct" />
                                    <button type="button" class="btn btn-primary flex-shrink-0 px-3"
                                        @click="applyProduct">
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
                                <div v-if="searchPreloader"
                                    class="d-flex justify-content-center align-items-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <template v-else>
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
                                </template>
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
                                            <div v-if="product.cares && product.cares.length && product.sale_type !== 'exchange'" class="mt-2">
                                                <select class="form-select form-select-sm"
                                                    v-model="product.selected_care_id"
                                                    @change="onCareChange(product)">
                                                    <option :value="null">No Care</option>
                                                    <option v-for="care in product.cares" :key="care.id" :value="care.id">
                                                        {{ care.name }} ({{ care.value }}%)
                                                    </option>
                                                </select>
                                            </div>
                                            <div v-if="product.note" class="mt-1">
                                                <small class="text-primary">{{ product.note }}</small>
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
                                            <span v-if="product.showPurchasePrice" class="me-1">{{
                                                product.purchase_price }}</span>
                                            <button class="btn btn-sm p-0"
                                                @click="product.showPurchasePrice = !product.showPurchasePrice">
                                                <Eye v-if="product.showPurchasePrice" width="14" height="14" />
                                                <EyeClosed v-else width="14" height="14" />
                                            </button>
                                        </td>
                                        <td>
                                            <app-input type="number" class="w-70" :id="`mrp_${index}`"
                                                v-model="product.price" placeholder="MRP" @keydown="blockInvalidKeys"
                                                @paste="blockNegativePaste" min="1"
                                                :disabled="product.sale_type === 'exchange'"
                                                :errors="getItemsError('price', index)" />

                                        </td>
                                        <td>
                                            <app-input type="number" class="w-70" :id="`sale_price_${index}`"
                                                v-model="product.sale_price" placeholder="Sale Price"
                                                @keydown="blockInvalidKeys" @paste="blockNegativePaste" min="1"
                                                @input="() => { validateDiscountAmount(product); syncCareCharges(); }"
                                                :errors="getItemsError('sale_price', index)" />
                                        </td>
                                        <td>{{ calculateDiscount(product) }}</td>

                                        <td :class="product.sale_type === 'exchange' ? 'text-danger' : ''">
                                            {{ calculateProductTotal(product) }}
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
                    <div class="col-12 col-lg-7">
                        <div class="row g-4">
                            <div class="col-6 col-md-6">
                                <app-input type="number" id="order_tax" v-model="formData.order_tax"
                                    :disabled="!productList.length" :label="$t('order_tax')"
                                    :placeholder="$t('order_tax')" min="0" max="100" @keydown="blockInvalidKeys"
                                    @paste="blockNegativePaste" @input="changeOrderTax" />

                            </div>
                            <div class="col-6 col-md-6">
                                <app-input type="number" id="shipping" :label="$t('shipping')"
                                    v-model="formData.shipping_cost" min="1" @keydown="blockInvalidKeys"
                                    @paste="blockNegativePaste" :disabled="!productList.length"
                                    :placeholder="$t('shipping_cost')" />
                            </div>
                            <div class="col-6 col-md-6">
                                <app-input type="text" id="additional_charge_name" :label="$t('additional_charge_name')"
                                    v-model="formData.additional_charge_name" :disabled="!productList.length"
                                    :placeholder="$t('additional_charge_name')" />
                            </div>
                            <div class="col-6 col-md-6">
                                <app-input type="number" id="additional_charge" :label="$t('additional_charge')"
                                    v-model="formData.additional_charge" min="0" @keydown="blockInvalidKeys"
                                    @paste="blockNegativePaste" :disabled="!productList.length"
                                    :placeholder="$t('enter_additional_charge')" />
                            </div>

                        </div>
                    </div>
                    <div class="col-12 col-lg-5">
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

                                <!-- Exchange overpaid: show Sent Payment -->
                                <template v-if="isExchangeOverpaid">
                                    <tr>
                                        <td class="px-4 py-3 text-danger fw-semibold align-top">Sent Payment</td>
                                        <td class="px-4 py-3 text-danger fw-semibold">
                                            {{ numberWithCurrencySymbol(sentPaymentAmount) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-primary align-top">Pay via</td>
                                        <td class="px-4 py-3 text-primary">
                                            <div class="d-flex flex-column gap-2 payment-wrapper mb-2"
                                                v-for="(payment, index) in payments" :key="index">
                                                <app-input type="number" :id="`sent_amount_${index}`"
                                                    v-model="payment.amount" placeholder="Amount"
                                                    @keydown="blockInvalidKeys" @paste="blockNegativePaste"
                                                    @input="validatePaymentAmount(payment, index)" min="0" />
                                                <app-input type="select" :id="`sent_payment_method_${index}`"
                                                    :options="availablePaymentMethods(index)" list-value-field="name"
                                                    :choose-label="$t('choose_a_payment_method')" :showSearch="true"
                                                    v-model="payment.payment_method_id" />
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
                                    <tr
                                        v-if="paymentMethodList?.length && payments.length < paymentMethodList.length && totalReceivedAmount < sentPaymentAmount">
                                        <td colspan="2">
                                            <button type="button" class="btn btn-primary btn-sm d-block ml-auto"
                                                @click="addPayment">
                                                + {{ $t('add_payment_method') }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-primary">Total Sent</td>
                                        <td class="px-4 py-3 text-primary">{{
                                            numberWithCurrencySymbol(totalReceivedAmount) }}</td>
                                    </tr>
                                </template>

                                <!-- Normal: show Received Amount section -->
                                <template v-else>
                                    <tr>
                                        <td class="px-4 py-3 text-primary align-top">{{ $t('received_amount') }}</td>
                                        <td class="px-4 py-3 text-primary">
                                            <div class="d-flex flex-column gap-2 payment-wrapper mb-2"
                                                v-for="(payment, index) in payments" :key="index">
                                                <app-input type="number" :id="`received_amount_${index}`"
                                                    v-model="payment.amount" :placeholder="$t('received_amount')"
                                                    @keydown="blockInvalidKeys" @paste="blockNegativePaste"
                                                    @input="validatePaymentAmount(payment, index)" min="0"
                                                    :errors="$errors(errors, `payments.${index}.amount`)" />
                                                <app-input type="select" :id="`payment_method_${index}`"
                                                    :options="availablePaymentMethods(index)" list-value-field="name"
                                                    :choose-label="$t('choose_a_payment_method')" :showSearch="true"
                                                    v-model="payment.payment_method_id"
                                                    :errors="$errors(errors, `payments.${index}.payment_method_id`)" />
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
                                            numberWithCurrencySymbol(totalReceivedAmount) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-primary">{{ $t('due_amount') }}</td>
                                        <td class="px-4 py-3 text-primary">{{ numberWithCurrencySymbol(totalDeuAmount)
                                        }}</td>
                                    </tr>
                                </template>
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
.summary-table td {
    @media (max-width: 575px) {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
}

.sale-type-toggle {
    display: inline-flex;
    background: #f0f2f5;
    border-radius: 12px;
    padding: 4px;
    gap: 4px;
    width: 100%;

    .toggle-btn {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        border-radius: 9px;
        padding: 8px 20px;
        font-size: 0.875rem;
        font-weight: 500;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.25s ease;
        white-space: nowrap;

        &:hover:not(.active) {
            color: var(--primary);
            background: rgba(255, 255, 255, 0.7);
        }

        &.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            font-weight: 600;
        }
    }
}

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

<script setup>
import { ref } from 'vue';
import {
    SELECTED_PRODUCT,
    SELECTED_PRODUCT_ATTRIBUTE,
    PRODUCT_BARCODE_GENERATE,
    PRODUCT_MULTI_BARCODE_PDF,
    SELECTED_SHOPS,
} from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import { Trash } from '@iconoir/vue';
import usePermission from "@/composable/permission/usePermission.js";


const props = defineProps({
    id: {},
});

const { isSupperAdmin, canFilterByShop } = usePermission();
const formData = ref({});
const errors = ref({});
const selectedProducts = ref([]);
const toast = useToast();
const attributeValueList = ref([]);

// ------------------------------
// Helper: generate 10-digit unique random serial
// ------------------------------
const generatedSerials = new Set();

const generateRandomSerial = () => {
    let serial;
    do {
        serial = Math.floor(1000000000 + Math.random() * 9000000000).toString();
    } while (generatedSerials.has(serial));
    generatedSerials.add(serial);
    return serial;
};

const changeProduct = (product) => {
    attributeValueList.value = []
    if (!product || !product.id) return;
    formData.value.variant_id = null;
    formData.value.sku = '';
    formData.value.attribute_id = null;
    Axios.get(`${SELECTED_PRODUCT_ATTRIBUTE}/${product.id}`).then(({ data }) => {
        attributeValueList.value = data || [];
    });

};

const changeAttribute = (variant) => {
    if (!variant) return;

    const attrString = variant.attributes || '';
    console.log(variant)

    const existing = selectedProducts.value.find(p => p.id === variant.id);
    if (!existing) {
        selectedProducts.value.push({
            id: variant.id,                  // Variant ID
            product_id: variant.product_id,  // Main product ID
            name: variant.product_name || '',
            warranty: variant.warranty_day || null,
            sku: variant.sku || '',
            max_retail_price: variant.max_retail_price || 0,
            attribute: attrString,
            quantity: 1,
            barcode: variant.barcode || '',
            serialNumbers: ['']
        });
    }
};

// Barcode generation
const barcodeList = ref([]);

// Remove product
const removeProduct = (index) => {
    selectedProducts.value.splice(index, 1);
};
const syncSerialNumbers = (product) => {
    if (!product.serialNumbers) product.serialNumbers = [];

    const diff = product.quantity - product.serialNumbers.length;

    if (diff > 0) {
        // Add new serials when quantity increases
        for (let i = 0; i < diff; i++) {
            product.serialNumbers.unshift('');
        }
    } else if (diff < 0) {
        // Remove extra serials when quantity decreases
        product.serialNumbers.splice(0, Math.abs(diff));
    }
};

const increaseQty = (product) => {
    product.quantity++;
    syncSerialNumbers(product);
};

const decreaseQty = (product) => {
    if (product.quantity > 1) {
        product.quantity--;
        syncSerialNumbers(product);
    }
};


// Barcode Generation
const generateBarcodeList = async () => {
    try {
        const items = [];
        errors.value = {};
        selectedProducts.value.forEach(product => {
            product.serialNumbers.forEach(serialNumber => {
                items.push({
                    serial_number: serialNumber,
                    max_retail_price: product.max_retail_price,
                    variant_id: product.id,
                    product_id: product.product_id,
                })
            })
        });
        // send to backend
        const { data } = await Axios.post(PRODUCT_BARCODE_GENERATE, {
            items: items,
            tenant_id: formData.value.tenant_id,
        });

        barcodeList.value = data;



    } catch ({ response }) {
        if (response.status === 422) {
            errors.value = response.data.errors;
        }
        toast.error(response.data.message);
    }
};

// For multiple barcodes
const printMultipleBarcodes = async () => {
    if (!barcodeList.value || !barcodeList.value.length) {
        toast.error('No barcodes to print');
        return;
    }

    try {
        const { data } = await Axios.post(PRODUCT_MULTI_BARCODE_PDF, {
            barcodes: Array.isArray(barcodeList.value) ? barcodeList.value : [barcodeList.value]
        }, { responseType: "text" });

        const printWindow = window.open('', '', 'width=700,height=500');
        printWindow.document.open();
        printWindow.document.write(data);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 500);

    } catch (error) {
        console.log(error, "Error");

        const response = error?.response;
        if (response?.status === 422) {
            errors.value = response.data?.errors;
        }
        toast.error(response?.data?.message || 'Failed to print barcodes');
    }
};


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Create barcode" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">

                    <div class="col-xl-4 col-md-6">
                        <app-input type="advance-search-select" id="product" :label="$t('product')" label-required
                            list-value-field="name" v-model="formData.product_id" :loading-length="10"
                            :fetch-url="SELECTED_PRODUCT" :choose-label="$t('choose_a_product')"
                            :errors="$errors(errors, 'product_id')" @searchSelect="changeProduct($event)" />
                    </div>

                    <div class="col-xl-4 col-md-6"
                        :style="[formData.product_id ? {} : 'pointer-events: none; cursor: not-allowed;']">
                        <app-input type="select" :showSearch="true" id="attribute" list-value-field="attributes"
                            list-key-field="id" :label="$t('attribute')" v-model="formData.attribute_id"
                            :options="attributeValueList" :choose-label="$t('choose_an_attribute')"
                            :selected-disable="false" :errors="$errors(errors, 'attribute_id')"
                            @selectInput="changeAttribute($event)" />
                    </div>

                    <div class="col-xl-4 col-md-6" v-if="isSupperAdmin()">
                        <app-input type="advance-search-select" id="warehouse" :label="$t('warehouse')" label-required
                            list-value-field="name" v-model="formData.tenant_id" :loading-length="10"
                            :fetch-url="SELECTED_SHOPS" :choose-label="$t('choose_a_warehouse')"
                            :errors="$errors(errors, 'tenant_id')" />
                    </div>


                    <div class="col-12">
                        <label for="warehouse" class="mb-2">{{ $t('order') }} <span class="text-danger">*</span></label>
                        <div class="table-responsive">
                            <table class="table purchase-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Retail Price</th>
                                        <th>Quantity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(product, index) in selectedProducts" :key="product.id">
                                        <td>
                                            <div>{{ product.name }}</div>
                                            <div v-if="product.attribute" class="badge-square-light bg-success">
                                                {{ product.attribute }}
                                            </div>
                                        </td>
                                        <td>{{ product.sku }}</td>
                                        <td>
                                            <app-input type="number" :id="`max_retail_price_${index}`"
                                                v-model="product.max_retail_price"
                                                :errors="$errors(errors, `max_retail_price.${index}`)" />
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <button class="btn btn-sm btn-outline-secondary"
                                                    @click="decreaseQty(product)" :disabled="product.quantity <= 1">
                                                    -
                                                </button>

                                                <input type="number" v-model.number="product.quantity" min="1"
                                                    class="form-control w-50 text-center"
                                                    @input="syncSerialNumbers(product)" />

                                                <button class="btn btn-sm btn-outline-secondary"
                                                    @click="increaseQty(product)">
                                                    +
                                                </button>
                                            </div>

                                            <div v-for="(serial, serialIndex) in product.serialNumbers"
                                                :key="serialIndex" class="mb-1">
                                                <div class="d-flex gap-2">
                                                    <app-input :id="`serial-${serialIndex}`" type="text"
                                                        v-model="product.serialNumbers[serialIndex]"
                                                        placeholder="Enter Serial Number" autofocus />

                                                    <button class="btn btn-sm btn-warning"
                                                        @click="product.serialNumbers[serialIndex] = generateRandomSerial()">
                                                        Suggest
                                                    </button>
                                                </div>
                                                <!-- Error message -->
                                                <div v-if="errors[`items.${serialIndex}.serial_number`]"
                                                    class="text-danger small">
                                                    {{ errors[`items.${serialIndex}.serial_number`][0] }}
                                                </div>

                                            </div>

                                        </td>

                                        <td>
                                            <button @click="removeProduct(index)" class="del-btn">
                                                <Trash />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-3">
                        <!-- Generate Barcode Button -->
                        <button @click="generateBarcodeList" class="btn btn-primary">
                            {{ $t('generate_barcode') }}
                        </button>
                    </div>

                    <div class="col-12 mt-3" v-if="barcodeList.length > 0">

                        <div class="mt-3 mb-3">
                            <div class="col-12 d-flex justify-content-end gap-3">
                                <!-- Print Barcode Button -->
                                <button @click="printMultipleBarcodes" class="btn btn-secondary">
                                    {{ $t('print_barcodes') }}
                                </button>
                            </div>
                        </div>

                        <div class="barcode-wrapper">
                            <div class="barcode-card" v-for="(barcode, index) in barcodeList" :key="index">

                                <div class="product-name text-black">{{ barcode.product_name }}</div>

                                <div class="variant-label">
                                    Variant: {{ barcode.attribute_values || '' }}
                                </div>

                                <div class="price">
                                    MRP - {{ barcode.price || '0.00' }}
                                </div>

                                <div class="barcode-bottom">
                                    <img :src="barcode.barcode" alt="Barcode" />
                                    <div class="serial-small">{{ barcode.serial_number }}</div>
                                </div>

                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.barcode-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.barcode-card {
    width: 320px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1.25rem 1rem;
    text-align: center;
    background: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.product-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    line-height: 1.2;
}

.variant-label {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.price {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.barcode-bottom {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.barcode-bottom img {
    max-width: 100%;
    height: 70px;
    object-fit: contain;
}

.serial-small {
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 2px;
    margin-top: 0.25rem;
}

/* Print-specific layout for the popup window */
@media print {
    .barcode-card {
        page-break-inside: avoid;
        border: none;
    }
}
</style>

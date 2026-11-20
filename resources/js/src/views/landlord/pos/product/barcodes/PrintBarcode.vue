<script setup>
import { Search, Trash } from "@iconoir/vue";
import { ref, watch } from "vue";
import Axios from "@/services/axios/index.js";
import {
    PRODUCT_PRINT_BARCODES,
    SEARCH_PRODUCT_SERIAL
} from "@/services/endpoints/landlord.js";
import { useToast } from "vue-toastification";

const toast = useToast();
const searchQuery = ref('');
const products = ref([]);
let timeout = null;
watch(searchQuery, (newValue) => {
    clearTimeout(timeout);

    timeout = setTimeout(() => {
        searchProducts(newValue);
    }); // debounce 400ms
});
const searchLoader = ref(false)
const searchProducts = async (query) => {
    if (!query.trim()) {
        products.value = [];
        return;
    }

    searchLoader.value = true;

    try {
        const { data } = await Axios.get(SEARCH_PRODUCT_SERIAL, {
            params: {
                search: query
            }
        });

        const result = data?.result ?? [];

        products.value = result;

        // ✅ AUTO ADD IF SINGLE MATCH
        if (result.length === 1) {
            addProduct(result[0]);
        }

    } catch (error) {
        products.value = [];
    } finally {
        searchLoader.value = false;
    }
};

const selectedProducts = ref([]);

const addProduct = (product) => {
    const exists = selectedProducts.value.some(
        item => item.serial_number === product.serial_number
    );

    if (exists) {
        toast.error("Product already added")
        return
    }
    selectedProducts.value.push(product);

    searchQuery.value = '';
    products.value = [];
};

const removeProduct = (index) => {
    selectedProducts.value.splice(index, 1);
}

const blockInvalidKeys = (e) => {
    const invalidKeys = ['e', 'E', '+', '-', 'Minus', 'Equal'];
    if (
        invalidKeys.includes(e.key) ||
        invalidKeys.includes(e.code)
    ) {
        e.preventDefault();
    }
}

const blockNegativePaste = (e) => {
    const pasted = (e.clipboardData || window.clipboardData).getData('text');
    if (/[eE\-+]/.test(pasted)) {
        e.preventDefault();
    }
};

const preloader = ref(false)
const handelSubmit = async () => {


    try {
        preloader.value = true;
        const { data } = await Axios.post(PRODUCT_PRINT_BARCODES,
            { items: selectedProducts.value },
            { responseType: "text" });

        const printWindow = window.open('', '', 'width=700,height=500');
        printWindow.document.open();
        printWindow.document.write(data);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 500);

    } catch (error) {
        toast.error("Print failed");
    } finally {
        preloader.value = false
    }

    // try {
    //     preloader.value = true;
    //     const {data} = await Axios.post(PRODUCT_PRINT_BARCODES, {
    //         items: selectedProducts.value
    //     }, {responseType: 'blob'})
    //     const file = new Blob([data], {type: 'application/pdf'});
    //     const fileURL = URL.createObjectURL(file);
    //     window.open(fileURL);
    //
    // } catch (error) {
    //
    //     if (error.response?.data instanceof Blob) {
    //
    //         const text = await error.response.data.text();
    //
    //         try {
    //             const json = JSON.parse(text);
    //
    //             toast.error(json.message);
    //
    //         } catch {
    //             toast.error('Print failed');
    //         }
    //
    //     } else {
    //         toast.error('Print has been failed');
    //     }
    // } finally {
    //     preloader.value = false
    // }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Print Barcode" />
        <div class="content">
            <div class="card">
                <div class="card-body">
                    <div class="col-12">
                        <label for="search" class="mb-2">{{ $t('product') }} </label>
                        <div class="search-input position-relative">
                            <span class="search-icon">
                                <Search />
                            </span>

                            <app-input type="text" class="form-control rounded-default px-3 ps-5" id="search"
                                v-model="searchQuery" :placeholder="$t('search_product_by_code')" autofocus />

                            <div v-if="searchQuery.trim().length > 0" class="product-dropdown-list shadow-sm">
                                <!-- 🔄 Loader -->
                                <ul v-if="searchLoader">
                                    <li class="product-list text-center">
                                        <app-button-loader />
                                        Searching...
                                    </li>
                                </ul>

                                <ul v-else-if="products.length > 0">
                                    <li class="product-list" v-for="product in products" :key="product.id">
                                        <div class="fw-bold" @click="addProduct(product)">{{
                                            product.name
                                        }}({{ product.serial_number }})
                                        </div>
                                    </li>
                                </ul>
                                <ul v-else>
                                    <li class="product-list">No Product found</li>
                                </ul>
                            </div>

                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <div class="table-responsive">
                            <table class="table purchase-table">
                                <thead>
                                    <tr>
                                        <th>{{ $t('sl') }}</th>
                                        <th>{{ $t('product_name') }}</th>
                                        <th>{{ $t('serial_imei_no') }}</th>
                                        <th>{{ $t('mrp') }}</th>
                                        <th>{{ $t('action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="selectedProducts.length" v-for="(product, index) in selectedProducts"
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
                                        </td>

                                        <td>
                                            <div class="d-flex">
                                                <div class="price">
                                                    <app-input type="number" class="w-70" :id="`mrp_${index}`"
                                                        placeholder="MRP" v-model="product.max_retail_price" min="1"
                                                        @keydown="blockInvalidKeys" @paste="blockNegativePaste" />
                                                </div>

                                            </div>
                                        </td>

                                        <td>
                                            <button type="button" @click.prevent="removeProduct(index)" class="del-btn">
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
                        <div class="mt-3">
                            <div class="col-12 d-flex justify-content-end gap-3">
                                <button class="btn btn-success text-white" @click.prevent="handelSubmit">
                                    <app-button-loader v-if="preloader" />
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped lang="scss"></style>

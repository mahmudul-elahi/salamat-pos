<script setup>
import { ref, watch } from 'vue';
import {
    ADJUSTMENTS,
    SEARCH_PRODUCTS,
    SELECTED_WAREHOUSES
} from '@/services/endpoints/landlord.js';
import Axios from "@/services/axios";
import { formDataAssigner, serverDateFormat } from "@/utils/helper.js";
import { useToast } from "vue-toastification";
import {Search, Trash} from "@iconoir/vue";
const formData = ref({});
const props = defineProps({
    id: {},
});


const toast = useToast();
const searchQuery = ref("");
const products = ref([]);
const selectedProducts = ref([]);
const loading = ref(false);

const errors = ref({});
const preloader = ref(false);

const searchProducts = async () => {
    if (!searchQuery.value.trim()) {
        products.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await Axios.get(SEARCH_PRODUCTS, {
            params: { query: searchQuery.value }
        });
        products.value = response.data?.data ?? [];

    } catch (error) {
        products.value = [];
    } finally {
        loading.value = false;
    }
};

const addProduct = (product) => {
    const existingProduct = selectedProducts.value.find(p => p.id === product.id);

    if (existingProduct) {
        existingProduct.quantity++;
    } else {
        selectedProducts.value.push({
            id: product.id,
            name: product.name,
            price: product.selling_price,
            quantity: 1
        });
    }
    searchQuery.value = "";
    products.value = [];
};

const removeProduct = (index) => {
    selectedProducts.value.splice(index, 1);
};
watch(searchQuery, searchProducts);

const resetData = () => {
    formData.value = {};
    searchQuery.value = "";
    products.value = [];
    selectedProducts.value = [];
    errors.value = {};

};

const submit = async () => {
    errors.value = {}; 
    const adjustmentDetails = selectedProducts.value.map(product => ({
        product_id: product.id,
        quantity: product.quantity,
        type: formData.value.type,
    }));

    const data = formDataAssigner(new FormData(), {
        ...formData.value,
        date: serverDateFormat(formData.value.date),
        adjustment_details: adjustmentDetails
    });

    preloader.value = true;

    try {
        await Axios.post(ADJUSTMENTS, data, {
            headers: { 'Content-Type': 'multipart/form-data' }
        }).then((response) => {
            toast.success(response.data?.message);
            resetData();
        });
    } catch (error) {
        
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || "An error occurred");
        }
    } finally {
        preloader.value = false;
    }
};




</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('create_adjustment')" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">                
                    <div class="col-md-6">
                        <app-input
                            type="advance-search-select"
                            id="warehouse"
                            :label="$t('warehouse')"
                            label-required
                            list-value-field="name"
                            v-model="formData.warehouse_id"
                            :loading-length="10"
                            :fetch-url="SELECTED_WAREHOUSES"
                            :choose-label="$t('choose_a_warehouse')"
                            :errors="$errors(errors, 'warehouse_id')" />
                    </div>
                    <div class="col-md-6">
                        <app-input
                            type="date"
                            id="name"
                            :label="$t('date')"
                            label-required
                            v-model="formData.date"
                            :enable-time-picker="false"
                            :placeholder="$t('dd/mm/yy')"
                            :errors="$errors(errors, 'date')" />
                    </div>
                    <div class="col-12">
                        <label for="warehouse" class="mb-2">{{ $t('product') }} <span class="text-danger">*</span></label>
                        <div class="search-input position-relative">
                            <span class="search-icon">
                                <Search />
                            </span>
                            <input v-model="searchQuery" :placeholder="$t('search_product_by_code')" class="form-control rounded-default px-3 ps-5">
                            <ul v-if="products.length" class="product-dropdown-list shadow-sm">
                                <li v-for="product in products" :key="product.id" @click="addProduct(product)" class="product-list">
                                    {{ product.name }} - {{ product.selling_price }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12">
                        <div>
                            <table class="table purchase-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                        <th>Quantity</th>
                                        <th>Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(product, index) in selectedProducts" :key="product.id">
                                        <td>{{ product.name }}</td>
                                        <td>{{ product.price }}</td>
                                        <td>
                                            <input type="number" v-model="product.quantity" min="1" class="form-control w-50" />
                                        </td>
                                        <td>
                                            <app-input
                                                type="select"
                                                id="type"
                                                label-required
                                                :options="[
                                                    { id: 'addition', value: 'Addition' },
                                                    { id: 'subtract', value: 'Subtract' }
                                                ]"
                                                :choose-label="$t('choose_a_type')"
                                                v-model="formData.type"
                                                :errors="$errors(errors, 'type')" />
                                        </td>
                                        <td>
                                            <button @click="removeProduct(index)" class="del-btn"><Trash /> </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-12">
                        <app-input
                            type="textarea"
                            id="note"
                            :label="$t('note')"
                            :placeholder="$t('note')"
                            v-model="formData.note"
                            :errors="$errors(errors, 'note')" />
                    </div>
                    <div class="mt-3">
                        <div class="col-12 d-flex justify-content-end gap-3">
                            <button class="btn btn-success text-white" @click.prevent="submit">
                                <!--                        <app-button-loader v-if="preloader"/>-->
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

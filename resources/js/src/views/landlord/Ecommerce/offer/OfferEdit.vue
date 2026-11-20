<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import { Trash } from '@iconoir/vue';
import { OFFERS } from '@/services/endpoints/Ecommerce.js';
import { SELECTED_PRODUCT, SELECTED_PRODUCT_ATTRIBUTE } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";

import { format } from "date-fns";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import Axios from "@/services/axios/index.js";

const typeOptions = [
    { id: 'campaign', name: 'Campaign' },
    { id: 'flash_sale', name: 'Flash Sale' },
    { id: 'coupon', name: 'Coupon' },
];

const mediaModalActive = ref(false);
const currentImageTarget = ref({ field: null, index: null });
const pageLoader = ref(true);
const preloader = ref(false);
const errors = ref({});

const formData = ref({
    products: [],
    title: '',
    description: '',
    type: 'campaign',
    discount_type: 'fixed',
    discount_amount: 0,
    start_at: null,
    end_at: null,
    banner: null,
    is_active: 1,
    priority: 0,
});

// Existing (persisted) rows the user removed via the delete icon - their
// `id`s ride along in the update payload so the backend deletes only these,
// instead of wiping and recreating every line item on every save.
const deletedProductIds = ref([]);

const route = useRoute();
const router = useRouter();
const toast = useToast();

const mediaLibraryModal = (field, index = null) => {
    currentImageTarget.value = { field, index };
    mediaModalActive.value = true;
};

const handleMediaSelected = (url) => {
    if (currentImageTarget.value.field === 'banner') {
        formData.value.banner = url;
    }
    closeMediaModalActive();
};

const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    currentImageTarget.value = { field: null, index: null };
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
};

// ── Product -> Variant cascading picker (add one row at a time) ──
const addProductId = ref(null);
const addVariantOptions = ref([]);
const addVariantId = ref(null);
const variantLoading = ref(false);

watch(addProductId, async (productId) => {
    addVariantId.value = null;
    addVariantOptions.value = [];
    if (!productId) return;

    variantLoading.value = true;
    try {
        const { data } = await Axios.get(`${SELECTED_PRODUCT_ATTRIBUTE}/${productId}`);
        addVariantOptions.value = Array.isArray(data) ? data : (data?.data ?? []);
    } finally {
        variantLoading.value = false;
    }
});

const addProductRow = () => {
    if (!addProductId.value) {
        toast.error('Please select a product');
        return;
    }
    if (!addVariantId.value) {
        toast.error('Please select a variant');
        return;
    }

    const variant = addVariantOptions.value.find((v) => v.id == addVariantId.value);
    if (!variant) return;

    const alreadyAdded = formData.value.products.some(
        (row) => row.product_id == addProductId.value && row.product_variant_id == addVariantId.value
    );
    if (alreadyAdded) {
        toast.error('This product variant is already added');
        return;
    }

    formData.value.products.push({
        id: null,
        product_id: Number(addProductId.value),
        product_name: variant.product_name,
        product_variant_id: variant.id,
        variant_label: variant.attributes,
        base_price: variant.max_retail_price ?? 0,
        discount_type: 'fixed',
        discount_amount: 0,
        special_price: null,
        sku: variant.sku,
    });

    // Keep the product selected (and its variant list loaded) since one
    // product often has several variants - only reset the variant picker so
    // the next one can be added immediately without re-searching the
    // product. Clear the "X" on the Products field to pick a different one.
    addVariantId.value = null;
};

const removeProductRow = (index) => {
    const row = formData.value.products[index];
    // Only persisted rows (they have a real offer_products id) need to be
    // reported for deletion - a row just added in this session was never
    // saved, so removing it from the array is enough.
    if (row?.id) {
        deletedProductIds.value.push(row.id);
    }
    formData.value.products.splice(index, 1);
};

onMounted(async () => {
    try {
        const { data } = await Axios.get(`${OFFERS}/${route.params.id}`);
        Object.assign(formData.value, data);

        formData.value.products = (data.products ?? []).map((row) => ({
            id: row.id,
            product_id: Number(row.product_id),
            product_name: row.product?.product_name,
            product_variant_id: row.product_variant_id,
            variant_label: row.variant_label,
            base_price: row.max_retail_price ?? 0,
            discount_type: row.discount_type ?? 'fixed',
            discount_amount: row.discount_amount ?? 0,
            special_price: row.special_price ?? null,
            sku: row.sku,
        }));
    } finally {
        pageLoader.value = false;
    }
});

const submit = async () => {
    try {
        preloader.value = true;
        errors.value = {};

        const payload = {
            ...formData.value,
            start_at: formData.value.start_at
                ? format(new Date(formData.value.start_at), 'yyyy-MM-dd HH:mm')
                : null,
            end_at: formData.value.end_at
                ? format(new Date(formData.value.end_at), 'yyyy-MM-dd HH:mm')
                : null,
            products: (formData.value.products ?? []).map((row) => ({
                ...(row.id ? { id: row.id } : {}),
                product_id: Number(row.product_id),
                product_variant_id: Number(row.product_variant_id),
                discount_type: row.discount_type,
                discount_amount: row.discount_amount ?? 0,
                special_price: row.special_price ?? null,
            })),
            deleted_product_ids: deletedProductIds.value,
        };

        await Axios.put(`${OFFERS}/${route.params.id}`, payload);
        await router.push({ name: 'OfferList' });
    } catch (err) {
        if (err?.response?.status === 422) {
            errors.value = err.response.data.errors ?? {};
        }
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('edit_offer')" />
        <app-loader v-if="pageLoader" />
        <form v-else>
            <div class="row g-4">
                <div class="col-md-8">
                    <app-input id="title" :label="$t('title')" label-required v-model="formData.title"
                        placeholder="Enter offer title" :errors="$errors(errors, 'title')" />
                </div>
                <div class="col-md-4">
                    <Attachment :label="$t('banner')" id="banner" v-model="formData.banner"
                        :error="$errors(errors, 'banner')" @click="mediaLibraryModal('banner')" :showInput="false" />
                </div>
                <div class="col-md-12">
                    <app-input type="editor" id="description" :label="$t('description')" v-model="formData.description"
                        :errors="$errors(errors, 'description')" />
                </div>

                <div class="col-md-3">
                    <app-input type="date" id="start_at" :label="$t('start_at')" label-required
                        v-model="formData.start_at" :errors="$errors(errors, 'start_at')" auto-apply
                        :placeholder="$t('dd/mm/yy hh:mm')" />
                </div>
                <div class="col-md-3">
                    <app-input type="date" id="end_at" :label="$t('end_at')" label-required v-model="formData.end_at"
                        :errors="$errors(errors, 'end_at')" auto-apply :placeholder="$t('dd/mm/yy hh:mm')" />
                </div>
                <div class="col-md-3">
                    <app-input id="type" type="select" :label="$t('type')" label-required :options="typeOptions"
                        list-value-field="name" v-model="formData.type" :errors="$errors(errors, 'type')"
                        :selected-disable="false" />
                </div>
                <div class="col-md-3">
                    <app-input id="is_active" type="select" :label="$t('status')"
                        :options="[{ id: 1, name: 'Active' }, { id: 0, name: 'Inactive' }]" list-value-field="name"
                        v-model="formData.is_active" :errors="$errors(errors, 'is_active')" :selected-disable="false" />
                </div>

                <div class="col-md-5">
                    <app-input type="advance-search-select" id="add_product" :label="$t('products')"
                        list-value-field="name" :loading-length="10" v-model="addProductId"
                        :fetch-url="SELECTED_PRODUCT" :choose-label="$t('select_products')"
                        :errors="$errors(errors, 'products')" />
                </div>
                <div class="col-md-5">
                    <label class="mb-2">{{ $t('variant') }}</label>
                    <app-input type="select" id="add_variant" :options="addVariantOptions"
                        list-value-field="attributes"
                        :choose-label="variantLoading ? $t('loading') : $t('choose_a_variant')"
                        :disabled="!addVariantOptions.length" v-model="addVariantId" :selected-disable="false" />
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" @click="addProductRow">
                        {{ $t('add') }}
                    </button>
                </div>

                <div class="col-md-12 mt-4" v-if="(formData.products ?? []).length">
                    <div class="offer-products-table-wrap">
                        <table class="offer-products-table">
                            <thead>
                                <tr>
                                    <th>{{ $t('product_name') }}</th>
                                    <th>{{ $t('sku') }}</th>
                                    <th>{{ $t('base_price') }}</th>
                                    <th>{{ $t('discount_type') }}</th>
                                    <th>{{ $t('discount_amount') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(product, index) in formData.products"
                                    :key="`${product.product_id}_${product.product_variant_id}`">
                                    <td>
                                        <div class="product-cell">
                                            <div class="product-name">{{ product.product_name }}</div>
                                            <div class="variant-label">{{ product.variant_label }}</div>
                                        </div>
                                    </td>
                                    <td><span class="sku-badge">{{ product.sku }}</span></td>
                                    <td><span class="price-text">{{ numberWithCurrencySymbol(product.base_price) }}</span></td>
                                    <td>
                                        <div class="discount-type-toggle">
                                            <button type="button" :class="{ active: product.discount_type === 'fixed' }"
                                                @click="product.discount_type = 'fixed'">
                                                {{ $t('fixed') }}
                                            </button>
                                            <button type="button" :class="{ active: product.discount_type === 'percentage' }"
                                                @click="product.discount_type = 'percentage'">
                                                {{ $t('percent') }}
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <app-input type="number" :id="`discount_amount_${index}`" class="discount-amount-input"
                                            v-model="product.discount_amount" />
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="row-delete-btn" @click="removeProductRow(index)">
                                            <Trash width="16" height="16" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="col-12 d-flex justify-content-end gap-3">
                        <button class="btn btn-success text-white" @click.prevent="submit" :disabled="preloader">
                            <app-button-loader v-if="preloader" />
                            {{ $t("save") }}
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <media-modal v-if="mediaModalActive" modal-id="media-modal" @selected="handleMediaSelected"
            @close="closeMediaModalActive" />
    </div>
</template>

<style scoped lang="scss">
.offer-products-table-wrap {
    border: 1px solid #eef0f3;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
}

.offer-products-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;

    thead th {
        background: #f8f9fc;
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: 14px 18px;
        border-bottom: 1px solid #eef0f3;
        text-align: left;
        white-space: nowrap;
    }

    tbody tr {
        transition: background-color .15s ease;

        &:hover {
            background: #fafbfc;
        }

        &:not(:last-child) td {
            border-bottom: 1px solid #f1f2f5;
        }
    }

    tbody td {
        padding: 14px 18px;
        vertical-align: middle;
        font-size: 13.5px;
        color: #1f2937;
    }
}

.product-cell {
    .product-name {
        font-weight: 600;
        color: #111827;
    }

    .variant-label {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 2px;
    }
}

.sku-badge {
    display: inline-block;
    background: #f3f4f6;
    color: #4b5563;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 6px;
    letter-spacing: .02em;
    white-space: nowrap;
}

.price-text {
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
}

.discount-type-toggle {
    display: inline-flex;
    background: #f3f4f6;
    border-radius: 9px;
    padding: 3px;
    gap: 2px;
    width: fit-content;

    button {
        border: none;
        background: transparent;
        padding: 6px 14px;
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 7px;
        color: #6b7280;
        cursor: pointer;
        transition: all .15s ease;
        white-space: nowrap;

        &.active {
            background: #fff;
            color: #16a34a;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .08);
        }
    }
}

.discount-amount-input {
    max-width: 120px;
}

.row-delete-btn {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    border: none;
    background: #fef2f2;
    color: #ef4444;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .15s ease;

    &:hover {
        background: #fee2e2;
        color: #dc2626;
    }
}
</style>

<script setup>
import { ref, computed, watch } from "vue";
import Axios from "@/services/axios/index.js";
import { SELECTED_PRODUCT, PRODUCT_VARIANT_SHOW } from "@/services/endpoints/landlord.js";
import { Check, Trash } from "@iconoir/vue";

// One product line being added to an existing order. Each row fetches its own
// variants, so several products can be picked at once without them clobbering
// each other's variant list.
const props = defineProps({
    row: { type: Object, required: true },
    index: { type: Number, required: true },
    canRemove: { type: Boolean, default: true },
});

const emit = defineEmits(["remove"]);

const variants = ref([]);
const variantsLoading = ref(false);

const lineTotal = computed(() => {
    const qty = Number(props.row.quantity) || 0;
    const price = Number(props.row.product_price) || 0;
    const discount = Number(props.row.discount_amount) || 0;
    return Math.max((price - discount) * qty, 0);
});

const fetchVariants = async (productId) => {
    variants.value = [];
    props.row.product_variant_id = null;

    if (!productId) return;

    variantsLoading.value = true;
    try {
        const { data } = await Axios.get(PRODUCT_VARIANT_SHOW(productId));
        variants.value = data.variants || [];
    } catch (error) {
        variants.value = [];
    } finally {
        variantsLoading.value = false;
    }
};

watch(() => props.row.product_id, fetchVariants);

const variantLabel = (variant) => {
    const attrs = (variant.attribute_values || []).map((a) => a.value).filter(Boolean).join(" / ");
    return attrs || variant.sku || `#${variant.id}`;
};
</script>

<template>
    <div class="border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold">Product {{ index + 1 }}</span>
            <button v-if="canRemove" type="button" class="btn btn-white btn-sm" title="Remove"
                @click="emit('remove')">
                <Trash width="16" height="16" />
            </button>
        </div>

        <app-input type="advance-search-select" :id="`new_item_product_${index}`" label="Product" label-required
            list-value-field="name" v-model="row.product_id" :loading-length="10" :fetch-url="SELECTED_PRODUCT"
            choose-label="Choose a product" />

        <div class="mt-3">
            <label class="form-label fw-semibold">Variant</label>
            <app-loader v-if="variantsLoading" />
            <p v-else-if="!row.product_id" class="text-muted mb-0">Choose a product first.</p>
            <p v-else-if="!variants.length" class="text-muted mb-0">No variants found for this product.</p>
            <div v-else class="row g-2">
                <div class="col-md-6" v-for="variant in variants" :key="variant.id">
                    <div class="variant-option" :class="{
                        'variant-option--active': row.product_variant_id === variant.id,
                        'variant-option--disabled': variant.stock <= 0,
                    }" @click="variant.stock > 0 && (row.product_variant_id = variant.id)">
                        <div>
                            <div class="fw-semibold">{{ variantLabel(variant) }}</div>
                            <small :class="variant.stock > 0 ? 'text-success' : 'text-danger'">
                                {{ variant.stock > 0 ? 'in stock' : 'Out of stock' }}
                            </small>
                        </div>
                        <Check v-if="row.product_variant_id === variant.id" width="18" height="18"
                            class="text-success" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <app-input type="number" :id="`new_item_qty_${index}`" label="Quantity" label-required
                    v-model="row.quantity" min="1" />
            </div>
            <div class="col-md-4">
                <app-input type="number" :id="`new_item_price_${index}`" label="Unit Price" label-required
                    v-model="row.product_price" min="0" step="0.01" />
            </div>
            <div class="col-md-4">
                <app-input type="number" :id="`new_item_discount_${index}`" label="Discount (per unit)"
                    v-model="row.discount_amount" min="0" step="0.01" />
            </div>
        </div>

        <div class="text-end mt-2">
            <span class="text-muted me-2">Line total</span>
            <strong>{{ lineTotal.toFixed(2) }}</strong>
        </div>
    </div>
</template>

<style scoped lang="scss">
.variant-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 6px;
    cursor: pointer;
}

.variant-option--active {
    border-color: var(--primary, #2b8768);
}

.variant-option--disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

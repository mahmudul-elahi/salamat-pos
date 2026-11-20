<script setup>
import { ref, computed, watch } from "vue";
import Axios from "@/services/axios/index.js";
import { ORDER_UPDATE_ITEM_VARIANT, ORDER_UPDATE_GIFT_ITEM, ORDER_ADD_ITEM, ORDER_DELETE_ITEM } from "@/services/endpoints/Ecommerce.js";
import { SELECTED_PRODUCT, PRODUCT_VARIANT_SHOW } from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import { useToast } from "vue-toastification";
import { useI18n } from "vue-i18n";
import { Check, Plus, Trash } from "@iconoir/vue";
import NewOrderItemRow from "@/views/landlord/Ecommerce/Order/Component/NewOrderItemRow.vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    order: { type: Object, required: true },
});

const emit = defineEmits(["close", "submitted"]);
const { t } = useI18n();
const { canAccess } = usePermission();
const toast = useToast();


const items = ref([...(props.order.items || [])]);

// "Add a product to this order" reuses the whole product/variant/qty/price
// form below; only the item picker and the submit target differ.
const canAddItem = canAccess('create_order_items');
const canDeleteItem = canAccess('delete_order_items');

// The shared delete modal performs the request itself; this only holds which
// line is pending so the confirmation can name it and build its url.
const pendingDelete = ref(null);

const askDeleteItem = (item) => {
    if (items.value.length <= 1) {
        toast.error("An order must keep at least one product.");
        return;
    }

    pendingDelete.value = item;
};

const deleteUrl = computed(() => pendingDelete.value
    ? ORDER_DELETE_ITEM(props.order.id, pendingDelete.value.id)
    : null);

// Removing a line changes the order total, so the list behind us has to
// refresh even if nothing else is submitted afterwards.
const onItemDeleted = () => {
    const deletedId = pendingDelete.value?.id;

    items.value = items.value.filter((item) => item.id !== deletedId);

    if (selectedItemId.value === deletedId) {
        selectedItemId.value = null;
    }

    pendingDelete.value = null;
    emit("submitted");
};
const isAddingNew = ref(false);

// A customer confirming an order often asks for more than one extra product,
// so add mode edits a list of lines, not a single one.
const blankRow = () => ({
    product_id: null,
    product_variant_id: null,
    quantity: 1,
    product_price: 0,
    discount_amount: 0,
});

const newRows = ref([]);
const addRow = () => newRows.value.push(blankRow());
const removeRow = (index) => newRows.value.splice(index, 1);

const newRowsTotal = computed(() => newRows.value.reduce((sum, row) => {
    const qty = Number(row.quantity) || 0;
    const price = Number(row.product_price) || 0;
    const discount = Number(row.discount_amount) || 0;
    return sum + Math.max((price - discount) * qty, 0);
}, 0));

const selectedItemId = ref(items.value.length === 1 ? items.value[0].id : null);
const selectedItem = computed(() => items.value.find((i) => i.id === selectedItemId.value) || null);

const selectedProductId = ref(null);
const variants = ref([]);
const variantsLoading = ref(false);
const selectedVariantId = ref(null);
const preloader = ref(false);
const errors = ref({});

// Discount is entered as a flat, per-unit currency amount — matching how
// discount_amount is stored/used everywhere else in this app (never a
// stored percent). "Total after discount" below is just a live preview;
// the backend recomputes and persists the authoritative final_price.
const quantity = ref(1);
const unitPrice = ref(0);
const discountAmount = ref(0);
const lineTotal = computed(() => {
    const qty = Number(quantity.value) || 0;
    const price = Number(unitPrice.value) || 0;
    const discount = Number(discountAmount.value) || 0;
    return Math.max((price - discount) * qty, 0);
});

// Prevents the productId watcher below from wiping out the variant we just
// prefilled when an item is (re)selected — it should only reset the variant
// when the *user* actively picks a different product.
let suppressVariantReset = false;

const fetchVariants = async (productId) => {
    if (!productId) {
        variants.value = [];
        return;
    }
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

// Gift item can be upgraded from the same modal — scoped to the item's
// original purchased product, independent of whatever product is picked
// above in this same form.
const canEditGift = canAccess('update_order_gift_item_variant');
const hasGift = computed(() => !isAddingNew.value && canEditGift && !!selectedItem.value?.gift_product_variant_id);
const giftOptions = ref([]);
const giftOptionsLoading = ref(false);
const selectedGiftVariantId = ref(null);

// Gift options are every variant of the SAME gift product the customer
// already received (e.g. its other colors) — not the wider catalog of gift
// products a GiftProduct config might offer as alternatives.
const fetchGiftOptions = async (item) => {
    const giftProductId = item?.gift_product_variant?.product_id;
    if (!giftProductId) {
        giftOptions.value = [];
        return;
    }
    giftOptionsLoading.value = true;
    try {
        const { data } = await Axios.get(PRODUCT_VARIANT_SHOW(giftProductId));
        giftOptions.value = data.variants || [];
    } catch (error) {
        giftOptions.value = [];
    } finally {
        giftOptionsLoading.value = false;
    }
};

const applyItemDefaults = (item) => {
    suppressVariantReset = true;
    errors.value = {};
    selectedProductId.value = item?.variant?.product_id ?? null;
    selectedVariantId.value = item?.product_variant_id ?? null;
    fetchVariants(selectedProductId.value);

    quantity.value = item?.quantity ?? 1;
    unitPrice.value = item?.product_price ?? 0;
    discountAmount.value = item?.discount_amount ?? 0;

    selectedGiftVariantId.value = item?.gift_product_variant?.product_variant_id ?? null;
    if (canEditGift && item?.gift_product_variant_id) {
        fetchGiftOptions(item);
    } else {
        giftOptions.value = [];
    }
};

const selectItem = (item) => {
    isAddingNew.value = false;
    selectedItemId.value = item.id;
    applyItemDefaults(item);
};

// A blank line rather than a copy of anything already on the order.
const startAddingNew = () => {
    isAddingNew.value = true;
    selectedItemId.value = null;
    errors.value = {};

    // Start with one blank line rather than carrying over anything already
    // on the order.
    newRows.value = [blankRow()];

    selectedGiftVariantId.value = null;
    giftOptions.value = [];
};

watch(selectedProductId, (productId) => {
    if (suppressVariantReset) {
        suppressVariantReset = false;
        return;
    }
    selectedVariantId.value = null;
    fetchVariants(productId);
});

if (selectedItem.value) {
    applyItemDefaults(selectedItem.value);
}

const variantLabel = (variant) => {
    const attrs = (variant.attribute_values || []).map((a) => a.value).filter(Boolean).join(" / ");
    return attrs || variant.sku || `#${variant.id}`;
};

const submit = async () => {
    errors.value = {};

    if (isAddingNew.value) {
        if (newRows.value.length === 0) {
            errors.value.item = "Add at least one product.";
            return;
        }

        for (const [index, row] of newRows.value.entries()) {
            const line = index + 1;

            if (!row.product_id) {
                errors.value.item = `Product ${line}: please select a product.`;
                return;
            }
            if (!row.product_variant_id) {
                errors.value.item = `Product ${line}: please select a variant.`;
                return;
            }
            if (!row.quantity || Number(row.quantity) < 1) {
                errors.value.item = `Product ${line}: quantity must be at least 1.`;
                return;
            }
            if (row.product_price === null || row.product_price === "" || Number(row.product_price) < 0) {
                errors.value.item = `Product ${line}: please enter a valid unit price.`;
                return;
            }
            if (Number(row.discount_amount || 0) > Number(row.product_price)) {
                errors.value.item = `Product ${line}: discount cannot be greater than the unit price.`;
                return;
            }
        }

        preloader.value = true;
        try {
            const { data } = await Axios.post(ORDER_ADD_ITEM(props.order.id), {
                items: newRows.value.map((row) => ({
                    product_id: row.product_id,
                    product_variant_id: row.product_variant_id,
                    quantity: Number(row.quantity),
                    product_price: Number(row.product_price),
                    discount_amount: Number(row.discount_amount || 0),
                })),
            });

            toast.success(data?.message || "Products added to the order successfully");
            emit("submitted");
        } catch (error) {
            toast.error(error.response?.data?.message || "Failed to add the products");
        } finally {
            preloader.value = false;
        }

        return;
    }

    if (!selectedItemId.value) {
        errors.value.item = "Please select an order item.";
        return;
    }
    if (!selectedProductId.value) {
        errors.value.product = "Please select a product.";
        return;
    }
    if (!selectedVariantId.value) {
        errors.value.variant = "Please select a variant.";
        return;
    }
    if (hasGift.value && !selectedGiftVariantId.value) {
        errors.value.gift = "Please select a gift item.";
        return;
    }
    if (!quantity.value || Number(quantity.value) < 1) {
        errors.value.quantity = "Quantity must be at least 1.";
        return;
    }
    if (unitPrice.value === null || unitPrice.value === "" || Number(unitPrice.value) < 0) {
        errors.value.price = "Please enter a valid unit price.";
        return;
    }
    if (Number(discountAmount.value || 0) > Number(unitPrice.value)) {
        errors.value.discount = "Discount cannot be greater than the unit price.";
        return;
    }

    preloader.value = true;
    try {
        await Axios.patch(ORDER_UPDATE_ITEM_VARIANT(props.order.id), {
            order_list_id: selectedItemId.value,
            product_id: selectedProductId.value,
            product_variant_id: selectedVariantId.value,
            quantity: Number(quantity.value),
            product_price: Number(unitPrice.value),
            discount_amount: Number(discountAmount.value || 0),
        });

        if (hasGift.value) {
            await Axios.patch(ORDER_UPDATE_GIFT_ITEM(props.order.id), {
                order_list_id: selectedItemId.value,
                product_variant_id: selectedGiftVariantId.value,
            });
        }

        toast.success("Order item updated successfully");
        emit("submitted");
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to update order item");
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="isAddingNew ? 'Add Product to Order' : 'Update Order Item'"
        :save-button-label="isAddingNew ? 'Add to Order' : 'Update'"
        :preloader="preloader" @submit="submit" @close="$emit('close')">
        <template #body>
            <div v-if="items.length > 1 || canAddItem" class="mb-4">
                <label class="form-label fw-semibold">Select Item</label>
                <div class="d-flex flex-column gap-2">
                    <div v-for="item in items" :key="item.id" class="update-item-row"
                        :class="{ 'update-item-row--active': !isAddingNew && selectedItemId === item.id }"
                        @click="selectItem(item)">
                        <div>
                            <div class="fw-semibold">{{ item.variant?.product?.product_name || '-' }}</div>
                            <small class="text-muted">SKU: {{ item.variant?.sku || '-' }}</small>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <Check v-if="!isAddingNew && selectedItemId === item.id" width="18" height="18"
                                class="text-success" />

                            <!-- An order can't be emptied this way; the last -->
                            <!-- line has no remove button. -->
                            <button v-if="canDeleteItem && items.length > 1" type="button"
                                class="btn btn-white btn-sm" title="Remove from order"
                                @click.stop="askDeleteItem(item)">
                                <Trash width="16" height="16" />
                            </button>
                        </div>
                    </div>

                    <!-- The customer who ordered A and asks for B on the
                         confirmation call. -->
                    <div v-if="canAddItem" class="update-item-row"
                        :class="{ 'update-item-row--active': isAddingNew }" @click="startAddingNew">
                        <div class="fw-semibold d-flex align-items-center gap-2">
                            <Plus width="18" height="18" /> Add more products
                        </div>
                        <Check v-if="isAddingNew" width="18" height="18" class="text-success" />
                    </div>
                </div>
                <small v-if="errors.item" class="text-danger d-block mt-1">{{ errors.item }}</small>
            </div>

            <template v-if="isAddingNew">
                <p class="text-muted mb-3">
                    These products will be added to the order and its total updated.
                </p>

                <new-order-item-row v-for="(row, index) in newRows" :key="index" :row="row" :index="index"
                    :can-remove="newRows.length > 1" @remove="removeRow(index)" />

                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-white" @click="addRow">
                        <Plus width="16" height="16" /> Add another product
                    </button>

                    <div>
                        <span class="text-muted me-2">Total to add</span>
                        <strong>{{ newRowsTotal.toFixed(2) }}</strong>
                    </div>
                </div>

                <small v-if="errors.item" class="text-danger d-block mt-2">{{ errors.item }}</small>
            </template>

            <template v-else-if="selectedItemId">
                <p class="text-muted mb-3">
                    Currently ordered: <strong>{{ selectedItem?.variant?.product?.product_name || '-' }}</strong>
                    ({{ selectedItem?.variant?.sku || '-' }})
                </p>

                <div class="mb-4">
                    <app-input :key="isAddingNew ? 'new' : selectedItemId" type="advance-search-select" id="update_order_product"
                        label="Product" label-required list-value-field="name" v-model="selectedProductId"
                        :loading-length="10" :fetch-url="SELECTED_PRODUCT" choose-label="Choose a product" />
                    <small v-if="errors.product" class="text-danger d-block mt-1">{{ errors.product }}</small>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold">Variant</label>
                    <app-loader v-if="variantsLoading" />
                    <p v-else-if="!variants.length" class="text-muted">No variants found for this product.</p>
                    <div v-else class="row g-2">
                        <div class="col-md-6" v-for="variant in variants" :key="variant.id">
                            <div class="variant-option" :class="{
                                'variant-option--active': selectedVariantId === variant.id,
                                'variant-option--disabled': variant.stock <= 0,
                            }" @click="variant.stock > 0 && (selectedVariantId = variant.id)">
                                <div>
                                    <div class="fw-semibold">{{ variantLabel(variant) }}</div>
                                    <small :class="variant.stock > 0 ? 'text-success' : 'text-danger'">
                                        {{ variant.stock > 0 ? `in stock` : 'Out of stock' }}
                                    </small>
                                </div>
                                <Check v-if="selectedVariantId === variant.id" width="18" height="18"
                                    class="text-success" />
                            </div>
                        </div>
                    </div>
                    <small v-if="errors.variant" class="text-danger d-block mt-1">{{ errors.variant }}</small>
                </div>

                <div class="row g-3 mt-1 mb-2">
                    <div class="col-md-4">
                        <app-input type="number" id="update_order_quantity" label="Quantity" label-required
                            v-model="quantity" min="1" />
                        <small v-if="errors.quantity" class="text-danger d-block mt-1">{{ errors.quantity }}</small>
                    </div>
                    <div class="col-md-4">
                        <app-input type="number" id="update_order_price" label="Unit Price" label-required
                            v-model="unitPrice" min="0" step="0.01" />
                        <small v-if="errors.price" class="text-danger d-block mt-1">{{ errors.price }}</small>
                    </div>
                    <div class="col-md-4">
                        <app-input type="number" id="update_order_discount" label="Discount (per unit)"
                            v-model="discountAmount" min="0" step="0.01" />
                        <small v-if="errors.discount" class="text-danger d-block mt-1">{{ errors.discount }}</small>
                    </div>
                </div>
                <p class="text-muted mb-4">Line total after discount: <strong>{{ lineTotal.toFixed(2) }}</strong></p>

                <div v-if="hasGift" class="mt-4 pt-3 gift-section">
                    <p class="text-muted mb-3">
                        Current gift: <strong>{{ selectedItem?.gift_product_variant?.product?.product_name || '-'
                        }}</strong>
                    </p>
                    <label class="form-label fw-semibold">Gift Item</label>
                    <app-loader v-if="giftOptionsLoading" />
                    <p v-else-if="!giftOptions.length" class="text-muted">No variants found for this gift item.</p>
                    <div v-else class="row g-2">
                        <div class="col-md-6" v-for="variant in giftOptions" :key="variant.id">
                            <div class="variant-option" :class="{
                                'variant-option--active': selectedGiftVariantId === variant.id,
                                'variant-option--disabled': variant.stock <= 0,
                            }" @click="variant.stock > 0 && (selectedGiftVariantId = variant.id)">
                                <div>
                                    <div class="fw-semibold">{{ variantLabel(variant) }}</div>
                                    <small :class="variant.stock > 0 ? 'text-success' : 'text-danger'">
                                        {{ variant.stock > 0 ? 'In stock' : 'Out of stock' }}
                                    </small>
                                </div>
                                <Check v-if="selectedGiftVariantId === variant.id" width="18" height="18"
                                    class="text-success" />
                            </div>
                        </div>
                    </div>
                    <small v-if="errors.gift" class="text-danger d-block mt-1">{{ errors.gift }}</small>
                </div>
            </template>
        </template>
    </app-modal>

    <app-delete-modal
        v-if="pendingDelete"
        modal-id="order-item-delete-modal"
        :table-id="tableId"
        :selected-url="deleteUrl"
        title="Remove product"
        :sub-title="pendingDelete.variant?.product?.product_name || 'this product'"
        message="This product will be removed from the order and its total updated."
        call-back-emit
        @confirmSuccess="onItemDeleted"
        @cancelled="pendingDelete = null" />
</template>

<style scoped lang="scss">
.update-item-row,
.variant-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 14px;
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 10px;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;

    &:hover {
        border-color: #a5b4fc;
    }
}

.update-item-row--active,
.variant-option--active {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.06);
}

.variant-option--disabled {
    opacity: 0.55;
    cursor: not-allowed;

    &:hover {
        border-color: var(--border-color, #e5e7eb);
    }
}

.gift-section {
    border-top: 1px solid var(--border-color, #e5e7eb);
}
</style>

<script setup>
import { ref, computed, onMounted } from 'vue';
import Axios from "@/services/axios/index.js";
import { PRODUCT_VARIANT_UPDATE } from "@/services/endpoints/landlord.js";
import { useToast } from "vue-toastification";
import { useI18n } from "vue-i18n";
import Avatar from "@/views/global/avatar/Avatar.vue";
import { FloppyDisk } from "@iconoir/vue";

const props = defineProps({
    modalId: String,
    productId: [String, Number],
    tableId: String
})

const emit = defineEmits(['close'])
const { t } = useI18n()
const toast = useToast()
const preloader = ref(false)
const variants = ref([])
const productData = ref({})
const colorOptions = ref([])
const originalVariants = ref([])

const fetchProduct = async () => {
    try {
        preloader.value = true;
        const { data } = await Axios.get(`admin/landlord/products/variant-show/${props.productId}`);
        productData.value = data;

        if (data.variants && data.variants.length) {
            variants.value = data.variants.map(v => {
                const attributeLabels = v.attribute_values
                    ? v.attribute_values.map(val => val.value)
                    : [];
                return { ...v, attributeLabels, stock: !!v.stock, is_prebook: !!v.is_prebook }
            });
            originalVariants.value = JSON.parse(JSON.stringify(variants.value));
        }
    } catch (error) {
        toast.error('Failed to fetch product details');
    } finally {
        preloader.value = false;
    }
}

const isDirty = (variant) => {
    const original = originalVariants.value.find(o => o.id === variant.id);
    if (!original) return false;
    return (
        String(variant.sale_price)       !== String(original.sale_price)       ||
        String(variant.max_retail_price) !== String(original.max_retail_price) ||
        variant.stock                    !== original.stock                    ||
        variant.is_prebook                !== original.is_prebook
    );
};

const dirtyCount = computed(() => variants.value.filter(v => isDirty(v)).length);

const variantDiscount = (variant) => {
    const mrp = parseFloat(variant.max_retail_price) || 0;
    const salePrice = parseFloat(variant.sale_price) || 0;
    return Math.max(0, mrp - salePrice);
};

const clampSalePrice = (variant) => {
    const mrp = parseFloat(variant.max_retail_price);
    const salePrice = parseFloat(variant.sale_price);
    if (!isNaN(mrp) && !isNaN(salePrice) && salePrice > mrp) {
        variant.sale_price = mrp;
    }
};

const buildPayload = (variant) => {
    let newAttributeValues = [...(variant.attribute_values || [])];
    const colorIdsSet = new Set(colorOptions.value.map(c => c.id));
    newAttributeValues = newAttributeValues.filter(id => !colorIdsSet.has(id));
    if (variant.selectedColorId) newAttributeValues.push(variant.selectedColorId);

    return {
        id: variant.id,
        sku: variant.sku,
        stock: variant.stock,
        is_prebook: variant.is_prebook,
        max_retail_price: variant.max_retail_price,
        sale_price: variant.sale_price,
        attribute_values: newAttributeValues,
    };
};

const buttonLoader = ref({})
const saveVariant = async (variant) => {
    try {
        buttonLoader.value[variant.id] = true;
        const { data } = await Axios.post(PRODUCT_VARIANT_UPDATE, buildPayload(variant));
        const idx = originalVariants.value.findIndex(o => o.id === variant.id);
        if (idx !== -1) originalVariants.value[idx] = JSON.parse(JSON.stringify(variant));
        toast.success(data.message);
    } catch (error) {
        toast.error(error.response?.data?.message || 'Error updating variant');
    } finally {
        buttonLoader.value[variant.id] = false;
    }
};

const saveAllLoader = ref(false);
const saveAll = async () => {
    const dirty = variants.value.filter(v => isDirty(v));
    if (!dirty.length) {
        toast.info(t('no_changes_to_save'));
        return;
    }

    saveAllLoader.value = true;
    dirty.forEach(v => { buttonLoader.value[v.id] = true; });

    const results = await Promise.allSettled(
        dirty.map(v => Axios.post(PRODUCT_VARIANT_UPDATE, buildPayload(v))
            .then(({ data }) => {
                const idx = originalVariants.value.findIndex(o => o.id === v.id);
                if (idx !== -1) originalVariants.value[idx] = JSON.parse(JSON.stringify(v));
                return { ok: true, message: data.message };
            })
            .catch(err => ({ ok: false, id: v.id, message: err.response?.data?.message || 'Error' }))
            .finally(() => { buttonLoader.value[v.id] = false; })
        )
    );

    const failed = results.filter(r => r.status === 'fulfilled' && !r.value.ok);
    const succeeded = results.filter(r => r.status === 'fulfilled' && r.value.ok).length;

    if (succeeded) toast.success(`${succeeded} ${t('variants')} ${t('saved_successfully')}`);
    if (failed.length) toast.error(`${failed.length} variant(s) failed to save`);

    saveAllLoader.value = false;
};

onMounted(() => fetchProduct())
const closeModal = () => emit('close')
</script>

<template>
    <app-modal :modal-id="modalId" :title="productData.name + ' ' + $t('variants')" modal-size="extra-large"
        :show-footer="false" @close="closeModal">

        <template v-slot:body>
            <app-loader v-if="preloader" />

            <div v-else class="variant-list">

                <!-- Table Header (desktop only) -->
                <div class="variant-header d-none d-md-grid">
                    <div>{{ $t('variant') }}</div>
                    <div>{{ $t('max_retail_price') }}</div>
                    <div>{{ $t('sale_price') }}</div>
                    <div>{{ $t('discount') }}</div>
                    <div>{{ $t('stock') }}</div>
                    <div>{{ $t('prebook') }}</div>
                    <div class="text-end">{{ $t('action') }}</div>
                </div>

                <!-- Variant Rows -->
                <div v-for="(variant, index) in variants" :key="variant.id"
                    class="variant-row" :class="{ 'variant-row--dirty': isDirty(variant) }">

                    <!-- Variant identity -->
                    <div class="variant-identity">
                        <avatar :image="variant?.image" name="variant" />
                        <div class="chip-group">
                            <span v-for="label in variant.attributeLabels" :key="label" class="chip">
                                {{ label }}
                            </span>
                            <span v-if="!variant.attributeLabels?.length" class="text-muted small">
                                Variant {{ index + 1 }}
                            </span>
                        </div>
                        <span v-if="isDirty(variant)" class="dirty-badge">{{ $t('modified') }}</span>
                    </div>

                    <!-- MRP -->
                    <div class="variant-field">
                        <span class="field-label d-md-none">{{ $t('max_retail_price') }}</span>
                        <app-input type="number" :id="`max_retail_price_${index}`" v-model="variant.max_retail_price"
                            @blur="clampSalePrice(variant)" />
                    </div>

                    <!-- Sale Price -->
                    <div class="variant-field">
                        <span class="field-label d-md-none">{{ $t('sale_price') }}</span>
                        <app-input type="number" :id="`sale_price_${index}`" v-model="variant.sale_price"
                            @blur="clampSalePrice(variant)" />
                    </div>

                    <!-- Discount (read-only, auto-calculated) -->
                    <div class="variant-field">
                        <span class="field-label d-md-none">{{ $t('discount') }}</span>
                        <span class="discount-value">{{ variantDiscount(variant) }}</span>
                    </div>

                    <!-- Stock toggle -->
                    <div class="variant-field">
                        <span class="field-label d-md-none">{{ $t('stock') }}</span>
                        <div class="stock-toggle">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" :id="`stock_${index}`"
                                    v-model="variant.stock">
                                <label class="form-check-label fw-medium" :for="`stock_${index}`"
                                    :class="variant.stock ? 'text-success' : 'text-secondary'">
                                    {{ variant.stock ? $t('in_stock') : $t('out_of_stock') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Prebook toggle -->
                    <div class="variant-field">
                        <span class="field-label d-md-none">{{ $t('prebook') }}</span>
                        <div class="prebook-toggle">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" :id="`prebook_${index}`"
                                    v-model="variant.is_prebook">
                                <label class="form-check-label fw-medium" :for="`prebook_${index}`"
                                    :class="variant.is_prebook ? 'text-primary' : 'text-secondary'">
                                    {{ variant.is_prebook ? $t('enabled') : $t('disabled') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Save -->
                    <div class="variant-action">
                        <button class="btn-save" @click="saveVariant(variant)" :disabled="buttonLoader[variant.id]">
                            <app-button-loader v-if="buttonLoader[variant.id]" />
                            <span v-else>{{ $t('save') }}</span>
                        </button>
                    </div>

                </div>

                <!-- Save All Footer Bar -->
                <div class="save-all-bar">
                    <div class="save-all-info">
                        <span v-if="dirtyCount > 0" class="pending-count">
                            <span class="pending-dot" />
                            {{ dirtyCount }} {{ $t('variants') }} {{ $t('modified') }}
                        </span>
                        <span v-else class="no-changes">{{ $t('no_unsaved_changes') }}</span>
                    </div>
                    <button class="btn-save-all" @click="saveAll" :disabled="saveAllLoader || dirtyCount === 0">
                        <app-button-loader v-if="saveAllLoader" />
                        <template v-else>
                            <FloppyDisk class="save-all-icon" />
                            {{ $t('save_all') }}
                            <span v-if="dirtyCount > 0" class="save-all-badge">{{ dirtyCount }}</span>
                        </template>
                    </button>
                </div>

            </div>
        </template>

    </app-modal>
</template>

<style scoped lang="scss">
.variant-list {
    padding: 0 4px;
}

/* ── Header ── */
.variant-header {
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 100px;
    gap: 12px;
    align-items: center;
    padding: 10px 16px;
    background: #f8f9fb;
    border-radius: 10px;
    margin-bottom: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #8a94a6;
}

/* ── Row ── */
.variant-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 100px;
    gap: 12px;
    align-items: center;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid #f0f2f5;
    margin-bottom: 8px;
    background: #fff;
    transition: box-shadow 0.18s ease, border-color 0.18s ease;

    &:hover {
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        border-color: #e0e4ea;
    }

    &--dirty {
        border-color: #f59e0b;
        background: #fffdf5;
        box-shadow: 0 0 0 1px #fde68a;
    }

    @media (max-width: 768px) {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    @media (max-width: 480px) {
        grid-template-columns: 1fr;
    }
}

/* ── Variant identity ── */
.variant-identity {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex-wrap: nowrap;
    min-width: 0;

    @media (max-width: 768px) {
        grid-column: 1 / -1;
    }
}

.dirty-badge {
    flex-shrink: 0;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    background: #fef3c7;
    color: #d97706;
    border: 1px solid #fde68a;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
    margin-top: 4px;
}

.chip-group {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    flex: 1 1 0;
    min-width: 0;
    padding-top: 4px;
}

.chip {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    background: #f0f2f5;
    color: #3d4a5c;
    font-size: 0.76rem;
    font-weight: 500;
    white-space: nowrap;
    border: 1px solid #e4e8ef;
    transition: background 0.15s;

    &:hover {
        background: #e6eaf0;
    }
}

/* ── Field ── */
.variant-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.field-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #8a94a6;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.discount-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #3d4a5c;
}

/* ── Stock ── */
.stock-toggle {
    .form-check-input {
        width: 2.2em;
        height: 1.2em;
        cursor: pointer;

        &:checked {
            background-color: #22c55e;
            border-color: #22c55e;
        }
    }

    .form-check-label {
        font-size: 0.82rem;
        cursor: pointer;
    }
}

/* ── Prebook ── */
.prebook-toggle {
    .form-check-input {
        width: 2.2em;
        height: 1.2em;
        cursor: pointer;

        &:checked {
            background-color: #6366f1;
            border-color: #6366f1;
        }
    }

    .form-check-label {
        font-size: 0.82rem;
        cursor: pointer;
    }
}

/* ── Action ── */
.variant-action {
    display: flex;
    justify-content: flex-end;

    @media (max-width: 768px) {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
}

.btn-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 20px;
    border-radius: 8px;
    border: none;
    background: #16a34a;
    color: #fff;
    font-size: 0.83rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s ease, transform 0.1s ease;
    white-space: nowrap;

    &:hover:not(:disabled) {
        background: #15803d;
        transform: translateY(-1px);
    }

    &:active:not(:disabled) {
        transform: translateY(0);
    }

    &:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }
}

/* ── Save All Bar ── */
.save-all-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 12px;
    padding: 14px 20px;
    border-radius: 14px;
    background: linear-gradient(135deg, #f8f9ff 0%, #f3f4f6 100%);
    border: 1px solid #e5e7eb;
    position: sticky;
    bottom: 0;
}

.save-all-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pending-count {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #d97706;
}

.pending-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f59e0b;
    animation: pulse 1.5s ease-in-out infinite;
}

.no-changes {
    font-size: 0.84rem;
    color: #9ca3af;
    font-weight: 500;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: 0.5; transform: scale(1.3); }
}

.btn-save-all {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 28px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
    white-space: nowrap;
    letter-spacing: 0.2px;

    &:hover:not(:disabled) {
        opacity: 0.92;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
    }

    &:active:not(:disabled) {
        transform: translateY(0);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        box-shadow: none;
    }
}

.save-all-icon {
    width: 16px;
    height: 16px;
}

.save-all-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    font-size: 11px;
    font-weight: 800;
    line-height: 1;
}

/* ── Dark Mode ── */
:global([theme="dark"]) {
    .variant-header {
        background: rgb(46, 50, 62);
        color: rgb(170, 170, 180);
    }

    .variant-row {
        background: #1e2330;
        border-color: #2a3045;

        &:hover {
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
            border-color: #3a4060;
        }

        &--dirty {
            border-color: #d97706;
            background: #1e2010;
            box-shadow: 0 0 0 1px #78350f;
        }
    }

    .chip {
        background: #2a3045;
        color: #c8cfe0;
        border-color: #3a4060;

        &:hover {
            background: #323a55;
        }
    }

    .field-label {
        color: #6b7a99;
    }

    .discount-value {
        color: #c8cfe0;
    }

    .btn-save {
        background: #16a34a;

        &:hover:not(:disabled) {
            background: #15803d;
        }
    }

    .save-all-bar {
        background: linear-gradient(135deg, #1e2330, #252a3a);
        border-color: #2a3045;
    }

    .no-changes {
        color: #4b5675;
    }
}
</style>

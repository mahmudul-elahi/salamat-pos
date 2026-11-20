<script setup>
import { ref, computed } from 'vue';
import Axios from "@/services/axios/index.js";
import { PRE_OWANED_PRODUCTS } from "@/services/endpoints/Ecommerce.js";
import { useToast } from "vue-toastification";
import { useI18n } from "vue-i18n";
import Avatar from "@/views/global/avatar/Avatar.vue";
import { FloppyDisk } from "@iconoir/vue";
import useEmitter from "@/composable/emitter/useEmitter.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    preOwned: { type: Object, required: true },
});

const emitter = useEmitter();

const emit = defineEmits(['close']);
const { t } = useI18n();
const toast = useToast();

const discountTypeOptions = [
    { id: 'percent', name: t('percent') },
    { id: 'fixed', name: t('fixed') },
];

const item = ref({
    price: props.preOwned.price,
    discount_type: props.preOwned.discount_type ?? null,
    discount_amount: props.preOwned.discount_amount ?? null,
    box: !!props.preOwned.box,
});

// attributes comes from the backend as a single "128GB/Graphite/..." string
// — split it into individual pills instead of one long chip.
const attributeLabels = computed(() =>
    (props.preOwned.attributes || '').split('/').map(v => v.trim()).filter(Boolean)
);

const original = ref(JSON.parse(JSON.stringify(item.value)));

const isDirty = computed(() =>
    String(item.value.price) !== String(original.value.price) ||
    (item.value.discount_type ?? '') !== (original.value.discount_type ?? '') ||
    String(item.value.discount_amount ?? '') !== String(original.value.discount_amount ?? '') ||
    item.value.box !== original.value.box
);

// Fixed discount can't exceed the price; percent discount can't exceed 100%
// — matches the backend's own max:100 / lte:price validation.
const clampAmount = (value) => {
    let discountAmount = Number(value.discount_amount || 0);
    let discountType = value.discount_type;
    let price = Number(value.price || 0);

    if (Number.isNaN(discountAmount)) return value;

    if (discountType === 'percent') {
        discountAmount = Math.min(discountAmount, 100);
    } else if (discountType === 'fixed') {
        return price > discountAmount ? price : discountAmount;
    }
    return 0;

};

const buttonLoader = ref(false);

const save = async () => {
    try {
        buttonLoader.value = true;
        const { data } = await Axios.patch(`${PRE_OWANED_PRODUCTS}/${props.preOwned.id}/price`, {
            price: item.value.price,
            discount_type: item.value.discount_type || null,
            discount_amount: item.value.discount_type ? item.value.discount_amount : null,
            box: item.value.box,
        });
        original.value = JSON.parse(JSON.stringify(item.value));
        toast.success(data.message || t('updated_successfully'));
        emitter.emit('reload-' + props.tableId);
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update price');
    } finally {
        buttonLoader.value = false;
    }
};
</script>

<template>
    <app-modal :modal-id="modalId" :title="(preOwned.product_name || '') + ' ' + t('price')" modal-size="extra-large"
        :show-footer="false" @close="$emit('close')">

        <template v-slot:body>
            <div class="variant-list">

                <!-- Table Header (desktop only) -->
                <div class="variant-header d-none d-md-grid">
                    <div>{{ t('product') }}</div>
                    <div>{{ t('discount_type') }}</div>
                    <div>{{ t('discount_amount') }}</div>
                    <div>{{ t('price') }}</div>
                    <div>{{ t('box') }}</div>
                    <div class="text-end">{{ t('action') }}</div>
                </div>

                <!-- Item Row -->
                <div class="variant-row" :class="{ 'variant-row--dirty': isDirty }">

                    <!-- Identity -->
                    <div class="variant-identity">
                        <avatar :image="preOwned.product_image" :name="preOwned.product_name" />
                        <div class="chip-group">
                            <span v-for="label in attributeLabels" :key="label" class="chip">{{ label }}</span>
                            <span v-if="!attributeLabels.length" class="text-muted small">{{ preOwned.product_name }}</span>
                        </div>
                        <span v-if="isDirty" class="dirty-badge">{{ t('modified') }}</span>
                    </div>

                    <!-- Discount Type -->
                    <div class="variant-field">
                        <span class="field-label d-md-none">{{ t('discount_type') }}</span>
                        <app-input type="select" id="pre_owned_discount_type" list-key-field="id"
                            list-value-field="name" v-model="item.discount_type" :options="discountTypeOptions"
                            :choose-label="t('none')" />
                    </div>

                    <!-- Discount Amount -->
                    <div class="variant-field">
                        <span class="field-label d-md-none">{{ t('discount_amount') }}</span>
                        <app-input type="number" id="pre_owned_discount_amount" v-model="item.discount_amount"
                            :disabled="!item.discount_type" @input="clampAmount(item)" />
                    </div>

                    <!-- Price -->
                    <div class="variant-field">
                        <span class="field-label d-md-none">{{ t('price') }}</span>
                        <app-input type="number" id="pre_owned_price" v-model="item.price" />
                    </div>

                    <!-- Box toggle -->
                    <div class="variant-field">
                        <span class="field-label d-md-none">{{ t('box') }}</span>
                        <div class="stock-toggle">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="pre_owned_box"
                                    v-model="item.box">
                                <label class="form-check-label fw-medium" for="pre_owned_box"
                                    :class="item.box ? 'text-success' : 'text-secondary'">
                                    {{ item.box ? t('with_box') : t('without_box') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Save -->
                    <div class="variant-action">
                        <button class="btn-save" @click="save" :disabled="buttonLoader">
                            <app-button-loader v-if="buttonLoader" />
                            <span v-else>{{ t('save') }}</span>
                        </button>
                    </div>

                </div>

                <!-- Save Bar -->
                <div class="save-all-bar">
                    <div class="save-all-info">
                        <span v-if="isDirty" class="pending-count">
                            <span class="pending-dot" />
                            {{ t('modified') }}
                        </span>
                        <span v-else class="no-changes">{{ t('no_unsaved_changes') }}</span>
                    </div>
                    <button class="btn-save-all" @click="save" :disabled="buttonLoader || !isDirty">
                        <app-button-loader v-if="buttonLoader" />
                        <template v-else>
                            <FloppyDisk class="save-all-icon" />
                            {{ t('save') }}
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

.variant-header {
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 100px;
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

.variant-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 100px;
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
}

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

    &:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }
}

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

    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }

    50% {
        opacity: 0.5;
        transform: scale(1.3);
    }
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

:global([theme="dark"]) {

    .variant-header {
        background: rgb(46, 50, 62);
        color: rgb(170, 170, 180);
    }

    .variant-row {
        background: #1e2330;
        border-color: #2a3045;

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
    }

    .field-label {
        color: #6b7a99;
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

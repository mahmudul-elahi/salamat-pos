<script setup>
import { ref, computed } from 'vue';
import Axios from "@/services/axios/index.js";
import { LANDLORD_AREA_SHIPPING_CHARGES } from "@/services/endpoints/landlord.js";
import { useToast } from "vue-toastification";
import { useI18n } from "vue-i18n";
import useEmitter from "@/composable/emitter/useEmitter.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    area: { type: Object, required: true },
});

const emit = defineEmits(['close']);
const { t } = useI18n();
const toast = useToast();
const emitter = useEmitter();

// mode: 'default' -> shipping_charge = null (fall back to inside/outside Dhaka setting)
//       'free'    -> shipping_charge = 0
//       'custom'  -> shipping_charge = amount
const initialMode = props.area.shipping_charge === null || props.area.shipping_charge === undefined
    ? 'default'
    : (Number(props.area.shipping_charge) === 0 ? 'free' : 'custom');

const mode = ref(initialMode);
const amount = ref(initialMode === 'custom' ? props.area.shipping_charge : null);
const buttonLoader = ref(false);

const resolvedValue = computed(() => {
    if (mode.value === 'default') return null;
    if (mode.value === 'free') return 0;
    return amount.value;
});

const save = async () => {
    try {
        buttonLoader.value = true;
        const { data } = await Axios.patch(`${LANDLORD_AREA_SHIPPING_CHARGES}/${props.area.id}`, {
            shipping_charge: resolvedValue.value,
        });
        toast.success(data.message || t('updated_successfully'));
        emitter.emit('reload-' + props.tableId);
        emit('close');
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update shipping charge');
    } finally {
        buttonLoader.value = false;
    }
};
</script>

<template>
    <app-modal :modal-id="modalId" :title="`${t('shipping_charge')} — ${area.name}`" modal-size="default"
        :show-footer="false" @close="$emit('close')">

        <template v-slot:body>
            <div class="charge-options">

                <label class="charge-option" :class="{ 'charge-option--active': mode === 'default' }">
                    <input type="radio" name="charge_mode" value="default" v-model="mode">
                    <div>
                        <p class="charge-option__title">{{ t('use_default_charge') }}</p>
                        <p class="charge-option__sub">{{ t('use_default_charge_desc') }}</p>
                    </div>
                </label>

                <label class="charge-option" :class="{ 'charge-option--active': mode === 'free' }">
                    <input type="radio" name="charge_mode" value="free" v-model="mode">
                    <div>
                        <p class="charge-option__title">{{ t('free_delivery') }}</p>
                        <p class="charge-option__sub">{{ t('free_delivery_desc') }}</p>
                    </div>
                </label>

                <label class="charge-option" :class="{ 'charge-option--active': mode === 'custom' }">
                    <input type="radio" name="charge_mode" value="custom" v-model="mode">
                    <div class="w-100">
                        <p class="charge-option__title">{{ t('custom_amount') }}</p>
                        <p class="charge-option__sub">{{ t('custom_amount_desc') }}</p>
                        <app-input v-if="mode === 'custom'" type="number" id="area_shipping_charge_amount"
                            v-model="amount" :min="0" class="mt-2" />
                    </div>
                </label>

            </div>

            <div class="charge-footer">
                <button class="btn btn-primary d-flex align-items-center gap-2" :disabled="buttonLoader || (mode === 'custom' && (amount === null || amount === ''))"
                    @click="save">
                    <app-button-loader v-if="buttonLoader" />
                    <span v-else>{{ t('save') }}</span>
                </button>
            </div>
        </template>

    </app-modal>
</template>

<style scoped lang="scss">
.charge-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.charge-option {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid var(--border-color, #e5e7eb);
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;

    input[type="radio"] {
        margin-top: 4px;
        flex-shrink: 0;
    }

    &:hover {
        border-color: #a5b4fc;
    }

    &--active {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.06);
    }
}

.charge-option__title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--font-color);
    margin: 0 0 2px;
}

.charge-option__sub {
    font-size: 0.76rem;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
    line-height: 1.5;
}

.charge-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
}
</style>

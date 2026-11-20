<script setup>
import { ref, computed, watch } from 'vue';
import { EMI_BANKS } from '@/services/endpoints/Ecommerce.js';
import Axios from '@/services/axios/index.js';
import { useToast } from 'vue-toastification';
import useEmitter from '@/composable/emitter/useEmitter.js';

const props = defineProps({
    bank: { type: Object, default: null },
    tableId: { type: String, default: null },
});
const emit = defineEmits(['saved', 'close']);
const toast = useToast();
const emitter = useEmitter();

const allTenures = [3, 6, 9, 12, 18, 24, 30, 36];
const errors = ref({});
const loading = ref(false);
const isEdit = computed(() => !!props.bank?.id);

const buildTenures = (existing = []) =>
    allTenures.map(m => {
        const found = existing.find(t => t.months === m);
        return { months: m, charge_rate: found ? found.charge_rate : '', is_active: found ? found.is_active : true, enabled: !!found };
    });

const form = ref({ bank_name: '', gw_key: '', is_active: true, tenures: buildTenures() });

watch(() => props.bank, (val) => {
    if (val?.id) {
        form.value = { bank_name: val.bank_name, gw_key: val.gw_key, is_active: val.is_active, tenures: buildTenures(val.tenures) };
    } else {
        form.value = { bank_name: '', gw_key: '', is_active: true, tenures: buildTenures() };
    }
}, { immediate: true });

const submit = async () => {
    errors.value = {};
    loading.value = true;
    const payload = {
        bank_name: form.value.bank_name,
        gw_key: form.value.gw_key,
        is_active: form.value.is_active,
        tenures: form.value.tenures
            .filter(t => t.enabled && t.charge_rate !== '' && t.charge_rate !== null)
            .map(t => ({ months: t.months, charge_rate: t.charge_rate, is_active: t.is_active })),
    };

    try {
        if (isEdit.value) {
            await Axios.put(`${EMI_BANKS}/${props.bank.id}`, payload);
            toast.success('Updated successfully');
        } else {
            await Axios.post(EMI_BANKS, payload);
            toast.success('Created successfully');
        }
        if (props.tableId) emitter.emit(`reload-${props.tableId}`);
        emit('saved');
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
        } else {
            toast.error(err.response?.data?.message || 'An error occurred');
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEdit ? 'Edit' : 'Add' }} EMI Bank</h5>
                    <button type="button" class="btn-close" @click="$emit('close')" />
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.bank_name" placeholder="e.g. AB Bank PLC" />
                            <div v-if="errors.bank_name" class="text-danger small mt-1">{{ errors.bank_name[0] }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Gateway Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.gw_key" placeholder="e.g. abbank" />
                            <div v-if="errors.gw_key" class="text-danger small mt-1">{{ errors.gw_key[0] }}</div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" v-model="form.is_active" />
                                <label class="form-check-label">{{ form.is_active ? 'Active' : 'Inactive' }}</label>
                            </div>
                        </div>
                    </div>

                    <label class="form-label fw-medium mb-2">Tenure Rates</label>
                    <div v-if="errors.tenures" class="text-danger small mb-2">{{ errors.tenures[0] }}</div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width:50px">Enable</th>
                                    <th>Tenure</th>
                                    <th>Charge Rate (%)</th>
                                    <th style="width:80px">Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(tenure, index) in form.tenures" :key="tenure.months">
                                    <td class="text-center">
                                        <input type="checkbox" v-model="tenure.enabled" class="form-check-input" />
                                    </td>
                                    <td class="fw-medium">{{ tenure.months }} Months</td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100"
                                            class="form-control form-control-sm"
                                            :disabled="!tenure.enabled"
                                            v-model.number="tenure.charge_rate"
                                            placeholder="e.g. 3.00" />
                                        <div v-if="errors[`tenures.${index}.charge_rate`]" class="text-danger" style="font-size:11px">
                                            {{ errors[`tenures.${index}.charge_rate`][0] }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" v-model="tenure.is_active" class="form-check-input" :disabled="!tenure.enabled" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
                    <button class="btn btn-primary" :disabled="loading" @click="submit">
                        {{ loading ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

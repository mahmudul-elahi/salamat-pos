<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import { CheckCircle, Plus, Trash } from '@iconoir/vue';
import Axios from '@/services/axios/index.js';
import { LANDLORD_ATTENDANCE_SETTINGS } from '@/services/endpoints/landlord.js';

const toast      = useToast();
const pageLoader = ref(false);
const preloader  = ref(false);
const errors     = ref({});

const formData = ref({
    ip_validation:  'none',
    allowed_ips:    [],
    restricted_ips: [],
});

const showIpList = computed(() =>
    formData.value.ip_validation === 'allow' || formData.value.ip_validation === 'restrict'
);

const activeIpList = computed(() =>
    formData.value.ip_validation === 'allow' ? formData.value.allowed_ips : formData.value.restricted_ips
);

const activeIpKey = computed(() =>
    formData.value.ip_validation === 'allow' ? 'allowed_ips' : 'restricted_ips'
);

const addRow = () => {
    formData.value[activeIpKey.value].push('');
};

const removeRow = (index) => {
    formData.value[activeIpKey.value].splice(index, 1);
};

const fetchSettings = () => {
    pageLoader.value = true;
    Axios.get(LANDLORD_ATTENDANCE_SETTINGS)
        .then(({ data }) => {
            formData.value.ip_validation  = data.ip_validation  ?? 'none';
            formData.value.allowed_ips    = Array.isArray(data.allowed_ips)    ? [...data.allowed_ips]    : [];
            formData.value.restricted_ips = Array.isArray(data.restricted_ips) ? [...data.restricted_ips] : [];
        })
        .finally(() => { pageLoader.value = false; });
};

const submit = () => {
    const key = activeIpKey.value;
    const ips  = formData.value[key].map(ip => ip.trim()).filter(Boolean);
    formData.value[key] = ips;

    preloader.value = true;
    errors.value = {};
    Axios.post(LANDLORD_ATTENDANCE_SETTINGS, {
        ip_validation:  formData.value.ip_validation,
        allowed_ips:    formData.value.allowed_ips,
        restricted_ips: formData.value.restricted_ips,
    })
        .then(({ data }) => toast.success(data.message))
        .catch(({ response }) => {
            if (response?.status === 422) errors.value = response.data.errors ?? {};
            else toast.error(response?.data?.message ?? 'Something went wrong');
        })
        .finally(() => { preloader.value = false; });
};

onMounted(fetchSettings);
</script>

<template>
    <div class="ai-wrap">
        <app-loader v-if="pageLoader" />

        <template v-else>
            <!-- IP Validation radio -->
            <div class="ai-row">
                <div class="ai-row__label">
                    <p class="ai-row__title">IP Validation</p>
                    <p class="ai-row__sub">Restrict or allow network IP addresses for Punch In.</p>
                </div>
                <div class="ai-row__body">
                    <div class="ip-radio-group">
                        <label v-for="opt in [
                            { value: 'allow',    label: 'Allow IP' },
                            { value: 'restrict', label: 'Restrict IP' },
                            { value: 'none',     label: 'None' },
                        ]" :key="opt.value" class="ip-radio">
                            <input type="radio" v-model="formData.ip_validation" :value="opt.value" />
                            {{ opt.label }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- Dynamic IP list -->
            <template v-if="showIpList">
                <div class="ai-divider"></div>

                <div class="ai-row">
                    <div class="ai-row__label">
                        <p class="ai-row__title">
                            {{ formData.ip_validation === 'allow' ? 'Allowed IPs' : 'Restricted IPs' }}
                        </p>
                        <p class="ai-row__sub">
                            {{ formData.ip_validation === 'allow'
                                ? 'Only these IPs will be allowed to punch in.'
                                : 'These IPs will be blocked from punching in.' }}
                        </p>
                    </div>
                    <div class="ai-row__body">
                        <div v-for="(ip, index) in activeIpList" :key="index" class="ip-input-row">
                            <input
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': errors[`${activeIpKey}.${index}`] }"
                                v-model="formData[activeIpKey][index]"
                                placeholder="e.g. 192.168.1.1"
                            />
                            <button type="button" class="ip-remove-btn" @click="removeRow(index)">
                                <Trash width="15" height="15" />
                            </button>
                            <div v-if="errors[`${activeIpKey}.${index}`]" class="invalid-feedback d-block">
                                {{ errors[`${activeIpKey}.${index}`][0] }}
                            </div>
                        </div>

                        <button type="button" class="ip-add-btn" @click="addRow">
                            <Plus width="15" height="15" />
                            Add IP
                        </button>
                    </div>
                </div>
            </template>

            <div class="ai-footer">
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2"
                    :disabled="preloader" @click="submit">
                    <app-button-loader v-if="preloader" />
                    <CheckCircle v-else width="16" height="16" />
                    Save
                </button>
            </div>
        </template>
    </div>
</template>

<style scoped lang="scss">
.ai-wrap { display: flex; flex-direction: column; }

.ai-row {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 28px;
    padding: 24px 28px;
    align-items: start;
}

.ai-row__label { padding-top: 2px; }

.ai-row__title {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 5px;
}

.ai-row__sub {
    font-size: 0.775rem;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
    line-height: 1.5;
}

.ai-row__body { display: flex; flex-direction: column; gap: 10px; }

.ai-divider { height: 1px; background: var(--border-color); margin: 0 28px; }

.ip-radio-group { display: flex; gap: 28px; align-items: center; }

.ip-radio {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.83rem;
    font-weight: 500;
    color: var(--font-color);
    cursor: pointer;

    input[type="radio"] {
        width: 16px;
        height: 16px;
        accent-color: #3b82f6;
        cursor: pointer;
    }
}

.ip-input-row {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;

    .form-control {
        flex: 1;
        min-width: 0;
    }
}

.ip-remove-btn {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    border: 1.5px solid #fecaca;
    background: #fff5f5;
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all .18s;

    &:hover {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
    }
}

.ip-add-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border-radius: 8px;
    border: 1.5px dashed #93c5fd;
    background: #f0f9ff;
    color: #2563eb;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .18s;
    align-self: flex-start;

    &:hover {
        background: #dbeafe;
        border-color: #3b82f6;
    }
}

.ai-footer {
    padding: 20px 28px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    background: var(--dashboard-bg);
}
</style>

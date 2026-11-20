<script setup>
defineOptions({ inheritAttrs: false });

import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES, HRM_DEPARTMENTS, HRM_DESIGNATIONS, HRM_EMPLOYEE_STATUSES } from '@/services/endpoints/hrm.js';
import { useToast } from 'vue-toastification';
import { Building, Suitcase, UserBadgeCheck, Coins, EditPencil, Xmark, Check } from '@iconoir/vue';

const route      = useRoute();
const toast      = useToast();
const employeeId = route.params.id;

/* ── Job history data ───────────────────────── */
const loading = ref(true);
const history = ref({ departments: [], designations: [], statuses: [], salaries: [] });

const fetchHistory = async () => {
    loading.value = true;
    try {
        const { data } = await Axios.get(`${HRM_EMPLOYEES}/${employeeId}/job-history`);
        history.value = data;
    } finally {
        loading.value = false;
    }
};

onMounted(fetchHistory);

/* ── Dropdown options ───────────────────────── */
const options = reactive({ departments: [], designations: [], statuses: [], loaded: false });

const loadOptions = async () => {
    if (options.loaded) return;
    const [deps, des, sts] = await Promise.all([
        Axios.get(`${HRM_DEPARTMENTS}?per_page=500`),
        Axios.get(`${HRM_DESIGNATIONS}?per_page=500`),
        Axios.get(`${HRM_EMPLOYEE_STATUSES}?per_page=500`),
    ]);
    options.departments  = deps.data.data.map(d => ({ id: d.id, value: d.name }));
    options.designations = des.data.data.map(d => ({ id: d.id, value: d.name }));
    options.statuses     = sts.data.data.map(d => ({ id: d.id, value: d.name }));
    options.loaded = true;
};

/* ── Edit state ─────────────────────────────── */
const editKey     = ref(null);
const formValue   = ref(null);
const formError   = ref('');
const saving      = ref(false);

const openEdit = async (key) => {
    await loadOptions();
    editKey.value   = key;
    formValue.value = null;
    formError.value = '';
};

const cancelEdit = () => { editKey.value = null; formError.value = ''; };

const save = async (section) => {
    formError.value = '';
    if (!formValue.value && section.type !== 'salary') {
        formError.value = `Please select a ${section.title.toLowerCase()}.`;
        return;
    }
    if (section.type === 'salary' && (formValue.value === null || formValue.value === '')) {
        formError.value = 'Please enter a salary amount.';
        return;
    }
    try {
        saving.value = true;
        const payload = { type: section.apiType };
        if (section.apiType === 'salary') {
            payload.amount = formValue.value;
        } else {
            payload[section.fieldName] = formValue.value;
        }

        await Axios.post(`${HRM_EMPLOYEES}/${employeeId}/job-history`, payload);
        toast.success(`${section.title} updated successfully.`);
        editKey.value = null;
        await fetchHistory();
    } catch (err) {
        const msg = err.response?.data?.message ?? err.response?.data?.error ?? 'Update failed.';
        formError.value = msg;
    } finally {
        saving.value = false;
    }
};

/* ── Sections config ────────────────────────── */
const sections = computed(() => [
    {
        key: 'departments',
        apiType: 'department',
        fieldName: 'department_id',
        title: 'Department',
        type: 'select',
        optionsKey: 'departments',
        icon: Building,
        color: '#3b82f6',
        bg: 'rgba(59,130,246,0.08)',
        items: history.value.departments,
        label: (item) => item.label ?? '—',
    },
    {
        key: 'designations',
        apiType: 'designation',
        fieldName: 'designation_id',
        title: 'Designation',
        type: 'select',
        optionsKey: 'designations',
        icon: Suitcase,
        color: '#8b5cf6',
        bg: 'rgba(139,92,246,0.08)',
        items: history.value.designations,
        label: (item) => item.label ?? '—',
    },
    {
        key: 'statuses',
        apiType: 'status',
        fieldName: 'employee_status_id',
        title: 'Employee Status',
        type: 'select',
        optionsKey: 'statuses',
        icon: UserBadgeCheck,
        color: '#f59e0b',
        bg: 'rgba(245,158,11,0.08)',
        items: history.value.statuses,
        label: (item) => item.label ?? '—',
    },
    {
        key: 'salaries',
        apiType: 'salary',
        fieldName: null,
        title: 'Salary',
        type: 'salary',
        optionsKey: null,
        icon: Coins,
        color: '#22c55e',
        bg: 'rgba(34,197,94,0.08)',
        items: history.value.salaries,
        label: (item) => fmtSalary(item.amount),
    },
]);

/* ── Helpers ────────────────────────────────── */
const fmtDate = (d) => {
    if (!d) return 'Present';
    const [y, m, day] = d.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${parseInt(day)} ${months[parseInt(m) - 1]} ${y}`;
};

const fmtSalary = (amount) => {
    if (amount == null) return '—';
    return '$ ' + parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <div>
        <div class="detail-section-header">
            <h6 class="detail-section-title">Job History</h6>
            <p class="detail-section-sub">Complete career history — update to close the current record and start a new one</p>
        </div>

        <app-loader v-if="loading" />

        <div v-else class="row g-4">
            <div v-for="section in sections" :key="section.key" class="col-12 col-lg-6">
                <div class="jh-card">

                    <!-- Card header -->
                    <div class="jh-card-header" :style="{ borderLeftColor: section.color }">
                        <div class="jh-card-icon" :style="{ background: section.bg }">
                            <component :is="section.icon" :style="{ color: section.color }" />
                        </div>
                        <div class="jh-card-meta">
                            <span class="jh-card-title">{{ section.title }}</span>
                            <span class="jh-card-count">{{ section.items.length }} {{ section.items.length === 1 ? 'record' : 'records' }}</span>
                        </div>
                        <button
                            class="jh-edit-btn"
                            :style="editKey === section.key ? { background: section.bg, color: section.color } : {}"
                            @click="editKey === section.key ? cancelEdit() : openEdit(section.key)"
                            :title="editKey === section.key ? 'Cancel' : `Update ${section.title}`"
                        >
                            <Xmark v-if="editKey === section.key" />
                            <EditPencil v-else />
                        </button>
                    </div>

                    <!-- Inline edit form -->
                    <Transition name="jh-slide">
                        <div v-if="editKey === section.key" class="jh-edit-form">
                            <p class="jh-edit-label">
                                New {{ section.title }}
                                <span v-if="section.items.some(i => i.is_current)" class="jh-edit-hint">
                                    — current record will be closed today
                                </span>
                            </p>

                            <!-- Select dropdown (department / designation / status) -->
                            <div v-if="section.type === 'select'">
                                <app-input
                                    :id="`jh_${section.key}`"
                                    type="select"
                                    :options="options[section.optionsKey]"
                                    :show-search="true"
                                    v-model="formValue"
                                    :choose-label="`Select ${section.title}`"
                                />
                            </div>

                            <!-- Salary number input -->
                            <div v-else>
                                <app-input
                                    :id="`jh_${section.key}`"
                                    type="number"
                                    v-model="formValue"
                                    placeholder="Enter new salary amount"
                                />
                            </div>

                            <div v-if="formError" class="jh-form-error">{{ formError }}</div>

                            <div class="jh-edit-actions">
                                <button class="btn btn-sm btn-outline-secondary" @click="cancelEdit">Cancel</button>
                                <button
                                    class="btn btn-sm btn-primary"
                                    :disabled="saving"
                                    @click="save(section)"
                                >
                                    <app-button-loader v-if="saving" />
                                    <Check v-else />
                                    {{ saving ? 'Saving…' : 'Save' }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                    <!-- Empty -->
                    <div v-if="!section.items.length" class="jh-empty">
                        No {{ section.title.toLowerCase() }} history found.
                    </div>

                    <!-- Timeline -->
                    <div v-else class="jh-timeline">
                        <div
                            v-for="(item, idx) in section.items"
                            :key="item.id"
                            class="jh-entry"
                            :class="{ 'jh-entry--current': item.is_current, 'jh-entry--last': idx === section.items.length - 1 }"
                        >
                            <div class="jh-connector">
                                <div
                                    class="jh-dot"
                                    :style="item.is_current ? { background: section.color, boxShadow: `0 0 0 4px ${section.color}22` } : {}"
                                ></div>
                                <div v-if="idx < section.items.length - 1" class="jh-line"></div>
                            </div>

                            <div class="jh-content">
                                <div class="jh-content-top">
                                    <span class="jh-value">{{ section.label(item) }}</span>
                                    <span
                                        v-if="item.is_current"
                                        class="jh-badge-current"
                                        :style="{ background: section.bg, color: section.color }"
                                    >Current</span>
                                </div>
                                <div class="jh-date-range">
                                    <span class="jh-date-from">{{ fmtDate(item.start_date) }}</span>
                                    <span class="jh-date-sep">→</span>
                                    <span class="jh-date-to" :class="{ 'jh-date-present': item.is_current }">
                                        {{ fmtDate(item.end_date) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.detail-section-header {
    padding-bottom: 18px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 24px;
}
.detail-section-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}
.detail-section-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

/* ── Card ───────────────────────────────────── */
.jh-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    overflow: hidden;
    height: 100%;
}

.jh-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: var(--dashboard-bg);
    border-bottom: 1px solid var(--border-color);
    border-left: 4px solid transparent;
}

.jh-card-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    svg { width: 18px; height: 18px; }
}

.jh-card-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.jh-card-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--font-color);
    line-height: 1.2;
}

.jh-card-count {
    font-size: 11px;
    color: var(--secondary-font-color, #6b7280);
}

.jh-edit-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--secondary-font-color, #6b7280);
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
    svg { width: 15px; height: 15px; }

    &:hover {
        background: var(--primary-light, rgba(43,135,104,0.08));
        color: var(--primary);
        border-color: var(--primary);
    }
}

/* ── Inline edit form ───────────────────────── */
.jh-edit-form {
    padding: 16px;
    background: var(--dashboard-bg);
    border-bottom: 1px solid var(--border-color);
}

.jh-edit-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--font-color);
    margin: 0 0 6px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.jh-edit-hint {
    font-weight: 400;
    text-transform: none;
    letter-spacing: 0;
    color: var(--secondary-font-color, #6b7280);
}

.jh-form-error {
    margin-top: 10px;
    font-size: 12px;
    color: #dc2626;
    background: #fee2e2;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    padding: 8px 12px;
}

.jh-edit-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 14px;

    .btn { display: flex; align-items: center; gap: 5px; }
    svg { width: 14px; height: 14px; }
}

/* ── Slide transition ───────────────────────── */
.jh-slide-enter-active,
.jh-slide-leave-active { transition: max-height 0.25s ease, opacity 0.2s ease; overflow: hidden; }
.jh-slide-enter-from,
.jh-slide-leave-to { max-height: 0; opacity: 0; }
.jh-slide-enter-to,
.jh-slide-leave-from { max-height: 400px; opacity: 1; }

/* ── Empty ──────────────────────────────────── */
.jh-empty {
    padding: 28px 20px;
    text-align: center;
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
}

/* ── Timeline ───────────────────────────────── */
.jh-timeline { padding: 18px 18px 6px; }

.jh-entry {
    display: flex;
    gap: 14px;
    padding-bottom: 18px;
    &.jh-entry--last { padding-bottom: 10px; }
}

.jh-connector {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    width: 14px;
}

.jh-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--border-color);
    flex-shrink: 0;
    margin-top: 4px;
    transition: background 0.2s, box-shadow 0.2s;
}

.jh-line {
    width: 2px;
    flex: 1;
    background: var(--border-color);
    margin-top: 6px;
    min-height: 20px;
    border-radius: 1px;
}

.jh-content { flex: 1; min-width: 0; }

.jh-content-top {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}

.jh-value {
    font-size: 14px;
    font-weight: 700;
    color: var(--font-color);
    line-height: 1.2;
}

.jh-badge-current {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.6;
}

.jh-date-range {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--secondary-font-color, #6b7280);
}

.jh-date-sep { opacity: 0.5; }

.jh-date-present { color: #16a34a; font-weight: 600; }

.jh-entry:not(.jh-entry--current) {
    .jh-value { font-weight: 500; color: var(--secondary-font-color, #6b7280); }
}
</style>

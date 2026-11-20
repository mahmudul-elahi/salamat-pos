<script setup>
defineOptions({ inheritAttrs: false });

import { ref, markRaw, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from 'vue-toastification';
import Axios from '@/services/axios/index.js';
import { HRM_PAYSLIPS } from '@/services/endpoints/hrm.js';
import useEmitter from '@/composable/emitter/useEmitter.js';
import { NavArrowDown, Download } from '@iconoir/vue';

const route      = useRoute();
const toast      = useToast();
const emitter    = useEmitter();
const employeeId = route.params.id;

const tableId = `emp-payroll-${employeeId}`;

/* ── Filters ─────────────────────────────────── */
const now            = new Date();
const selectedYear   = ref(now.getFullYear());
const selectedStatus = ref('');

const yearRange = Array.from({ length: 5 }, (_, i) => now.getFullYear() - i);

const STATUS_FILTERS = [
    { value: '',          label: 'All' },
    { value: 'generated', label: 'Generated' },
    { value: 'sent',      label: 'Sent' },
];

const buildUrl = () =>
    `${HRM_PAYSLIPS}?employee_id=${employeeId}&year=${selectedYear.value}${selectedStatus.value ? '&status=' + selectedStatus.value : ''}`;

/* ── Datatable options ───────────────────────── */
const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '—';

const payrunCell = (payrun, row) => `
  <div style="min-width:180px">
    <div style="font-weight:600;font-size:13px;color:var(--font-color, #1e293b)">${payrun ?? '—'}</div>
    <div style="color:#6b7280;font-size:11px;margin-top:3px">${row.period ?? ''}</div>
    ${row.payrun_uuid ? `<span style="display:inline-flex;align-items:center;font-size:10px;font-weight:600;color:#4f46e5;background:rgba(99,102,241,0.08);padding:2px 7px;border-radius:20px;margin-top:4px;border:1px solid rgba(99,102,241,0.18)">ID: ${row.payrun_uuid.slice(0, 8)}</span>` : ''}
  </div>`;

const periodCell = (val) =>
    val ? `<span style="display:inline-flex;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(14,165,233,0.08);color:#0284c7;border:1px solid rgba(14,165,233,0.18)">${capitalize(val)}</span>`
        : '<span style="color:#9ca3af">—</span>';

const considerTypeCell = (val) =>
    val ? `<span style="display:inline-flex;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(168,85,247,0.08);color:#7c3aed;border:1px solid rgba(168,85,247,0.18)">${capitalize(val)}</span>`
        : '<span style="color:#9ca3af">—</span>';

const payrunTypeCell = (val) => {
    const map = {
        settings:   { label: 'Settings',   bg: 'rgba(245,158,11,0.08)', color: '#b45309', border: 'rgba(245,158,11,0.2)' },
        customized: { label: 'Customized', bg: 'rgba(34,197,94,0.08)',  color: '#15803d', border: 'rgba(34,197,94,0.2)'  },
        auto:       { label: 'Auto',       bg: 'rgba(99,102,241,0.08)', color: '#4338ca', border: 'rgba(99,102,241,0.2)' },
    };
    const c = map[val] ?? { label: val ?? '—', bg: 'rgba(107,114,128,0.08)', color: '#4b5563', border: 'rgba(107,114,128,0.2)' };
    return `<span style="display:inline-flex;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:${c.bg};color:${c.color};border:1px solid ${c.border}">${c.label}</span>`;
};

const salaryCell = (val) => {
    const n = parseFloat(val ?? 0);
    return `<span style="font-weight:700;font-size:13px">${'$' + n.toFixed(2)}</span>`;
};

const netSalaryCell = (val) => {
    const n = parseFloat(val ?? 0);
    if (!val || n === 0) return '<span style="color:#9ca3af;font-size:13px">—</span>';
    return `<span style="font-weight:700;font-size:13px;color:#16a34a">${'$' + n.toFixed(2)}</span>`;
};

const statusCell = (status, row) => {
    const cfg = {
        generated: { label: 'Generated', bg: '#1e293b',   color: '#fff' },
        sent:      { label: 'Sent',      bg: '#22c55e',   color: '#fff' },
    };
    const c = cfg[status] ?? { label: status ?? '—', bg: '#6b7280', color: '#fff' };
    const badge = `<span style="display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;background:${c.bg};color:${c.color};white-space:nowrap">${c.label}</span>`;
    const sentAt = row.sent_at
        ? `<div style="font-size:10px;color:#9ca3af;margin-top:4px">${row.sent_at}</div>`
        : '';
    return `<div>${badge}${sentAt}</div>`;
};

const options = ref({
    url: buildUrl(),
    pageSize: 10,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: false,
    showFilter: false,
    columns: [
        { title: 'Payrun',        key: 'payrun',        type: 'custom-html', modifier: payrunCell },
        { title: 'Period',        key: 'payrun_period', type: 'custom-html', modifier: periodCell },
        { title: 'Consider Type', key: 'consider_type', type: 'custom-html', modifier: considerTypeCell },
        { title: 'Payrun Type',   key: 'payrun_type',   type: 'custom-html', modifier: payrunTypeCell },
        { title: 'Salary',        key: 'basic_salary',  type: 'custom-html', modifier: salaryCell },
        { title: 'Net Salary',    key: 'net_salary',    type: 'custom-html', modifier: netSalaryCell },
        { title: 'Status',        key: 'status',        type: 'custom-html', modifier: statusCell },
        { title: 'Action', type: 'action', align: 'right', permission: true },
    ],
    actions: [
        { title: 'Download', type: 'download', icon: markRaw(Download), modifier: () => true },
    ],
});

watch([selectedYear, selectedStatus], () => {
    options.value.url = buildUrl();
    emitter.emit('reload-' + tableId);
});

/* ── Download handler ────────────────────────── */
const downloadPayslip = (row) => {
    Axios.get(`${HRM_PAYSLIPS}/${row.id}/download`, { params: { preview: 1 }, responseType: 'blob' })
        .then((response) => {
            const file = new Blob([response.data], { type: 'application/pdf' });
            window.open(URL.createObjectURL(file));
        })
        .catch(() => toast.error('Failed to download payslip.'));
};

const actionTiger = (row, action) => {
    if (action.type === 'download') downloadPayslip(row);
};
</script>

<template>
    <div>
        <!-- Page header -->
        <div class="pr-page-header">
            <div>
                <h6 class="pr-page-title">Payroll</h6>
                <p class="pr-page-sub">Payslip history and earnings overview</p>
            </div>

            <!-- Filters -->
            <div class="pr-filters">
                <!-- Status pills -->
                <div class="pr-status-pills">
                    <button
                        v-for="sf in STATUS_FILTERS"
                        :key="sf.value"
                        class="pr-pill"
                        :class="{ 'pr-pill--active': selectedStatus === sf.value }"
                        @click="selectedStatus = sf.value"
                    >
                        {{ sf.label }}
                    </button>
                </div>

                <!-- Year select -->
                <div class="pr-year-select-wrap">
                    <select class="pr-year-select" v-model.number="selectedYear">
                        <option v-for="y in yearRange" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <NavArrowDown class="pr-year-chevron" />
                </div>
            </div>
        </div>

        <!-- Datatable card -->
        <div class="pr-table-card">
            <app-datatable :id="tableId" :options="options" :button-visible="false" @action="actionTiger" />
        </div>
    </div>
</template>

<style scoped lang="scss">
/* ── Page header ──────────────────────────────── */
.pr-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 28px;
    gap: 16px;
    flex-wrap: wrap;
}
.pr-page-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}
.pr-page-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

/* ── Filters ──────────────────────────────────── */
.pr-filters {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* Status pills */
.pr-status-pills {
    display: flex;
    align-items: center;
    gap: 4px;
    background: var(--dashboard-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 4px;
}
.pr-pill {
    padding: 5px 14px;
    border-radius: 7px;
    border: none;
    background: transparent;
    font-size: 12px;
    font-weight: 600;
    color: var(--secondary-font-color, #6b7280);
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;

    &:hover {
        background: var(--white);
        color: var(--font-color);
    }

    &--active {
        background: var(--white) !important;
        color: var(--primary) !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
}

/* Year select */
.pr-year-select-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.pr-year-select {
    appearance: none;
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 7px 32px 7px 14px;
    font-size: 13px;
    font-weight: 600;
    color: var(--font-color);
    cursor: pointer;
    outline: none;
    transition: border-color 0.15s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);

    &:focus { border-color: var(--primary); }
}
.pr-year-chevron {
    position: absolute;
    right: 10px;
    width: 14px;
    height: 14px;
    color: var(--secondary-font-color, #6b7280);
    pointer-events: none;
}

/* ── Table card ───────────────────────────────── */
.pr-table-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
</style>

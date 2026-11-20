<script setup>
defineOptions({ inheritAttrs: false });

import { ref, computed, onMounted, onUnmounted, nextTick, markRaw } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES, HRM_ATTENDANCES } from '@/services/endpoints/hrm.js';
import LocationViewModal from '@/views/landlord/hrm/attendance/attendance/LocationViewModal.vue';
import { Chart, ArcElement, DoughnutController, Tooltip } from 'chart.js';
import { Activity, Clock, CalendarCheck, Hourglass, Timer, Medal } from '@iconoir/vue';

Chart.register(ArcElement, DoughnutController, Tooltip);

const route      = useRoute();
const employeeId = route.params.id;

/* ── Current month ───────────────────────────── */
const now   = new Date();
const month = now.getMonth() + 1;
const year  = now.getFullYear();
const monthLabel = now.toLocaleString('en-US', { month: 'long', year: 'numeric' });

/* ── Stats ───────────────────────────────────── */
const loading = ref(true);
const stats   = ref(null);

onMounted(async () => {
    try {
        const { data } = await Axios.get(
            `${HRM_EMPLOYEES}/${employeeId}/attendance-stats?month=${month}&year=${year}`
        );
        stats.value = data;
    } finally {
        loading.value = false;
        nextTick(renderChart);
    }
});

/* ── Average behavior label ──────────────────── */
const avgBehaviorLabel = computed(() => {
    if (!stats.value) return '—';
    const ab = stats.value.average_behavior ?? {};
    const entries = Object.entries(ab).filter(([, v]) => v > 0);
    if (!entries.length) return 'N/A';
    const [key] = entries.sort((a, b) => b[1] - a[1])[0];
    return key.charAt(0).toUpperCase() + key.slice(1);
});

const behaviorColor = { Late: '#f59e0b', Regular: '#22c55e', Absent: '#ef4444', Early: '#3b82f6', 'N/A': '#94a3b8' };

/* ── Work availability ───────────────────────── */
const availabilityStatus = computed(() => stats.value?.work_availability_status ?? null);
const availabilityColor  = computed(() => {
    if (availabilityStatus.value === 'good') return '#22c55e';
    if (availabilityStatus.value === 'bad')  return '#ef4444';
    return '#1e293b';
});

/* ── Stat cards config ───────────────────────── */
const statCards = computed(() => [
    {
        key: 'avg_behavior',
        label: 'Average Behaviour',
        value: avgBehaviorLabel.value,
        color: behaviorColor[avgBehaviorLabel.value] ?? '#94a3b8',
        icon: markRaw(Activity),
        isText: true,
    },
    {
        key: 'work_shift',
        label: 'Work Shift Hours',
        value: stats.value?.work_shift_hours ?? '—',
        color: '#ef4444',
        icon: markRaw(CalendarCheck),
    },
    {
        key: 'attendance',
        label: 'Attendance Hours',
        value: stats.value?.attendance_hours ?? '—',
        color: '#22c55e',
        icon: markRaw(Clock),
    },
    {
        key: 'leave',
        label: 'Leave Hours',
        value: stats.value?.leave_hours ?? '—',
        color: '#3b82f6',
        icon: markRaw(Timer),
    },
    {
        key: 'balance',
        label: 'Balance',
        value: stats.value?.balance_hours ?? '—',
        color: '#8b5cf6',
        icon: markRaw(Hourglass),
    },
    {
        key: 'availability',
        label: 'Work Availability',
        value: stats.value ? `${stats.value.work_availability_percent}%` : '—',
        color: availabilityColor.value,
        icon: markRaw(Medal),
        badge: availabilityStatus.value,
        threshold: stats.value?.work_availability_threshold ?? null,
    },
]);

/* ── Donut chart ─────────────────────────────── */
const chartCanvas = ref(null);
let chartInstance = null;

const STATUS_COLOR = {
    late:    '#f59e0b',
    absent:  '#ef4444',
    regular: '#22c55e',
    early:   '#3b82f6',
    holiday: '#94a3b8',
    weekend: '#1e293b',
};

const renderChart = () => {
    if (!chartCanvas.value || !stats.value) return;
    if (chartInstance) { chartInstance.destroy(); chartInstance = null; }

    const counts = stats.value.counts ?? {};
    const order  = ['late', 'absent', 'regular', 'early', 'holiday', 'weekend'];
    const labels = order;
    const data   = order.map(k => counts[k] ?? 0);
    const colors = order.map(k => STATUS_COLOR[k]);

    chartInstance = new Chart(chartCanvas.value, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }] },
        options: {
            cutout: '72%',
            plugins: { legend: { display: false }, tooltip: { callbacks: {
                label: (ctx) => ` ${ctx.label}: ${ctx.parsed} days`,
            }}},
        },
    });
};

/* ── Legend entries ──────────────────────────── */
const legendItems = computed(() => {
    if (!stats.value) return [];
    const counts = stats.value.counts ?? {};
    return [
        { key: 'late',    label: 'Late',    color: STATUS_COLOR.late,    count: counts.late    ?? 0 },
        { key: 'absent',  label: 'Absent',  color: STATUS_COLOR.absent,  count: counts.absent  ?? 0 },
        { key: 'regular', label: 'Regular', color: STATUS_COLOR.regular, count: counts.regular ?? 0 },
        { key: 'early',   label: 'Early',   color: STATUS_COLOR.early,   count: counts.early   ?? 0 },
        { key: 'holiday', label: 'Holiday', color: STATUS_COLOR.holiday, count: counts.holiday ?? 0 },
        { key: 'weekend', label: 'Weekend', color: STATUS_COLOR.weekend, count: counts.weekend ?? 0 },
    ];
});

/* ── Location modal ──────────────────────────── */
const isLocationModalActive = ref(false);
const locationModalData     = ref(null);
const locationModalTitle    = ref('');

window.__viewEmpAttendanceLocation = (locStr, title) => {
    try {
        locationModalData.value     = JSON.parse(decodeURIComponent(locStr));
        locationModalTitle.value    = title;
        isLocationModalActive.value = true;
    } catch {}
};
onUnmounted(() => { delete window.__viewEmpAttendanceLocation; });

const makeLocCell = (ip, loc, title) => {
    if (!ip && !loc) return '<span class="text-muted">—</span>';
    const enc = encodeURIComponent(JSON.stringify(loc ?? { ip }));
    return `<button class="btn btn-link btn-sm p-0 text-start" style="color:inherit"
        onclick="window.__viewEmpAttendanceLocation('${enc}', '${title}')">
        📍 <span style="text-decoration:underline dotted">${ip ?? 'View'}</span>
    </button>`;
};

/* ── Datatable ───────────────────────────────── */
const tableId  = `emp-attendance-${employeeId}`;
const tableUrl = `${HRM_ATTENDANCES}?user_id=${employeeId}&month=${month}&year=${year}`;

const behaviorBadge = {
    early:   'badge rounded-pill border border-info    text-info    bg-info-subtle    px-3',
    late:    'badge rounded-pill border border-warning text-warning bg-warning-subtle px-3',
    regular: 'badge rounded-pill border border-success text-success bg-success-subtle px-3',
};

const initials = (name = '') => {
    const parts = name.trim().split(' ').filter(Boolean);
    return ((parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')).toUpperCase();
};
const avatarStyle = 'width:34px;height:34px;border-radius:50%;background:#ede9fe;color:#4338ca;'
    + 'display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;flex-shrink:0;';

const profileCell = (name, row) => `
  <div style="display:flex;align-items:center;gap:8px">
    <div style="${avatarStyle}">${initials(name)}</div>
    <div>
      <div style="font-weight:600;font-size:13px">${name ?? '—'}</div>
      <div style="color:#6b7280;font-size:11px">ID: ${row.employee_code ?? '—'}</div>
    </div>
  </div>`;

const tableOptions = ref({
    url: tableUrl,
    pageSize: 10,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: false,
    columns: [
        { title: 'Profile',      key: 'employee',    type: 'custom-html', modifier: profileCell },
        { title: 'In Date',      key: 'in_date',     type: 'text' },
        { title: 'Punch In',     key: 'in_time',     type: 'text' },
        { title: 'In Location',  key: 'in_ip',  type: 'custom-html', modifier: (ip, row) => makeLocCell(ip, row?.in_location,  'Punch In Location') },
        { title: 'Punch Out',    key: 'out_time',    type: 'text' },
        { title: 'Out Location', key: 'out_ip', type: 'custom-html', modifier: (ip, row) => makeLocCell(ip, row?.out_location, 'Punch Out Location') },
        {
            title: 'Behavior', key: 'behavior', type: 'custom-class',
            modifier: (b) => behaviorBadge[b] ?? 'badge rounded-pill border border-secondary text-secondary bg-secondary-subtle px-3',
        },
        { title: 'Break Times',  key: 'break_time',  type: 'text' },
        { title: 'Total Hours',  key: 'total_hours', type: 'text' },
    ],
    actions: [],
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="at-header">
            <div>
                <h6 class="at-title">Attendance</h6>
                <p class="at-sub">{{ monthLabel }} — current month overview</p>
            </div>
        </div>

        <!-- Top section: Overview + Stats -->
        <div class="row g-4 align-items-stretch mb-4">

            <!-- Donut card -->
            <div class="col-12 col-lg-5 d-flex">
                <div class="at-card w-100">
                    <p class="at-card-title">Attendance Overview</p>

                    <app-loader v-if="loading" />
                    <div v-else class="at-overview-body">
                        <div class="at-chart-wrap">
                            <canvas ref="chartCanvas" width="160" height="160"></canvas>
                        </div>
                        <div class="at-legend">
                            <div v-for="item in legendItems" :key="item.key" class="at-legend-row">
                                <span class="at-legend-dot" :style="{ background: item.color }"></span>
                                <span class="at-legend-label">{{ item.label }}</span>
                                <span class="at-legend-count">{{ item.count }} days</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat cards grid -->
            <div class="col-12 col-lg-7 d-flex">
                <div class="w-100">
                    <app-loader v-if="loading" />
                    <div v-else class="row g-3 h-100">
                        <div
                            v-for="card in statCards"
                            :key="card.key"
                            class="col-12 col-md-4 d-flex"
                        >
                            <div class="at-stat-card w-100">
                                <div class="at-stat-indicator" :style="{ background: card.color }"></div>
                                <span class="at-stat-label">{{ card.label }}</span>
                                <span
                                    class="at-stat-value"
                                    :class="{ 'at-stat-value--text': card.isText }"
                                    :style="card.isText ? { color: card.color } : {}"
                                >{{ card.value }}</span>
                                <template v-if="card.badge">
                                    <div class="at-availability-row">
                                        <span
                                            class="at-avail-badge"
                                            :class="card.badge === 'good' ? 'at-avail-badge--good' : 'at-avail-badge--bad'"
                                        >{{ card.badge === 'good' ? 'Good' : 'Bad' }}</span>
                                        <span v-if="card.threshold" class="at-avail-threshold">
                                            Threshold: {{ card.threshold }}%
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datatable -->
        <div class="at-card at-card--table">
            <app-datatable :id="tableId" :options="tableOptions" :button-visible="false" />
        </div>
    </div>

    <location-view-modal v-if="isLocationModalActive" modal-id="emp-location-modal"
        :title="locationModalTitle" :location="locationModalData"
        @close="isLocationModalActive = false" />
</template>

<style scoped lang="scss">
/* ── Header ───────────────────────────────────── */
.at-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 28px;
}
.at-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}
.at-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

/* ── Cards ────────────────────────────────────── */
.at-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);

    &--table {
        padding: 0;
        overflow: hidden;
    }
}

.at-card-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 18px;
}

/* ── Overview body ────────────────────────────── */
.at-overview-body {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.at-chart-wrap {
    flex-shrink: 0;
    canvas { display: block; }
}

/* ── Legend ───────────────────────────────────── */
.at-legend {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 140px;
}

.at-legend-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.at-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.at-legend-label {
    flex: 1;
    font-size: 13px;
    color: var(--font-color);
}

.at-legend-count {
    font-size: 13px;
    font-weight: 700;
    color: var(--font-color);
    white-space: nowrap;
}

/* ── Stat cards ───────────────────────────────── */
.at-stat-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    transition: box-shadow 0.15s;
    &:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
}

.at-stat-indicator {
    width: 28px;
    height: 6px;
    border-radius: 20px;
    margin-bottom: 4px;
}

.at-stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--secondary-font-color, #6b7280);
    line-height: 1.2;
}

.at-stat-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--secondary-font-color, #94a3b8);
    line-height: 1.2;

    &--text {
        font-size: 20px;
    }
}

.at-availability-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
    flex-wrap: wrap;
}

.at-avail-badge {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 100px;
    border: 1.5px solid;
    letter-spacing: 0.02em;

    &--good { color: #16a34a; border-color: #16a34a; background: #f0fdf4; }
    &--bad  { color: #dc2626; border-color: #dc2626; background: #fef2f2; }
}

.at-avail-threshold {
    font-size: 0.68rem;
    color: var(--secondary-font-color, #9ca3af);
}
</style>

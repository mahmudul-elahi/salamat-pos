<script setup>
import { ref, onMounted, onBeforeUnmount, computed, nextTick, watch } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES } from '@/services/endpoints/hrm.js';
import { NavArrowLeft, NavArrowRight } from '@iconoir/vue';
import { Chart, ArcElement, DoughnutController, Tooltip } from 'chart.js';

Chart.register(ArcElement, DoughnutController, Tooltip);

const route      = useRoute();
const employeeId = route.params.id;

const now          = new Date();
const currentMonth = ref(now.getMonth() + 1);
const currentYear  = ref(now.getFullYear());

const calendarData = ref({});
const statsData    = ref(null);
const loading      = ref(true);
const chartRef     = ref(null);
let   chartInstance = null;

const MONTH_NAMES = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December',
];

const STATUS_STYLE = {
    absent:  { bg: '#ef4444', fg: '#fff',    shadow: 'rgba(239,68,68,0.3)'    },
    late:    { bg: '#f59e0b', fg: '#fff',    shadow: 'rgba(245,158,11,0.3)'   },
    early:   { bg: '#3b82f6', fg: '#fff',    shadow: 'rgba(59,130,246,0.3)'   },
    regular: { bg: '#22c55e', fg: '#fff',    shadow: 'rgba(34,197,94,0.3)'    },
    holiday: { bg: '#94a3b8', fg: '#fff',    shadow: 'rgba(148,163,184,0.3)'  },
    leave:   { bg: '#06b6d4', fg: '#fff',    shadow: 'rgba(6,182,212,0.3)'    },
    weekend: { bg: '#ede9fe', fg: '#7c3aed', shadow: 'rgba(124,58,237,0.15)'  },
};

const LEGEND_ITEMS = [
    { label: 'Absent',  key: 'absent'  },
    { label: 'Weekend', key: 'weekend' },
    { label: 'Late',    key: 'late'    },
    { label: 'Early',   key: 'early'   },
    { label: 'Regular', key: 'regular' },
    { label: 'Holiday', key: 'holiday' },
];

const CHART_CONFIG = [
    { key: 'late',    label: 'Late',    color: '#f59e0b' },
    { key: 'absent',  label: 'Absent',  color: '#ef4444' },
    { key: 'regular', label: 'Regular', color: '#22c55e' },
    { key: 'early',   label: 'Early',   color: '#3b82f6' },
    { key: 'holiday', label: 'Holiday', color: '#94a3b8' },
    { key: 'weekend', label: 'Weekend', color: '#7c3aed' },
];

/* ── Calendar ──────────────────────────────────────── */
const calendarWeeks = computed(() => {
    const y = currentYear.value, m = currentMonth.value;
    const firstDow    = new Date(y, m - 1, 1).getDay();
    const daysInMonth = new Date(y, m, 0).getDate();
    const weeks = [];
    let week = Array(firstDow).fill(null);
    for (let d = 1; d <= daysInMonth; d++) {
        week.push(d);
        if (week.length === 7) { weeks.push([...week]); week = []; }
    }
    if (week.length) { while (week.length < 7) week.push(null); weeks.push(week); }
    return weeks;
});

const dateKey = (day) => {
    const m = String(currentMonth.value).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    return `${currentYear.value}-${m}-${d}`;
};

const getStatus = (day) => day ? (calendarData.value[dateKey(day)] ?? null) : null;

const dayStyle = (day) => {
    const s = STATUS_STYLE[getStatus(day)];
    if (!s) return {};
    return {
        backgroundColor: s.bg,
        color: s.fg,
        boxShadow: `0 4px 10px ${s.shadow}`,
    };
};

const isToday = (day) => {
    if (!day) return false;
    return dateKey(day) === now.toISOString().slice(0, 10);
};

/* ── Month nav ─────────────────────────────────────── */
const prevMonth = () => {
    currentMonth.value === 1
        ? (currentMonth.value = 12, currentYear.value--)
        : currentMonth.value--;
};
const nextMonth = () => {
    currentMonth.value === 12
        ? (currentMonth.value = 1, currentYear.value++)
        : currentMonth.value++;
};

/* ── Chart ─────────────────────────────────────────── */
const chartItems = computed(() => {
    const counts = statsData.value?.counts ?? {};
    return CHART_CONFIG.map(c => ({ ...c, value: counts[c.key] ?? 0 }));
});

const totalDays = computed(() => chartItems.value.reduce((s, i) => s + i.value, 0));

const renderChart = () => {
    if (!chartRef.value) return;
    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(chartRef.value, {
        type: 'doughnut',
        data: {
            labels: chartItems.value.map(i => i.label),
            datasets: [{
                data: chartItems.value.map(i => i.value),
                backgroundColor: chartItems.value.map(i => i.color),
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (c) => ` ${c.label}: ${c.raw} days` } },
            },
        },
        plugins: [{
            id: 'center',
            beforeDraw({ width, height, ctx }) {
                const isDark = document.documentElement.getAttribute('theme') === 'dark';
                ctx.save();
                ctx.textAlign    = 'center';
                ctx.textBaseline = 'middle';
                ctx.font      = '500 11px Inter, sans-serif';
                ctx.fillStyle = isDark ? '#9ca3af' : '#6b7280';
                ctx.fillText('Total Days', width / 2, height / 2 - 11);
                ctx.font      = 'bold 24px Inter, sans-serif';
                ctx.fillStyle = isDark ? '#f9fafb' : '#111827';
                ctx.fillText(totalDays.value, width / 2, height / 2 + 14);
                ctx.restore();
            },
        }],
    });
};

/* ── Fetch ─────────────────────────────────────────── */
const fetchData = async () => {
    loading.value = true;
    try {
        const params = { month: currentMonth.value, year: currentYear.value };
        const [calRes, statsRes] = await Promise.all([
            Axios.get(`${HRM_EMPLOYEES}/${employeeId}/attendance-calendar`, { params }),
            Axios.get(`${HRM_EMPLOYEES}/${employeeId}/attendance-stats`,    { params }),
        ]);
        calendarData.value = calRes.data;
        statsData.value    = statsRes.data;
    } finally {
        loading.value = false;
        await nextTick();
        renderChart();
    }
};

watch([currentMonth, currentYear], fetchData);
onMounted(fetchData);
onBeforeUnmount(() => { if (chartInstance) chartInstance.destroy(); });
</script>

<template>
    <div>
        <div class="detail-section-header">
            <h6 class="detail-section-title">Attendance Overview</h6>
            <p class="detail-section-sub">Monthly attendance calendar and statistics</p>
        </div>

        <div class="row g-4 align-items-stretch">

            <!-- ── Calendar card ──────────────────────── -->
            <div class="col-12 col-lg-6 d-flex">
            <div class="att-card att-card--cal">

                <!-- Header -->
                <div class="att-card-header">
                    <button class="att-nav" type="button" @click="prevMonth">
                        <NavArrowLeft />
                    </button>
                    <div class="att-month-block">
                        <span class="att-month-name">{{ MONTH_NAMES[currentMonth - 1] }}</span>
                        <span class="att-month-year">{{ currentYear }}</span>
                    </div>
                    <button class="att-nav" type="button" @click="nextMonth">
                        <NavArrowRight />
                    </button>
                </div>

                <!-- Skeleton -->
                <div v-if="loading" class="att-skeleton-grid">
                    <div v-for="n in 42" :key="n" class="att-skeleton-day"></div>
                </div>

                <!-- Calendar grid -->
                <template v-else>
                    <div class="att-grid">
                        <!-- DOW row -->
                        <div
                            v-for="h in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']"
                            :key="h"
                            class="att-dow"
                        >{{ h }}</div>

                        <!-- Day cells -->
                        <template v-for="(week, wi) in calendarWeeks" :key="wi">
                            <div
                                v-for="(day, di) in week"
                                :key="`${wi}-${di}`"
                                class="att-cell"
                            >
                                <span
                                    v-if="day"
                                    class="att-day"
                                    :class="{
                                        'att-day--colored': !!getStatus(day),
                                        'att-day--today':   isToday(day) && !getStatus(day),
                                    }"
                                    :style="dayStyle(day)"
                                >{{ day }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Legend -->
                    <div class="att-legend-row">
                        <div
                            v-for="item in LEGEND_ITEMS"
                            :key="item.key"
                            class="att-legend-chip"
                        >
                            <span
                                class="att-legend-dot"
                                :style="{ background: STATUS_STYLE[item.key].bg }"
                            ></span>
                            <span>{{ item.label }}</span>
                        </div>
                    </div>
                </template>
            </div>

            </div><!-- /col -->

            <!-- ── Overview card ──────────────────────── -->
            <div class="col-12 col-lg-6 d-flex">
            <div class="att-card att-card--overview">
                <div class="att-card-header">
                    <span class="att-month-name" style="font-size:14px;">Leave Overview</span>
                </div>

                <app-loader v-if="loading" />

                <template v-else>
                    <div class="att-ov-body">
                        <!-- Donut on the left -->
                        <div class="att-donut-wrap">
                            <canvas ref="chartRef"></canvas>
                        </div>

                        <!-- Legend list on the right -->
                        <div class="att-ov-list">
                            <div v-for="item in chartItems" :key="item.key" class="att-ov-item">
                                <div class="att-ov-left">
                                    <span class="att-ov-swatch" :style="{ background: item.color }"></span>
                                    <span class="att-ov-label">{{ item.label }}</span>
                                </div>
                                <span class="att-ov-count">{{ item.value }} days</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            </div><!-- /col -->

        </div><!-- /row -->
    </div>
</template>

<style scoped lang="scss">
/* ── Section header ────────────────────────── */
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

/* ── Shared card ───────────────────────────── */
.att-card {
    background: var(--white);
    border-radius: 16px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
}

/* ── Card header ───────────────────────────── */
.att-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    background: var(--dashboard-bg);

    &--overview {
        justify-content: flex-start;
        padding: 16px 20px 12px;
        background: transparent;
        border-bottom: none;
    }
}

.att-month-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 1.2;
}

.att-month-name {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
}

.att-month-year {
    font-size: 12px;
    font-weight: 500;
    color: var(--secondary-font-color, #6b7280);
}

.att-nav {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    background: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    flex-shrink: 0;

    svg { width: 15px; height: 15px; color: var(--font-color); }

    &:hover {
        background: var(--primary);
        border-color: var(--primary);
        svg { color: #fff; }
    }
}

/* ── Calendar grid ─────────────────────────── */
.att-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    padding: 16px 12px 8px;
    gap: 2px;
    text-align: center;
}

.att-dow {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--secondary-font-color, #6b7280);
    padding-bottom: 10px;
}

.att-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3px 0;
}

.att-day {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 400;
    color: var(--secondary-font-color, #9ca3af);
    transition: transform 0.15s, box-shadow 0.15s;
    cursor: default;

    &--colored {
        font-weight: 700;
        font-size: 13px;
        &:hover { transform: scale(1.12); }
    }

    &--today {
        font-weight: 700;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
}

/* ── Calendar legend ───────────────────────── */
.att-legend-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 16px;
    padding: 12px 16px 16px;
    border-top: 1px solid var(--border-color);
    background: var(--dashboard-bg);
}

.att-legend-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
    color: var(--secondary-font-color, #6b7280);
}

.att-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ── Skeleton ──────────────────────────────── */
.att-skeleton-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    padding: 16px;

    .att-skeleton-day {
        height: 36px;
        border-radius: 50%;
        background: var(--border-color);
        animation: att-pulse 1.4s ease-in-out infinite;
    }
}

@keyframes att-pulse {
    0%, 100% { opacity: 1;   }
    50%       { opacity: 0.3; }
}

/* ── Overview panel ────────────────────────── */
.att-overview-heading {
    font-size: 14px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0;
}

/* overview body: donut left, legend right */
.att-ov-body {
    display: flex;
    align-items: center;
    gap: 32px;
    padding: 24px;
    flex: 1;
}

.att-donut-wrap {
    width: 240px;
    height: 240px;
    flex-shrink: 0;
}

.att-ov-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
    align-self: center;
    height: fit-content;
}

.att-ov-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.att-ov-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.att-ov-swatch {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.att-ov-label {
    font-size: 15px;
    font-weight: 400;
    color: var(--font-color);
}

.att-ov-count {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    white-space: nowrap;
}
</style>

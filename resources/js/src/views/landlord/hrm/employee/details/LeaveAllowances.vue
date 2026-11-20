<script setup>
defineOptions({ inheritAttrs: false });

import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES } from '@/services/endpoints/hrm.js';

const props  = defineProps({ employeeId: { type: [String, Number], default: null } });
const route  = useRoute();
const empId  = props.employeeId ?? route.params.id;

const loading      = ref(true);
const allowances   = ref([]);

const COLORS = [
    '#6c63ff', '#27ae60', '#f39c12', '#e74c3c',
    '#2980b9', '#8e44ad', '#16a085', '#d35400',
    '#c0392b', '#2c3e50',
];

const color = (i) => COLORS[i % COLORS.length];

const fetchData = async () => {
    loading.value = true;
    try {
        const { data } = await Axios.get(`${HRM_EMPLOYEES}/${empId}/leave-allowances`);
        allowances.value = data;
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

/* ── SVG ring helpers ────────────────────────── */
const pct = (taken, availability) => {
    const total = taken + availability;
    if (!total) return 0;
    return Math.min(Math.round((taken / total) * 100), 100);
};

const ringDash = (taken, availability) => {
    const p = pct(taken, availability);
    return `${p}, 100`;
};

const availColor = (availability, allowance) => {
    if (!allowance) return '#94a3b8';
    const ratio = availability / allowance;
    if (ratio >= 0.5) return '#22c55e';
    if (ratio >= 0.2) return '#f59e0b';
    return '#ef4444';
};
</script>

<template>
    <div>
        <!-- Header -->
        <div class="la-header">
            <div>
                <h6 class="la-title">Leave Allowances</h6>
                <p class="la-sub">Annual entitlement vs usage per leave type</p>
            </div>
        </div>

        <!-- Loading -->
        <app-loader v-if="loading" />

        <!-- Empty -->
        <div v-else-if="!allowances.length" class="la-empty">
            <div class="la-empty-icon">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
            </div>
            <p>No leave types configured</p>
        </div>

        <!-- Cards grid -->
        <div v-else class="row g-4">
            <div
                v-for="(leave, index) in allowances"
                :key="leave.id"
                class="col-xl-3 col-lg-4 col-md-6 col-12"
            >
                <div class="la-card" :style="{ '--accent': color(index) }">

                    <!-- Top accent bar + header -->
                    <div class="la-card-header">
                        <div class="la-type-dot"></div>
                        <div class="la-card-info">
                            <span class="la-card-name">{{ leave.name }}</span>
                            <span class="la-type-badge" :class="`la-type-badge--${leave.type}`">
                                {{ leave.type === 'paid' ? 'Paid' : 'Unpaid' }}
                            </span>
                        </div>
                    </div>

                    <!-- Ring + availability -->
                    <div class="la-ring-section">
                        <div class="la-ring-wrap">
                            <svg viewBox="0 0 36 36" class="la-ring-svg">
                                <!-- Background track -->
                                <path
                                    class="la-ring-track"
                                    d="M18 2.0845 a15.9155 15.9155 0 0 1 0 31.831 a15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <!-- Progress arc -->
                                <path
                                    class="la-ring-arc"
                                    :stroke="color(index)"
                                    :stroke-dasharray="ringDash(leave.taken, leave.availability)"
                                    d="M18 2.0845 a15.9155 15.9155 0 0 1 0 31.831 a15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <!-- Center text -->
                                <text x="18" y="16" class="la-ring-num">{{ leave.taken }}</text>
                                <text x="18" y="22" class="la-ring-label">taken</text>
                            </svg>
                        </div>

                        <!-- Availability pill -->
                        <div class="la-avail-wrap">
                            <span
                                class="la-avail-num"
                                :style="{ color: availColor(leave.availability, leave.allowance) }"
                            >{{ leave.availability }}</span>
                            <span class="la-avail-label">days left</span>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="la-progress-wrap">
                        <div class="la-progress-track">
                            <div
                                class="la-progress-fill"
                                :style="{
                                    width: pct(leave.taken, leave.availability) + '%',
                                    background: color(index)
                                }"
                            ></div>
                        </div>
                        <span class="la-progress-pct">{{ pct(leave.taken, leave.availability) }}% used</span>
                    </div>

                    <!-- Stats row -->
                    <div class="la-stats">
                        <div class="la-stat">
                            <span class="la-stat-val">{{ leave.allowance }}</span>
                            <span class="la-stat-lbl">Allowance</span>
                        </div>
                        <div class="la-stat-divider"></div>
                        <div class="la-stat">
                            <span class="la-stat-val">{{ leave.earned }}</span>
                            <span class="la-stat-lbl">Earned</span>
                        </div>
                        <div class="la-stat-divider"></div>
                        <div class="la-stat">
                            <span class="la-stat-val la-stat-val--taken">{{ leave.taken }}</span>
                            <span class="la-stat-lbl">Taken</span>
                        </div>
                        <div class="la-stat-divider"></div>
                        <div class="la-stat">
                            <span
                                class="la-stat-val"
                                :style="{ color: availColor(leave.availability, leave.allowance) }"
                            >{{ leave.availability }}</span>
                            <span class="la-stat-lbl">Available</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
/* ── Header ───────────────────────────────────── */
.la-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 28px;
}
.la-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}
.la-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

/* ── Empty ────────────────────────────────────── */
.la-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 64px 20px;
    color: var(--secondary-font-color, #9ca3af);
    font-size: 14px;
}
.la-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: var(--dashboard-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--border-color);
}

/* ── Card ─────────────────────────────────────── */
.la-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: box-shadow 0.2s, transform 0.2s;
    position: relative;

    &::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--accent);
    }

    &:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.10);
        transform: translateY(-3px);
    }
}

/* ── Card header ──────────────────────────────── */
.la-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 18px 0;
}
.la-type-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--accent);
    flex-shrink: 0;
}
.la-card-info {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
}
.la-card-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--font-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}
.la-type-badge {
    display: inline-flex;
    align-items: center;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    white-space: nowrap;
    flex-shrink: 0;
    text-transform: uppercase;
    letter-spacing: 0.4px;

    &--paid {
        background: rgba(34,197,94,0.10);
        color: #15803d;
        border: 1px solid rgba(34,197,94,0.2);
    }
    &--unpaid {
        background: rgba(239,68,68,0.10);
        color: #dc2626;
        border: 1px solid rgba(239,68,68,0.2);
    }
}

/* ── Ring section ─────────────────────────────── */
.la-ring-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 18px 12px;
    gap: 12px;
}
.la-ring-wrap {
    flex-shrink: 0;
    width: 90px;
    height: 90px;
}
.la-ring-svg {
    width: 100%;
    height: 100%;
}
.la-ring-track {
    fill: none;
    stroke: var(--dashboard-bg, #f1f5f9);
    stroke-width: 3.5;
}
.la-ring-arc {
    fill: none;
    stroke-width: 3.5;
    stroke-linecap: round;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
    transition: stroke-dasharray 0.5s ease;
}
.la-ring-num {
    fill: var(--font-color, #1e293b);
    font-size: 7px;
    font-weight: 800;
    text-anchor: middle;
    dominant-baseline: middle;
    font-family: inherit;
}
.la-ring-label {
    fill: var(--secondary-font-color, #9ca3af);
    font-size: 4px;
    font-weight: 500;
    text-anchor: middle;
    font-family: inherit;
}

.la-avail-wrap {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}
.la-avail-num {
    font-size: 32px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -1px;
}
.la-avail-label {
    font-size: 11px;
    color: var(--secondary-font-color, #9ca3af);
    font-weight: 500;
}

/* ── Progress bar ─────────────────────────────── */
.la-progress-wrap {
    padding: 0 18px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.la-progress-track {
    flex: 1;
    height: 6px;
    background: var(--dashboard-bg, #f1f5f9);
    border-radius: 20px;
    overflow: hidden;
}
.la-progress-fill {
    height: 100%;
    border-radius: 20px;
    transition: width 0.5s ease;
    min-width: 2px;
}
.la-progress-pct {
    font-size: 11px;
    font-weight: 600;
    color: var(--secondary-font-color, #9ca3af);
    white-space: nowrap;
    flex-shrink: 0;
}

/* ── Stats row ────────────────────────────────── */
.la-stats {
    display: flex;
    align-items: stretch;
    border-top: 1px solid var(--border-color);
}
.la-stat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 6px;
    gap: 3px;
}
.la-stat-divider {
    width: 1px;
    background: var(--border-color);
    margin: 10px 0;
}
.la-stat-val {
    font-size: 16px;
    font-weight: 800;
    color: var(--font-color);
    line-height: 1;

    &--taken {
        color: #ef4444;
    }
}
.la-stat-lbl {
    font-size: 10px;
    font-weight: 500;
    color: var(--secondary-font-color, #9ca3af);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    line-height: 1;
}
</style>

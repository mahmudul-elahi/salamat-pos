<script setup>
defineOptions({ inheritAttrs: false });

import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES } from '@/services/endpoints/hrm.js';
import { useToast } from 'vue-toastification';
import { InfoCircle, CheckCircle, XmarkCircle, NavArrowLeft, NavArrowRight, Attachment, OpenNewWindow, Calendar } from '@iconoir/vue';

const route      = useRoute();
const toast      = useToast();
const employeeId = route.params.id;

/* ── Month navigation ────────────────────────── */
const now          = new Date();
const currentMonth = ref(now.getMonth() + 1);
const currentYear  = ref(now.getFullYear());

const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

const monthLabel = computed(() => `${MONTHS[currentMonth.value - 1]} ${currentYear.value}`);

const prevMonth = () => {
    if (currentMonth.value === 1) { currentMonth.value = 12; currentYear.value--; }
    else currentMonth.value--;
};
const nextMonth = () => {
    if (currentMonth.value === 12) { currentMonth.value = 1; currentYear.value++; }
    else currentMonth.value++;
};

/* ── Data ────────────────────────────────────── */
const loading  = ref(true);
const counts   = ref({ pending: 0, approved: 0, rejected: 0 });
const requests = ref([]);

const fetchData = async () => {
    loading.value = true;
    try {
        const { data } = await Axios.get(
            `${HRM_EMPLOYEES}/${employeeId}/leave-summary?month=${currentMonth.value}&year=${currentYear.value}`
        );
        counts.value   = data.counts;
        requests.value = data.requests;
    } catch {
        toast.error('Failed to load leave data.');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
watch([currentMonth, currentYear], fetchData);

/* ── Helpers ─────────────────────────────────── */
const fmtDate = (item) => {
    if (item.start_date && item.end_date) return `${item.start_date} – ${item.end_date}`;
    return item.date ?? '—';
};

const fmtDuration = (d) => {
    if (!d) return '—';
    const map = { full_day: 'Full Day', half_day: 'Half Day', hourly: 'Hourly' };
    return map[d] ?? d;
};

const STATUS_CFG = {
    pending:  { label: 'Pending',  cls: 'lv-badge lv-badge--pending'  },
    approved: { label: 'Approved', cls: 'lv-badge lv-badge--approved' },
    rejected: { label: 'Rejected', cls: 'lv-badge lv-badge--rejected' },
};
</script>

<template>
    <div>

        <!-- Page header -->
        <div class="lv-page-header">
            <div>
                <h6 class="lv-page-title">Leave</h6>
                <p class="lv-page-sub">Leave requests and status overview</p>
            </div>

            <!-- Month navigation -->
            <div class="lv-month-nav">
                <button class="lv-nav-btn" @click="prevMonth">
                    <NavArrowLeft />
                </button>
                <div class="lv-month-label">
                    <Calendar />
                    {{ monthLabel }}
                </div>
                <button class="lv-nav-btn" @click="nextMonth">
                    <NavArrowRight />
                </button>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-4">
                <div class="lv-stat-card lv-stat-card--pending">
                    <div class="lv-stat-icon lv-stat-icon--pending">
                        <InfoCircle />
                    </div>
                    <div class="lv-stat-body">
                        <span class="lv-stat-count">{{ counts.pending }}</span>
                        <span class="lv-stat-label">Pending requests</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="lv-stat-card lv-stat-card--approved">
                    <div class="lv-stat-icon lv-stat-icon--approved">
                        <CheckCircle />
                    </div>
                    <div class="lv-stat-body">
                        <span class="lv-stat-count">{{ counts.approved }}</span>
                        <span class="lv-stat-label">Approved leave</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="lv-stat-card lv-stat-card--rejected">
                    <div class="lv-stat-icon lv-stat-icon--rejected">
                        <XmarkCircle />
                    </div>
                    <div class="lv-stat-body">
                        <span class="lv-stat-count">{{ counts.rejected }}</span>
                        <span class="lv-stat-label">Rejected leave</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table card -->
        <div class="lv-table-card">

            <app-loader v-if="loading" />

            <template v-else>
                <!-- Table -->
                <div class="lv-table-wrap">
                    <table class="lv-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Leave Duration</th>
                                <th>Leave Type</th>
                                <th>Note</th>
                                <th>Attachment</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in requests" :key="row.id" class="lv-table-row">
                                <td class="lv-td-date">{{ fmtDate(row) }}</td>
                                <td>
                                    <span class="lv-duration-badge">{{ fmtDuration(row.leave_duration) }}</span>
                                </td>
                                <td class="lv-td-type">{{ row.leave_type ?? '—' }}</td>
                                <td class="lv-td-note">{{ row.note || '—' }}</td>
                                <td>
                                    <div v-if="row.attachments && row.attachments.length" class="lv-attachments">
                                        <a
                                            v-for="(url, i) in row.attachments"
                                            :key="i"
                                            :href="url"
                                            target="_blank"
                                            class="lv-attach-link"
                                        >
                                            <Attachment />
                                            File {{ i + 1 }}
                                            <OpenNewWindow />
                                        </a>
                                    </div>
                                    <span v-else class="lv-td-empty">—</span>
                                </td>
                                <td>
                                    <span :class="STATUS_CFG[row.status]?.cls ?? 'lv-badge lv-badge--pending'">
                                        {{ STATUS_CFG[row.status]?.label ?? row.status }}
                                    </span>
                                </td>
                            </tr>

                            <!-- Empty -->
                            <tr v-if="!requests.length">
                                <td colspan="6">
                                    <div class="lv-empty">
                                        <div class="lv-empty-icon-wrap">
                                            <Calendar class="lv-empty-icon" />
                                        </div>
                                        <p class="lv-empty-text">No leave requests for {{ monthLabel }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

    </div>
</template>

<style scoped lang="scss">
/* ── Page header ──────────────────────────────── */
.lv-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 28px;
    gap: 16px;
    flex-wrap: wrap;
}
.lv-page-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}
.lv-page-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

/* ── Month navigation ─────────────────────────── */
.lv-month-nav {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 6px 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.lv-nav-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--dashboard-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--font-color);
    transition: background 0.15s, border-color 0.15s;
    svg { width: 16px; height: 16px; }
    &:hover { background: var(--primary); border-color: var(--primary); color: #fff; }
}
.lv-month-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: var(--font-color);
    min-width: 140px;
    justify-content: center;
    svg { width: 15px; height: 15px; color: var(--primary); }
}

/* ── Stat cards ───────────────────────────────── */
.lv-stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: box-shadow 0.2s, transform 0.2s;
    border-left: 4px solid transparent;
    &:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); transform: translateY(-2px); }
    &--pending  { border-left-color: #f59e0b; }
    &--approved { border-left-color: #22c55e; }
    &--rejected { border-left-color: #ef4444; }
}

.lv-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    svg { width: 22px; height: 22px; }
    &--pending  { background: rgba(245,158,11,0.12); svg { color: #f59e0b; } }
    &--approved { background: rgba(34,197,94,0.12);  svg { color: #16a34a; } }
    &--rejected { background: rgba(239,68,68,0.12);  svg { color: #ef4444; } }
}

.lv-stat-body {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.lv-stat-count {
    font-size: 24px;
    font-weight: 800;
    color: var(--font-color);
    line-height: 1;
}
.lv-stat-label {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    font-weight: 500;
}

/* ── Table card ───────────────────────────────── */
.lv-table-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}

.lv-table-wrap {
    overflow-x: auto;
}

.lv-table {
    width: 100%;
    border-collapse: collapse;

    thead tr {
        background: var(--dashboard-bg);
        border-bottom: 1px solid var(--border-color);

        th {
            padding: 13px 18px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--secondary-font-color, #6b7280);
            text-align: left;
            white-space: nowrap;
        }
    }

    tbody tr {
        border-bottom: 1px solid var(--border-color);
        transition: background 0.15s;
        &:last-child { border-bottom: none; }
        &:hover { background: var(--dashboard-bg); }

        td {
            padding: 14px 18px;
            font-size: 13px;
            color: var(--font-color);
            vertical-align: middle;
        }
    }
}

.lv-td-date   { font-weight: 500; white-space: nowrap; }
.lv-td-type   { font-weight: 600; }
.lv-td-note   { color: var(--secondary-font-color, #6b7280); max-width: 200px; }
.lv-td-empty  { color: var(--border-color); }

/* ── Duration badge ───────────────────────────── */
.lv-duration-badge {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    background: rgba(99,102,241,0.08);
    color: #6366f1;
    border: 1px solid rgba(99,102,241,0.18);
    white-space: nowrap;
}

/* ── Status badges ────────────────────────────── */
.lv-badge {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    text-transform: capitalize;
    white-space: nowrap;
    &--pending  { background: rgba(245,158,11,0.10); color: #d97706; border: 1px solid rgba(245,158,11,0.25); }
    &--approved { background: rgba(34,197,94,0.10);  color: #16a34a; border: 1px solid rgba(34,197,94,0.25); }
    &--rejected { background: rgba(239,68,68,0.10);  color: #dc2626; border: 1px solid rgba(239,68,68,0.25); }
}

/* ── Attachments ──────────────────────────────── */
.lv-attachments {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.lv-attach-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 500;
    color: var(--primary);
    text-decoration: none;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(43,135,104,0.06);
    transition: background 0.15s;
    white-space: nowrap;
    svg { width: 12px; height: 12px; }
    &:hover { background: rgba(43,135,104,0.14); }
}

/* ── Empty state ──────────────────────────────── */
.lv-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 56px 20px;
    gap: 12px;
}
.lv-empty-icon-wrap {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: var(--dashboard-bg);
    display: flex;
    align-items: center;
    justify-content: center;
}
.lv-empty-icon {
    width: 30px;
    height: 30px;
    color: var(--border-color);
    stroke-width: 1.2;
}
.lv-empty-text {
    font-size: 14px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}
</style>

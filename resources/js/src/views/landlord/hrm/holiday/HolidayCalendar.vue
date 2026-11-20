<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import Axios from '@/services/axios/index.js';
import { HRM_HOLIDAYS_CALENDAR, HRM_HOLIDAYS } from '@/services/endpoints/hrm.js';
import { useI18n } from 'vue-i18n';
import HolidayModal from './HolidayModal.vue';

const { t } = useI18n();

const DEPT_COLORS = [
    '#4f46e5', '#0891b2', '#059669', '#d97706',
    '#dc2626', '#7c3aed', '#db2777', '#0284c7',
    '#16a34a', '#ea580c',
];

const rawHolidays = ref([]);
const loading = ref(false);
const isModalActive = ref(false);
const selectedUrl = ref(null);
const tableId = ref('holiday-cal-refresh');
const selectedEvent = ref(null);
const popoverVisible = ref(false);
const popoverEl = ref(null);
const calendarRef = ref(null);

// dept → color map
const deptColorMap = ref({});
const getDeptColor = (dept) => {
    if (!dept) return '#4f46e5';
    if (!deptColorMap.value[dept]) {
        const idx = Object.keys(deptColorMap.value).length % DEPT_COLORS.length;
        deptColorMap.value[dept] = DEPT_COLORS[idx];
    }
    return deptColorMap.value[dept];
};

const events = computed(() =>
    rawHolidays.value.map(h => ({
        ...h,
        backgroundColor: getDeptColor(h.department),
        borderColor: getDeptColor(h.department),
        textColor: '#fff',
    }))
);

const today = new Date();
const thisMonth = today.getMonth();
const thisYear = today.getFullYear();

const upcomingHolidays = computed(() =>
    rawHolidays.value
        .filter(h => new Date(h.start) >= today)
        .slice(0, 6)
);

const thisMonthCount = computed(() =>
    rawHolidays.value.filter(h => {
        const d = new Date(h.start);
        return d.getMonth() === thisMonth && d.getFullYear() === thisYear;
    }).length
);

const fetchEvents = async () => {
    loading.value = true;
    try {
        const { data } = await Axios.get(HRM_HOLIDAYS_CALENDAR);
        rawHolidays.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next',
        center: 'title',
        right: 'dayGridMonth,dayGridWeek',
    },
    events: events.value,
    editable: false,
    selectable: false,
    eventClick: (info) => {
        const rect = info.el.getBoundingClientRect();
        selectedEvent.value = {
            id: info.event.id,
            title: info.event.title,
            start: info.event.startStr,
            end: info.event.endStr,
            description: info.event.extendedProps.description,
            department: info.event.extendedProps.department,
            color: info.event.backgroundColor,
        };
        // position popover
        const scrollY = window.scrollY;
        const scrollX = window.scrollX;
        const top = rect.bottom + scrollY + 10;
        let left = rect.left + scrollX;
        if (left + 290 > window.innerWidth) left = window.innerWidth - 300;
        popoverStyle.value = { top: `${top}px`, left: `${left}px` };
        popoverVisible.value = true;
    },
    eventDidMount: (info) => {
        info.el.style.cursor = 'pointer';
        info.el.style.borderRadius = '5px';
        info.el.style.fontSize = '0.76rem';
        info.el.style.fontWeight = '500';
        info.el.style.padding = '2px 7px';
    },
    height: 'auto',
    contentHeight: 620,
    dayMaxEvents: 3,
    moreLinkText: (n) => `+${n} more`,
}));

const popoverStyle = ref({});

const closePopover = () => {
    popoverVisible.value = false;
    selectedEvent.value = null;
};

const openEdit = () => {
    selectedUrl.value = `${HRM_HOLIDAYS}/${selectedEvent.value.id}`;
    isModalActive.value = true;
    closePopover();
};

const openAdd = () => {
    selectedUrl.value = null;
    isModalActive.value = true;
    closePopover();
};

const closeModal = () => {
    isModalActive.value = false;
    selectedUrl.value = null;
    fetchEvents();
};

const onDocClick = (e) => {
    if (popoverVisible.value && popoverEl.value && !popoverEl.value.contains(e.target)) {
        closePopover();
    }
};

const formatDateShort = (str) => {
    if (!str) return '';
    const d = new Date(str);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const daysLeft = (str) => {
    const diff = Math.ceil((new Date(str) - today) / 86400000);
    if (diff === 0) return 'Today';
    if (diff === 1) return 'Tomorrow';
    return `${diff} days`;
};

onMounted(() => {
    fetchEvents();
    document.addEventListener('click', onDocClick);
});
onBeforeUnmount(() => document.removeEventListener('click', onDocClick));
</script>

<template>
    <div class="content-wrapper hc-page">

        <!-- Top bar -->
        <div class="hc-topbar">
            <div class="hc-topbar-left">
                <h5 class="hc-title">{{ $t('holiday_calendar') }}</h5>
                <p class="hc-subtitle">{{ $t('manage_and_track_holidays') }}</p>
            </div>
            <button class="hc-add-btn" @click="openAdd">
                <span class="hc-add-icon">＋</span> {{ $t('add_holiday') }}
            </button>
        </div>

        <!-- Stat cards -->
        <div class="hc-stats">
            <div class="hc-stat-card hc-stat-blue">
                <div class="hc-stat-val">{{ rawHolidays.length }}</div>
                <div class="hc-stat-label">{{ $t('total_holidays') }}</div>
            </div>
            <div class="hc-stat-card hc-stat-green">
                <div class="hc-stat-val">{{ thisMonthCount }}</div>
                <div class="hc-stat-label">{{ $t('this_month') }}</div>
            </div>
            <div class="hc-stat-card hc-stat-violet">
                <div class="hc-stat-val">{{ upcomingHolidays.length }}</div>
                <div class="hc-stat-label">{{ $t('upcoming') }}</div>
            </div>
            <div class="hc-stat-card hc-stat-orange">
                <div class="hc-stat-val">{{ Object.keys(deptColorMap).length }}</div>
                <div class="hc-stat-label">{{ $t('departments') }}</div>
            </div>
        </div>

        <!-- Main layout -->
        <div class="hc-layout">

            <!-- Calendar -->
            <div class="hc-calendar-wrap">
                <div v-if="loading" class="hc-loading">
                    <app-loader />
                </div>
                <FullCalendar v-else ref="calendarRef" :options="calendarOptions" />
            </div>

            <!-- Sidebar -->
            <aside class="hc-sidebar">
                <div class="hc-sidebar-header">
                    <span class="hc-sidebar-dot"></span>
                    {{ $t('upcoming_holidays') }}
                </div>

                <div v-if="upcomingHolidays.length === 0" class="hc-empty">
                    <div class="hc-empty-icon">📅</div>
                    <p>{{ $t('no_upcoming_holidays') }}</p>
                </div>

                <div v-else class="hc-upcoming-list">
                    <div v-for="h in upcomingHolidays" :key="h.id" class="hc-upcoming-item">
                        <div class="hc-upcoming-bar" :style="{ background: getDeptColor(h.department) }"></div>
                        <div class="hc-upcoming-body">
                            <div class="hc-upcoming-name">{{ h.title }}</div>
                            <div class="hc-upcoming-date">{{ formatDateShort(h.start) }}</div>
                            <div v-if="h.department" class="hc-upcoming-dept">
                                <span class="hc-dept-dot" :style="{ background: getDeptColor(h.department) }"></span>
                                {{ h.department }}
                            </div>
                        </div>
                        <span class="hc-days-badge"
                            :style="{ background: getDeptColor(h.department) + '22', color: getDeptColor(h.department) }">
                            {{ daysLeft(h.start) }}
                        </span>
                    </div>
                </div>

                <!-- Legend -->
                <div v-if="Object.keys(deptColorMap).length" class="hc-legend">
                    <div class="hc-sidebar-header" style="margin-bottom:10px;">
                        <span class="hc-sidebar-dot" style="background:#6c757d"></span>
                        {{ $t('departments') }}
                    </div>
                    <div v-for="(color, dept) in deptColorMap" :key="dept" class="hc-legend-item">
                        <span class="hc-legend-dot" :style="{ background: color }"></span>
                        <span>{{ dept }}</span>
                    </div>
                    <div class="hc-legend-item">
                        <span class="hc-legend-dot" style="background:#4f46e5"></span>
                        <span>{{ $t('all_departments') }}</span>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Event Popover -->
        <transition name="pop">
            <div v-if="popoverVisible && selectedEvent" ref="popoverEl" class="hc-popover" :style="popoverStyle">
                <div class="hc-pop-header" :style="{ background: selectedEvent.color }">
                    <span class="hc-pop-title">{{ selectedEvent.title }}</span>
                    <button class="hc-pop-close" @click.stop="closePopover">✕</button>
                </div>
                <div class="hc-pop-body">
                    <div class="hc-pop-row">
                        <span class="hc-pop-label">{{ $t('start_date') }}</span>
                        <span>{{ formatDateShort(selectedEvent.start) }}</span>
                    </div>
                    <div class="hc-pop-row">
                        <span class="hc-pop-label">{{ $t('end_date') }}</span>
                        <span>{{ formatDateShort(selectedEvent.end) }}</span>
                    </div>
                    <div v-if="selectedEvent.department" class="hc-pop-row">
                        <span class="hc-pop-label">{{ $t('department') }}</span>
                        <span class="hc-pop-dept">
                            <span class="hc-dept-dot" :style="{ background: selectedEvent.color }"></span>
                            {{ selectedEvent.department }}
                        </span>
                    </div>
                    <div v-if="selectedEvent.description" class="hc-pop-row hc-pop-desc">
                        <span class="hc-pop-label">{{ $t('description') }}</span>
                        <span>{{ selectedEvent.description }}</span>
                    </div>
                </div>
                <div class="hc-pop-footer">
                    <button class="hc-pop-edit-btn" :style="{ background: selectedEvent.color }" @click.stop="openEdit">
                        {{ $t('edit') }}
                    </button>
                </div>
            </div>
        </transition>

        <holiday-modal v-if="isModalActive" :table-id="tableId" modal-id="holiday-cal-modal" :selected-url="selectedUrl"
            @close="closeModal" />
    </div>
</template>

<style scoped lang="scss">
/* ── Page ── */
.hc-page {
    min-height: 100vh;
}

/* ── Top bar ── */
.hc-topbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 20px;
}

.hc-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}

.hc-subtitle {
    font-size: 0.82rem;
    color: #8a94a6;
    margin: 2px 0 0;
}

.hc-add-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #4f46e5;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 9px 18px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s, transform 0.12s;
    white-space: nowrap;

    &:hover {
        background: #4338ca;
        transform: translateY(-1px);
    }

    &:active {
        transform: translateY(0);
    }
}

.hc-add-icon {
    font-size: 1rem;
    line-height: 1;
}

/* ── Stats ── */
.hc-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 20px;

    @media (max-width: 768px) {
        grid-template-columns: repeat(2, 1fr);
    }
}

.hc-stat-card {
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.hc-stat-val {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
}

.hc-stat-label {
    font-size: 0.78rem;
    font-weight: 500;
    opacity: 0.75;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.hc-stat-blue {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #fff;
}

.hc-stat-green {
    background: linear-gradient(135deg, #059669, #34d399);
    color: #fff;
}

.hc-stat-violet {
    background: linear-gradient(135deg, #7c3aed, #a78bfa);
    color: #fff;
}

.hc-stat-orange {
    background: linear-gradient(135deg, #d97706, #fbbf24);
    color: #fff;
}

/* ── Layout ── */
.hc-layout {
    display: grid;
    grid-template-columns: 1fr 290px;
    gap: 18px;
    align-items: start;

    @media (max-width: 1024px) {
        grid-template-columns: 1fr;
    }
}

/* ── Calendar ── */
.hc-calendar-wrap {
    background: #fff;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.07);
    min-height: 500px;
    position: relative;
}

.hc-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 400px;
}

/* ── Sidebar ── */
.hc-sidebar {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.hc-sidebar-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #6c757d;
}

.hc-sidebar-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #4f46e5;
    flex-shrink: 0;
}

.hc-empty {
    text-align: center;
    padding: 30px 20px;
    background: #fff;
    border-radius: 14px;
    color: #8a94a6;
    font-size: 0.83rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
}

.hc-empty-icon {
    font-size: 2rem;
    margin-bottom: 8px;
}

/* ── Upcoming list ── */
.hc-upcoming-list {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.hc-upcoming-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-bottom: 1px solid #f0f2f5;
    transition: background 0.15s;

    &:last-child {
        border-bottom: none;
    }

    &:hover {
        background: #f8f9fb;
    }
}

.hc-upcoming-bar {
    width: 4px;
    height: 40px;
    border-radius: 4px;
    flex-shrink: 0;
}

.hc-upcoming-body {
    flex: 1;
    min-width: 0;
}

.hc-upcoming-name {
    font-size: 0.84rem;
    font-weight: 600;
    color: #1a202c;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.hc-upcoming-date {
    font-size: 0.75rem;
    color: #8a94a6;
    margin-top: 1px;
}

.hc-upcoming-dept {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.73rem;
    color: #8a94a6;
    margin-top: 2px;
}

.hc-dept-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.hc-days-badge {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 20px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* ── Legend ── */
.hc-legend {
    background: #fff;
    border-radius: 14px;
    padding: 14px 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.hc-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #3d4a5c;
    padding: 4px 0;
}

.hc-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
}

/* ── Popover ── */
.hc-popover {
    position: absolute;
    z-index: 1200;
    width: 285px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.16);
    overflow: hidden;
    border: 1px solid #f0f2f5;
}

.hc-pop-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    color: #fff;
}

.hc-pop-title {
    font-weight: 700;
    font-size: 0.88rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hc-pop-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    font-size: 11px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.15s;

    &:hover {
        background: rgba(255, 255, 255, 0.35);
    }
}

.hc-pop-body {
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.hc-pop-row {
    display: flex;
    gap: 8px;
    font-size: 0.81rem;
    align-items: flex-start;
}

.hc-pop-label {
    font-weight: 600;
    color: #8a94a6;
    min-width: 78px;
    flex-shrink: 0;
}

.hc-pop-dept {
    display: flex;
    align-items: center;
    gap: 5px;
}

.hc-pop-desc {
    align-items: flex-start;

    span:last-child {
        color: #6c757d;
        line-height: 1.5;
    }
}

.hc-pop-footer {
    padding: 10px 16px 14px;
}

.hc-pop-edit-btn {
    width: 100%;
    border: none;
    color: #fff;
    border-radius: 8px;
    padding: 7px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s;

    &:hover {
        opacity: 0.88;
    }
}

/* ── Transition ── */
.pop-enter-active,
.pop-leave-active {
    transition: opacity 0.18s, transform 0.18s;
}

.pop-enter-from,
.pop-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.97);
}

/* ── FullCalendar deep overrides ── */
:deep(.fc) {
    font-family: inherit;
}

:deep(.fc-toolbar) {
    margin-bottom: 16px !important;
}

:deep(.fc-toolbar-title) {
    font-size: 1rem !important;
    font-weight: 700 !important;
    color: #1a202c;
}

:deep(.fc-button-group) {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

:deep(.fc-button) {
    background: #fff !important;
    border: 1px solid #e2e8f0 !important;
    color: #4f46e5 !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    padding: 6px 14px !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    transition: background 0.15s, color 0.15s !important;
}

:deep(.fc-button:hover) {
    background: #f0f0ff !important;
    color: #4338ca !important;
}

:deep(.fc-button-active),
:deep(.fc-button:not(:disabled):active) {
    background: #4f46e5 !important;
    color: #fff !important;
    border-color: #4f46e5 !important;
}

:deep(.fc-prev-button),
:deep(.fc-next-button) {
    border-radius: 8px !important;
    width: 32px;
    padding: 0 !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

:deep(.fc-today-button) {
    border-radius: 8px !important;
}

:deep(.fc-col-header-cell-cushion) {
    font-size: 0.75rem !important;
    font-weight: 700 !important;
    color: #8a94a6;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 8px 0 !important;
}

:deep(.fc-daygrid-day-number) {
    font-size: 0.82rem;
    color: #3d4a5c;
    padding: 6px 8px !important;
    font-weight: 500;
}

:deep(.fc-day-today) {
    background: #f0f0ff !important;
}

:deep(.fc-day-today .fc-daygrid-day-number) {
    background: #4f46e5;
    color: #fff;
    border-radius: 50%;
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 4px;
}

:deep(.fc-daygrid-event) {
    border-radius: 5px !important;
    margin: 1px 3px !important;
}

:deep(.fc-more-link) {
    font-size: 0.72rem;
    font-weight: 600;
    color: #4f46e5;
    padding: 1px 4px;
}

:deep(.fc-scrollgrid) {
    border-radius: 10px;
    overflow: hidden;
    border-color: #f0f2f5 !important;
}

:deep(.fc-scrollgrid td),
:deep(.fc-scrollgrid th) {
    border-color: #f0f2f5 !important;
}

/* ── Dark mode ── */
:global([theme="dark"]) {
    .hc-title {
        color: #e2e8f0;
    }

    .hc-calendar-wrap,
    .hc-upcoming-list,
    .hc-legend,
    .hc-empty {
        background: #1e2330;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
    }

    .hc-popover {
        background: #252a3a;
        border-color: #2e3650;
    }

    .hc-upcoming-item:hover {
        background: #252a3a;
    }

    .hc-upcoming-item {
        border-color: #2e3650;
    }

    .hc-upcoming-name {
        color: #e2e8f0;
    }

    .hc-pop-body {
        color: #c0c8d8;
    }

    .hc-legend-item {
        color: #c0c8d8;
    }
}

:global([theme="dark"]) :deep(.fc-toolbar-title),
:global([theme="dark"]) :deep(.fc-daygrid-day-number) {
    color: #c0c8d8;
}

:global([theme="dark"]) :deep(.fc-button) {
    background: #252a3a !important;
    border-color: #2e3650 !important;
    color: #a78bfa !important;
}

:global([theme="dark"]) :deep(.fc-button:hover) {
    background: #2e3650 !important;
}

:global([theme="dark"]) :deep(.fc-button-active) {
    background: #4f46e5 !important;
    color: #fff !important;
}

:global([theme="dark"]) :deep(.fc-day-today) {
    background: rgba(79, 70, 229, 0.12) !important;
}

:global([theme="dark"]) :deep(.fc-scrollgrid td),
:global([theme="dark"]) :deep(.fc-scrollgrid th) {
    border-color: #2e3650 !important;
}
</style>

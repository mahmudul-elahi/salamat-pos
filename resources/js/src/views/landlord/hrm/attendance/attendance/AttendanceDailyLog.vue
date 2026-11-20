<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('daily_attendance_log')" />

        <app-datatable :id="tableId" :options="options" />

        <location-view-modal v-if="isLocationModalActive" modal-id="daily-log-location-modal"
            :title="locationModalTitle" :location="locationModalData"
            @close="isLocationModalActive = false" />

        <div v-if="enlargedSelfie" class="selfie-lightbox" @click="enlargedSelfie = null">
            <img :src="enlargedSelfie" alt="Selfie" />
        </div>
    </div>
</template>

<script setup>
import { ref, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { format } from "date-fns";
import Axios from "@/services/axios/index.js";
import { HRM_ATTENDANCES } from "@/services/endpoints/hrm.js";
import LocationViewModal from "./LocationViewModal.vue";

const { t } = useI18n();
const tableId = ref('hrm-attendance-daily-log-table');
const today   = format(new Date(), 'yyyy-MM-dd');

/* ── Location modal ───────────────────────────────────── */
const isLocationModalActive = ref(false);
const locationModalData     = ref(null);
const locationModalTitle    = ref('');

const openLocationModal = (location, title) => {
    locationModalData.value     = location;
    locationModalTitle.value    = title;
    isLocationModalActive.value = true;
};

// Bridge for innerHTML onclick handlers inside the datatable
window.__viewDailyLogLocation = (locStr, title) => {
    try { openLocationModal(JSON.parse(decodeURIComponent(locStr)), title); } catch {}
};
onUnmounted(() => { delete window.__viewDailyLogLocation; });

/* ── Selfie lightbox ───────────────────────────────────── */
const enlargedSelfie = ref(null);

window.__viewDailyLogSelfie = (urlStr) => {
    try { enlargedSelfie.value = decodeURIComponent(urlStr); } catch {}
};
onUnmounted(() => { delete window.__viewDailyLogSelfie; });

/* ── Cell renderers ───────────────────────────────────── */
const behaviorBadgeClass = {
    early:   'badge rounded-pill border border-info text-info bg-info-subtle px-3',
    late:    'badge rounded-pill border border-danger text-danger bg-danger-subtle px-3',
    regular: 'badge rounded-pill border border-success text-success bg-success-subtle px-3',
};

const initials = (name) => {
    const parts = (name || '').trim().split(' ').filter(Boolean);
    const first = parts[0] ? parts[0][0] : '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return `${first}${last}`.toUpperCase();
};

const avatarStyle = 'width:36px;height:36px;border-radius:50%;background:#ede9fe;color:#4338ca;'
    + 'display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;flex-shrink:0;';
const imgStyle = 'width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #e2e8f0;';

const profileCell = (name, row) => {
    const avatar = row.profile_picture
        ? `<img src="${row.profile_picture}" alt="${name ?? ''}" style="${imgStyle}">`
        : `<div style="${avatarStyle}">${initials(name)}</div>`;
    return `<div class="d-flex align-items-center gap-2">${avatar}<div>
        <div class="fw-semibold">${name ?? '-'}</div>
        <div class="text-muted small">ID: ${row.employee_code ?? '-'}</div>
    </div></div>`;
};

const makeLocationCell = (ip, loc, title) => {
    if (!ip && !loc) return '<span class="text-muted">—</span>';
    const enc = encodeURIComponent(JSON.stringify(loc ?? { ip }));
    return `<button class="btn btn-link btn-sm p-0 text-start" style="color:inherit"
        onclick="window.__viewDailyLogLocation('${enc}', '${title}')">
        📍 <span style="text-decoration:underline dotted">${ip ?? 'View'}</span>
    </button>`;
};

const inLocationCell  = (ip, row) => makeLocationCell(ip, row?.in_location,  'Punch In Location');
const outLocationCell = (ip, row) => makeLocationCell(ip, row?.out_location, 'Punch Out Location');

const selfieCell = (url) => url
    ? `<img src="${url}" alt="Selfie" style="${imgStyle}cursor:pointer;"
        onclick="window.__viewDailyLogSelfie('${encodeURIComponent(url)}')">`
    : '<span class="text-muted">—</span>';

/* ── Datatable options ────────────────────────────────── */
const options = ref({
    url: `${HRM_ATTENDANCES}?date=${today}`,
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
        { title: t('profile'),      key: 'employee',  type: 'custom-html', modifier: profileCell },
        { title: t('punch_in'),     key: 'in_time',   type: 'text' },
        { title: t('in_selfie'),    key: 'in_selfie',  type: 'custom-html', modifier: selfieCell },
        { title: t('in_location'),  key: 'in_ip',     type: 'custom-html', modifier: inLocationCell },
        { title: t('out_location'), key: 'out_ip',    type: 'custom-html', modifier: outLocationCell },
        { title: t('punch_out'),    key: 'out_time',  type: 'text' },
        { title: t('out_selfie'),   key: 'out_selfie', type: 'custom-html', modifier: selfieCell },
        {
            title: t('behavior'), key: 'behavior', type: 'custom-class',
            modifier: (b) => behaviorBadgeClass[b] ?? 'badge rounded-pill border bg-light text-dark px-3',
        },
        { title: t('break_times'), key: 'break_time',  type: 'text' },
        { title: t('total_hours'), key: 'total_hours', type: 'text' },
    ],
    actions: [
        {
            title: 'View In Location',
            type: 'view_in_location',
            modifier: (row) => !!(row.in_location?.ip || row.in_location?.latitude || row.in_location?.address),
        },
        {
            title: 'View Out Location',
            type: 'view_out_location',
            modifier: (row) => !!(row.out_location?.ip || row.out_location?.latitude || row.out_location?.address),
        },
    ],
});
</script>

<style scoped lang="scss">
.location-map {
    img {
        display: block;
        width: 100%;
        height: 280px;
        object-fit: cover;
    }
}

.location-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.location-row {
    display: flex;
    gap: 16px;
    font-size: 0.9rem;
}

.location-label {
    width: 110px;
    flex-shrink: 0;
    color: var(--secondary-font-color, #6b7280);
    font-weight: 500;
}

.location-value {
    color: var(--font-color);
    font-weight: 600;
    word-break: break-all;
}

.selfie-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    cursor: zoom-out;

    img {
        max-width: 90vw;
        max-height: 90vh;
        border-radius: 8px;
    }
}
</style>

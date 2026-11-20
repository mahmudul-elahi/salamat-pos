<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('attendances')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_attendance')"
            :button-visible="canAccess('create_attendances')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <attendance-modal v-if="isModalActive" :table-id="tableId" modal-id="attendance-modal"
            :selected-url="selectedData" @close="closeModal" />

        <attendance-punch-details-modal v-if="isDetailsModalActive" modal-id="attendance-punch-details-modal"
            :attendance-id="detailsAttendanceId" @close="isDetailsModalActive = false" />

        <location-view-modal v-if="isLocationModalActive" modal-id="attendance-location-modal"
            :title="locationModalTitle" :location="locationModalData"
            @close="isLocationModalActive = false" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<script setup>
import { ref, markRaw, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { Edit, Trash, CheckCircle, XmarkCircle, Prohibition } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import Axios from "@/services/axios";
import { HRM_ATTENDANCES, HRM_EMPLOYEES } from "@/services/endpoints/hrm.js";
import AttendanceModal from "./AttendanceModal.vue";
import AttendancePunchDetailsModal from "./AttendancePunchDetailsModal.vue";
import LocationViewModal from "./LocationViewModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const toast = useToast();
const emitter = useEmitter();
const tableId = ref('hrm-attendance-table');

/* ── Location modal ───────────────────────────────── */
const isLocationModalActive = ref(false);
const locationModalData     = ref(null);
const locationModalTitle    = ref('');

const openLocationModal = (location, title) => {
    locationModalData.value     = location;
    locationModalTitle.value    = title;
    isLocationModalActive.value = true;
};

// Bridge for innerHTML onclick handlers inside the datatable
window.__viewAttendanceLocation = (locStr, title) => {
    try { openLocationModal(JSON.parse(decodeURIComponent(locStr)), title); } catch {}
};
onUnmounted(() => { delete window.__viewAttendanceLocation; });

const statusBadgeClass = {
    approved: 'badge bg-success',
    rejected: 'badge bg-danger',
    cancel: 'badge bg-secondary',
    pending: 'badge bg-warning',
};

const behaviorBadgeClass = {
    early: 'badge rounded-pill border border-info text-info bg-info-subtle px-3',
    late: 'badge rounded-pill border border-danger text-danger bg-danger-subtle px-3',
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
    return `
        <div class="d-flex align-items-center gap-2">
            ${avatar}
            <div>
                <div class="fw-semibold">${name ?? '-'}</div>
                <div class="text-muted small">ID: ${row.employee_code ?? '-'}</div>
            </div>
        </div>
    `;
};

const makeLocationCell = (ip, loc, title) => {
    if (!ip && !loc) return '<span class="text-muted">—</span>';
    const enc = encodeURIComponent(JSON.stringify(loc ?? { ip }));
    return `<button class="btn btn-link btn-sm p-0 text-start" style="color:inherit"
        onclick="window.__viewAttendanceLocation('${enc}', '${title}')">
        📍 <span style="text-decoration:underline dotted">${ip ?? 'View'}</span>
    </button>`;
};

const inLocationCell  = (ip, row) => makeLocationCell(ip, row?.in_location,  'Punch In Location');
const outLocationCell = (ip, row) => makeLocationCell(ip, row?.out_location, 'Punch Out Location');

const options = ref({
    url: HRM_ATTENDANCES,
    pageSize: 10,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: true,
    filters: [
        {
            label: t('employee'),
            type: 'advance-search-select',
            key: 'user_id',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: HRM_EMPLOYEES,
            permission: true,
        },
        {
            label: t('date'),
            type: 'date-range',
            key: 'date',
            placeholder: t('select_a_range'),
            permission: true,
        },
    ],
    columns: [
        { title: t('profile'), key: 'employee', type: 'custom-html', modifier: profileCell },
        { title: t('in_date'), key: 'in_date', type: 'text' },
        { title: t('leave'), key: 'leave', type: 'custom-html', modifier: () => '-' },
        {
            title: t('punch_records'), key: 'records_count', type: 'button',
            label: `👁 ${t('view_details')}`, className: 'btn btn-light btn-sm rounded-pill border',
            modifier: () => '',
        },
        { title: t('punch_in'), key: 'in_time', type: 'text' },
        { title: t('in_location'),  key: 'in_ip',  type: 'custom-html', modifier: inLocationCell },
        { title: t('out_location'), key: 'out_ip', type: 'custom-html', modifier: outLocationCell },
        { title: t('punch_out'), key: 'out_time', type: 'text' },
        {
            title: t('behavior'), key: 'behavior', type: 'custom-class',
            modifier: (behavior) => behaviorBadgeClass[behavior] ?? 'badge rounded-pill border bg-light text-dark px-3',
        },
        { title: t('break_times'), key: 'break_time', type: 'text' },
        { title: t('total_hours'), key: 'total_hours', type: 'text' },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_attendances') || canAccess('delete_attendances') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_attendances') },
        { title: t('approve'), type: 'approved', icon: markRaw(CheckCircle), modifier: (row) => canAccess('update_attendances') && row.status !== 'approved' },
        { title: t('reject'), type: 'rejected', icon: markRaw(XmarkCircle), modifier: (row) => canAccess('update_attendances') && row.status !== 'rejected' },
        { title: t('cancel'), type: 'cancel', icon: markRaw(Prohibition), modifier: (row) => canAccess('update_attendances') && row.status !== 'cancel' },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_attendances') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const isDetailsModalActive = ref(false);
const detailsAttendanceId = ref(null);

const updateStatus = (row, status) => {
    Axios.patch(`${HRM_ATTENDANCES}/${row.id}`, { status })
        .then((response) => {
            toast.success(response.data.message);
            emitter.emit("reload-" + tableId.value);
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message);
        });
};

const actionTiger = (row, action) => {
    if (action.type === 'button') {
        detailsAttendanceId.value = row.id;
        isDetailsModalActive.value = true;
    } else if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_ATTENDANCES}/${row.id}`;
    } else if (['approved', 'rejected', 'cancel'].includes(action.type)) {
        updateStatus(row, action.type);
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_ATTENDANCES}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

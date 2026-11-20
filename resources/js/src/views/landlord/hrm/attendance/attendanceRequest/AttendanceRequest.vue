<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('attendance_requests')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_attendance_request')"
            :button-visible="canAccess('create_attendance_requests')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <attendance-request-modal v-if="isModalActive" :table-id="tableId" modal-id="attendance-request-modal"
            :selected-url="selectedData" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<script setup>
import { ref, h, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { Edit, Trash, CheckCircle, XmarkCircle } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { HRM_ATTENDANCE_REQUESTS } from "@/services/endpoints/hrm.js";
import { SELECTED_EMPLOYEES } from "@/services/endpoints/landlord.js";
import AttendanceRequestModal from "./AttendanceRequestModal.vue";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import Axios from "@/services/axios";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import { useTimezone } from "@/utils/useTimezone.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const { formatDateOnly } = useTimezone();

const to12h = (time) => {
    if (!time) return '-';
    const [h, m] = time.split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    const hour = h % 12 || 12;
    return `${String(hour).padStart(2, '0')}:${String(m).padStart(2, '0')} ${period}`;
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

const profileCell = (user) => {
    const name = user?.name;
    const avatar = user?.profile_picture
        ? `<img src="${user.profile_picture}" alt="${name ?? ''}" style="${imgStyle}">`
        : `<div style="${avatarStyle}">${initials(name)}</div>`;
    return `
        <div class="d-flex align-items-center gap-2">
            ${avatar}
            <div>
                <div class="fw-semibold">${name ?? '-'}</div>
                <div class="text-muted small">${user?.department ?? '-'}</div>
            </div>
        </div>
    `;
};

const tableId = ref('attendance-request-table');

const options = ref({
    url: HRM_ATTENDANCE_REQUESTS,
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
    columns: [
        {
            title: t('employee'), key: 'user', type: 'custom-html',
            modifier: profileCell,
        },
        {
            title: t('date'), key: 'date', type: 'object',
            modifier: (date) => formatDateOnly(date),
        },
        {
            title: t('punch_in'), key: 'in_time', type: 'object',
            modifier: (time) => to12h(time),
        },
        {
            title: t('punch_out'), key: 'out_time', type: 'object',
            modifier: (time) => to12h(time),
        },
        {
            title: t('note'), key: 'note', type: 'component',
            componentName: h(NoteColumn),
        },
        {
            title: t('status'), key: 'status', type: 'custom-html',
            modifier: (value) => {
                const map = {
                    pending: '<span class="badge bg-warning text-dark">Pending</span>',
                    approved: '<span class="badge bg-success">Approved</span>',
                    rejected: '<span class="badge bg-danger">Rejected</span>',
                };
                return map[value] ?? value;
            },
        },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_attendance_requests') || canAccess('delete_attendance_requests') },
    ],
    filters: [
        {
            label: t('employee'),
            type: 'advance-search-select',
            key: 'employee',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: SELECTED_EMPLOYEES,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('approve'), type: 'approve', icon: markRaw(CheckCircle),
            modifier: (row) => canAccess('update_attendance_requests_status') && row.status !== 'approved',
        },
        {
            title: t('reject'), type: 'reject', icon: markRaw(XmarkCircle),
            modifier: (row) => canAccess('update_attendance_requests_status') && row.status !== 'rejected',
        },
        {
            title: t('edit'), type: 'edit', icon: markRaw(Edit),
            modifier: (row) => canAccess('update_attendance_requests') && row.status === 'pending',
        },
        {
            title: t('delete'), type: 'delete', icon: markRaw(Trash),
            modifier: (row) => canAccess('delete_attendance_requests') && row.status === 'pending',
        },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();
const toast = useToast();
const emitter = useEmitter();

const updateStatus = (row, status) => {
    Axios.patch(`${HRM_ATTENDANCE_REQUESTS}/${row.id}/status`, { status })
        .then(() => {
            toast.success(`Attendance request ${status} successfully`);
            emitter.emit(`reload-${tableId.value}`);
        })
        .catch(({ response }) => toast.error(response?.data?.message || 'Failed to update status'));
};

const actionTiger = (row, action) => {
    if (action.type === 'approve') {
        updateStatus(row, 'approved');
    } else if (action.type === 'reject') {
        updateStatus(row, 'rejected');
    } else if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_ATTENDANCE_REQUESTS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_ATTENDANCE_REQUESTS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

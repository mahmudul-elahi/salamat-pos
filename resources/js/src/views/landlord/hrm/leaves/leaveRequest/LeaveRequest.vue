<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('leave_request')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_leave_request')"
            :button-visible="canAccess('create_leave_requests')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <leave-request-modal v-if="isModalActive" :table-id="tableId" modal-id="leave-request-modal"
            :selected-url="selectedData" @close="closeModal" />

        <leave-request-view-modal v-if="isViewModalActive" modal-id="leave-request-view-modal" :selected-url="viewUrl"
            @close="isViewModalActive = false" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { Edit, Trash, Eye, CheckCircle, XmarkCircle } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { HRM_LEAVE_REQUESTS } from "@/services/endpoints/hrm.js";
import LeaveRequestModal from "./LeaveRequestModal.vue";
import LeaveRequestViewModal from "./LeaveRequestViewModal.vue";
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

const formatPeriod = (row) => {
    if (row.leave_duration === 'hours') {
        const date = formatDateOnly(row.date);
        const from = to12h(row.start_time);
        const to   = to12h(row.end_time);
        return `<span>${date}</span><br><small class="text-muted">${from} &ndash; ${to}</small>`;
    }
    if (row.leave_duration === 'multi_day') {
        return `${formatDateOnly(row.start_date)} &ndash; ${formatDateOnly(row.end_date)}`;
    }
    return formatDateOnly(row.date);
};

const tableId = ref('leave-request-table');

const options = ref({
    url: HRM_LEAVE_REQUESTS,
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
        {
            title: t('leave_type'), key: 'leave_type', type: 'custom-html',
            modifier: (value) => value ? `<span>${value.name}</span>` : '-',
        },
        {
            title: t('leave_duration'), key: 'leave_duration', type: 'custom-html',
            modifier: (value) => {
                const map = {
                    single_day: '<span class="badge bg-primary">Single Day</span>',
                    multi_day: '<span class="badge bg-info text-dark">Multi Day</span>',
                    hours: '<span class="badge bg-secondary">Hours</span>',
                };
                return map[value] ?? value;
            },
        },
        {
            title: t('employee'), key: 'user', type: 'custom-html',
            modifier: (user) => user?.name ?? '-',
        },
        {
            title: t('period'), key: 'leave_duration', type: 'custom-html',
            modifier: (_, row) => formatPeriod(row),
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
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_leave_requests') || canAccess('delete_leave_requests') },
    ],
    actions: [
        { title: t('view'), type: 'view', icon: markRaw(Eye) },
        {
            title: t('approve'), type: 'approve', icon: markRaw(CheckCircle),
            modifier: (row) => canAccess('update_leave_requests') && row.status !== 'approved',
        },
        {
            title: t('reject'), type: 'reject', icon: markRaw(XmarkCircle),
            modifier: (row) => canAccess('update_leave_requests') && row.status !== 'rejected',
        },
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_leave_requests') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_leave_requests') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();
const toast = useToast();
const emitter = useEmitter();

const isViewModalActive = ref(false);
const viewUrl = ref(null);

const updateStatus = (row, status) => {
    Axios.patch(`${HRM_LEAVE_REQUESTS}/${row.id}`, { status })
        .then(() => {
            toast.success(`Leave request ${status} successfully`);
            emitter.emit(`reload-${tableId.value}`);
        })
        .catch(() => toast.error('Failed to update status'));
};

const actionTiger = (row, action) => {
    if (action.type === 'view') {
        isViewModalActive.value = true;
        viewUrl.value = `${HRM_LEAVE_REQUESTS}/${row.id}`;
    } else if (action.type === 'approve') {
        updateStatus(row, 'approved');
    } else if (action.type === 'reject') {
        updateStatus(row, 'rejected');
    } else if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_LEAVE_REQUESTS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_LEAVE_REQUESTS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

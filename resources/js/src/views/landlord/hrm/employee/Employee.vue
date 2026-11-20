<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('employee')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_employee')"
            :button-visible="canAccess('create_employees')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <employee-modal v-if="isModalActive" :table-id="tableId" modal-id="employee-modal" :selected-url="selectedData"
            @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

        <confirmation-modal
            v-if="resendConfirmModal"
            modal-id="resend-email-confirm"
            :icon="InfoCircle"
            title="Resend Email"
            :message="`A new password will be generated and sent to <strong>${resendRow?.email}</strong>. Are you sure?`"
            :preloader="resendPreloader"
            first-button-name="Send"
            second-button-name="Cancel"
            @cancel="resendConfirmModal = false"
            @confirm="confirmResendEmail" />
    </div>
</template>

<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { Edit, Trash, Eye, CheckCircle, Prohibition, Mail, InfoCircle } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import Axios from "@/services/axios";
import { HRM_EMPLOYEES } from "@/services/endpoints/hrm.js";
import EmployeeModal from "./EmployeeModal.vue";
import ConfirmationModal from "@/views/global/modal/ConfirmationModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const router = useRouter();
const toast = useToast();
const emitter = useEmitter();
const tableId = ref('hrm-employee-table');

const options = ref({
    url: HRM_EMPLOYEES,
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
            title: t('name'), key: 'name', type: 'custom-html', width: '260px',
            modifier: (name, row) => {
                const initials = (name ?? '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
                const avatar = row.profile_picture
                    ? `<img src="${row.profile_picture}" alt="${name}" class="emp-avatar-img">`
                    : `<span class="emp-avatar-initials">${initials}</span>`;
                const role = row.role
                    ? `<span class="badge bg-primary-subtle text-primary me-1">${row.role}</span>`
                    : '';
                const meta = role
                    ? `<div class="emp-name-meta">${role}</div>`
                    : '';
                return `<div class="emp-name-cell">${avatar}<div style="min-width:0"><div class="emp-name-label">${name ?? ''}</div>${meta}</div></div>`;
            },
        },
        { title: t('email'), key: 'email', type: 'text' },
        { title: t('designation'), key: 'designation', type: 'text' },
        { title: t('department'), key: 'department', type: 'text' },
        { title: t('employee_status'), key: 'employee_status', type: 'text' },
        { title: t('joining_date'), key: 'joining_date', type: 'text' },
        { title: t('salary'), key: 'salary', type: 'text' },
        {
            title: t('status'),
            key: 'status',
            type: 'custom-html',
            modifier: (status) => {
                const cls = status === 'active' ? 'badge bg-success' : 'badge bg-danger';
                const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : '';
                return `<span class="${cls}">${label}</span>`;
            },
        },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_employees') || canAccess('delete_employees') },
    ],
    actions: [
        { title: t('details'), type: 'view', icon: markRaw(Eye), modifier: () => canAccess('view_employees') },
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_employees') },
        { title: t('activate'), type: 'activate', icon: markRaw(CheckCircle), modifier: (row) => canAccess('update_employees') && row.status === 'inactive' },
        { title: t('deactivate'), type: 'deactivate', icon: markRaw(Prohibition), modifier: (row) => canAccess('update_employees') && row.status === 'active' },
        { title: 'Resend Email', type: 'resend_email', icon: markRaw(Mail), modifier: () => canAccess('resend_email_employees') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_employees') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const resendConfirmModal = ref(false);
const resendRow = ref(null);
const resendPreloader = ref(false);

const resendEmail = (row) => {
    resendRow.value = row;
    resendConfirmModal.value = true;
};

const confirmResendEmail = () => {
    resendPreloader.value = true;
    Axios.post(`${HRM_EMPLOYEES}/${resendRow.value.id}/resend-email`)
        .then(({ data }) => {
            toast.success(data.message);
            resendConfirmModal.value = false;
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message ?? 'Failed to send email');
        })
        .finally(() => {
            resendPreloader.value = false;
        });
};

const toggleStatus = (row) => {
    Axios.patch(`${HRM_EMPLOYEES}/${row.id}/toggle-status`)
        .then(({ data }) => {
            toast.success(data.message);
            emitter.emit('reload-' + tableId.value);
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message ?? 'Something went wrong');
        });
};

const actionTiger = (row, action) => {
    if (action.type === 'view') {
        router.push({ name: 'HrmEmployeeDetails', params: { id: row.id } });
    } else if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_EMPLOYEES}/${row.id}`;
    } else if (action.type === 'activate' || action.type === 'deactivate') {
        toggleStatus(row);
    } else if (action.type === 'resend_email') {
        resendEmail(row);
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_EMPLOYEES}/${row.id}`;
    }
};
</script>

<style scoped lang="scss">
:deep(.emp-name-cell) {
    display: flex;
    align-items: center;
    gap: 10px;
}

:deep(.emp-avatar-img) {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid #e2e8f0;
}

:deep(.emp-avatar-initials) {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

:deep(.emp-name-label) {
    font-weight: 600;
    line-height: 1.3;
}

:deep(.emp-name-meta) {
    margin-top: 3px;
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
}
</style>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('payslips')" />

        <app-datatable :id="tableId" :options="options" @action="actionTiger" />
    </div>
</template>

<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute } from "vue-router";
import { useToast } from "vue-toastification";
import { Send, Download } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import Axios from "@/services/axios";
import { HRM_PAYSLIPS } from "@/services/endpoints/hrm.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const toast = useToast();
const emitter = useEmitter();
const route = useRoute();
const tableId = ref('hrm-payslip-table');

const payrunId = route.query.payrun_id;
const url = payrunId ? `${HRM_PAYSLIPS}?payrun_id=${payrunId}` : HRM_PAYSLIPS;

const statusBadgeClass = {
    generated: 'badge bg-success',
    sent: 'badge bg-info text-dark',
};

const options = ref({
    url,
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
            title: t('employee'), key: 'employee', type: 'custom-html',
            modifier: (name, row) => {
                const initials = (name ?? '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
                const avatar = row.profile_picture
                    ? `<img src="${row.profile_picture}" alt="${name}" class="emp-avatar-img">`
                    : `<span class="emp-avatar-initials">${initials}</span>`;
                return `<div class="emp-name-cell">${avatar}<span>${name ?? ''}</span></div>`;
            },
        },
        { title: t('department'), key: 'department', type: 'text' },
        { title: t('period'), key: 'period', type: 'text' },
        { title: t('basic_salary'), key: 'basic_salary', type: 'text' },
        { title: t('net_salary'), key: 'net_salary', type: 'text' },
        {
            title: t('status'), key: 'status', type: 'custom-class',
            modifier: (status) => statusBadgeClass[status] ?? 'badge bg-light text-dark',
        },
        { title: t('sent_at'), key: 'sent_at', type: 'text' },
        {
            title: t('action'), type: 'action', align: 'right',
            permission: canAccess('send_payslips') || canAccess('download_payslips'),
        },
    ],
    actions: [
        {
            title: t('send'), type: 'send', icon: markRaw(Send),
            modifier: () => canAccess('send_payslips'),
        },
        {
            title: t('download'), type: 'download', icon: markRaw(Download),
            modifier: () => canAccess('download_payslips'),
        },
    ],
});

const sendPayslip = (row) => {
    Axios.post(`${HRM_PAYSLIPS}/${row.id}/send`)
        .then((response) => {
            toast.success(response.data.message);
            emitter.emit("reload-" + tableId.value);
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message);
        });
};

const downloadPayslip = (row) => {
    Axios.get(`${HRM_PAYSLIPS}/${row.id}/download`, {
        params: { preview: 1 },
        responseType: 'blob',
    })
        .then((response) => {
            const file = new Blob([response.data], { type: 'application/pdf' });
            window.open(URL.createObjectURL(file));
        })
        .catch(() => {
            toast.error(t('something_went_wrong'));
        });
};

const actionTiger = (row, action) => {
    if (action.type === 'send') {
        sendPayslip(row);
    } else if (action.type === 'download') {
        downloadPayslip(row);
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
</style>

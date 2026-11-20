<script setup>
import { markRaw, ref } from "vue";
import { Eye, Trash } from "@iconoir/vue";
import { CASH_TRANSFERS } from "@/services/endpoints/landlord.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import router from "@/router/index.js";
import usePermission from "@/composable/permission/usePermission.js";

const { canAccess } = usePermission();

const tableId = ref('cash-transfer-table');

const statusBadge = (status) => {
    const map = {
        draft: { color: '#868e96', bg: '#f1f3f5' },
        approved: { color: '#2f9e44', bg: '#d3f9d8' },
        cancelled: { color: '#e03131', bg: '#fff0f0' },
    };
    const s = map[status] ?? map.draft;
    return `<span style="padding:2px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;background:${s.bg};color:${s.color};">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
};

const options = ref({
    url: CASH_TRANSFERS,
    pageSize: 10,
    rowLimit: 15,
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
            title: 'Ref #',
            type: 'text',
            key: 'reference_no',
        },
        {
            title: 'From Shop',
            type: 'object',
            key: 'from_tenant',
            modifier: (t) => t?.name ?? '-',
        },
        {
            title: 'To Shop',
            type: 'object',
            key: 'to_tenant',
            modifier: (t) => t?.name ?? '-',
        },
        {
            title: 'Amount',
            type: 'object',
            key: 'amount',
            modifier: (v) => numberWithCurrencySymbol(v),
        },
        {
            title: 'Date',
            type: 'text',
            key: 'transfer_date',
        },
        {
            title: 'Created At',
            type: 'text',
            key: 'created_at',
        },
        {
            title: 'Status',
            type: 'custom-html',
            key: 'status',
            modifier: statusBadge,
        },
        {
            title: 'Action',
            type: 'action',
            align: 'right',
            permission: canAccess("view_cash_transfers") || canAccess("delete_cash_transfers"),
        },
    ],
    actions: [
        {
            title: 'View',
            type: 'view',
            icon: markRaw(Eye),
            modifier: () => canAccess('view_cash_transfers'),
        },
        {
            title: 'Delete',
            type: 'delete',
            icon: markRaw(Trash),
            modifier: (row) => row.status === 'draft' && canAccess('delete_cash_transfers'),
        },
    ],
});

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'view') {
        router.push({ name: 'viewCashTransfer', params: { id: row.id } });
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${CASH_TRANSFERS}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Cash Transfer" />

        <app-datatable :id="tableId" :options="options" add-button-text="New Transfer"
            :button-visible="canAccess('create_cash_transfers')"
            @buttonAction="router.push({ name: 'createCashTransfer' })" @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

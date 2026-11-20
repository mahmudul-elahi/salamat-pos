<script setup>
import { ref, markRaw } from 'vue';
import { useI18n } from 'vue-i18n';
import { Edit, Trash } from '@iconoir/vue';
import usePermission from '@/composable/permission/usePermission.js';
import { useDeleteModal } from '@/composable/modal/useDeleteModal.js';
import { EMI_BANKS } from '@/services/endpoints/Ecommerce.js';
import EmiModal from './EmiModal.vue';

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('emi-banks-table');
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const isModalActive = ref(false);
const selectedBank = ref(null);

const fmt = (tenures, months) => {
    const found = tenures?.find(t => t.months === months);
    return found ? `${parseFloat(found.charge_rate).toFixed(2)}%` : '—';
};

const options = ref({
    url: EMI_BANKS,
    pageSize: 5,
    rowLimit: 5,
    orderBy: 'asc',
    showGridView: false,
    showPagination: true,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: false,
    columns: [
        {
            title: 'Bank Name',
            type: 'text',
            key: 'bank_name',
        },
        {
            title: 'GW Key',
            type: 'text',
            key: 'gw_key',
        },
        {
            title: '3M',
            type: 'custom-html',
            key: 'tenures',
            modifier: (tenures) => fmt(tenures, 3),
        },
        {
            title: '6M',
            type: 'custom-html',
            key: 'tenures',
            modifier: (tenures) => fmt(tenures, 6),
        },
        {
            title: '9M',
            type: 'custom-html',
            key: 'tenures',
            modifier: (tenures) => fmt(tenures, 9),
        },
        {
            title: '12M',
            type: 'custom-html',
            key: 'tenures',
            modifier: (tenures) => fmt(tenures, 12),
        },
        {
            title: '18M',
            type: 'custom-html',
            key: 'tenures',
            modifier: (tenures) => fmt(tenures, 18),
        },
        {
            title: '24M',
            type: 'custom-html',
            key: 'tenures',
            modifier: (tenures) => fmt(tenures, 24),
        },
        {
            title: '30M',
            type: 'custom-html',
            key: 'tenures',
            modifier: (tenures) => fmt(tenures, 30),
        },
        {
            title: '36M',
            type: 'custom-html',
            key: 'tenures',
            modifier: (tenures) => fmt(tenures, 36),
        },
        {
            title: 'Status',
            type: 'custom-html',
            key: 'is_active',
            modifier: (val) => val
                ? '<span class="badge-square-light bg-success">Active</span>'
                : '<span class="badge-square-light bg-danger">Inactive</span>',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_emi_banks') || canAccess('delete_emi_banks'),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_emi_banks'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_emi_banks'),
        },
    ],
});

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        selectedBank.value = { ...row };
        isModalActive.value = true;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${EMI_BANKS}/${row.id}`;
    }
};

const onSaved = () => {
    isModalActive.value = false;
    selectedBank.value = null;
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="EMI Charge Matrix" />

        <app-datatable
            :id="tableId"
            :options="options"
            add-button-text="Add Bank"
            :button-visible="canAccess('create_emi_banks')"
            @buttonAction="isModalActive = true; selectedBank = null"
            @action="actionTiger" />

        <EmiModal
            v-if="isModalActive"
            :bank="selectedBank"
            :table-id="tableId"
            @saved="onSaved"
            @close="isModalActive = false; selectedBank = null" />

        <app-delete-modal
            v-if="isDeleteModal"
            :table-id="tableId"
            :selected-url="deleteUrl"
            @cancelled="cancelled" />
    </div>
</template>

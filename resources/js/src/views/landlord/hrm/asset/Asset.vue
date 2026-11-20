<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('asset')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_asset')"
            :button-visible="canAccess('create_assets')" @buttonAction="isModalActive = true" @action="actionTiger" />

        <asset-modal v-if="isModalActive" :table-id="tableId" modal-id="asset-modal" :selected-url="selectedData"
            @close="closeModal" />

        <assign-asset-modal v-if="isAssignModalActive" :table-id="tableId" modal-id="assign-asset-modal"
            :asset="selectedAsset" @close="isAssignModalActive = false" />

        <take-back-asset-modal v-if="isTakeBackModalActive" :table-id="tableId" modal-id="take-back-asset-modal"
            :asset="selectedAsset" @close="isTakeBackModalActive = false" />

        <asset-history-modal v-if="isHistoryModalActive" modal-id="asset-history-modal" :asset="selectedAsset"
            @close="isHistoryModalActive = false" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { Edit, Trash, UserPlus, ClockRotateRight, Undo } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { HRM_ASSETS } from "@/services/endpoints/hrm.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import AssetModal from "./AssetModal.vue";
import AssignAssetModal from "./AssignAssetModal.vue";
import TakeBackAssetModal from "./TakeBackAssetModal.vue";
import AssetHistoryModal from "./AssetHistoryModal.vue";
import { useTimezone } from "@/utils/useTimezone.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const { formatDateOnly } = useTimezone();

const tableId = ref('asset-table');

const options = ref({
    url: HRM_ASSETS,
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
        { title: t('asset_name'), key: 'asset_name', type: 'text' },
        { title: t('asset_code'), key: 'asset_code', type: 'text' },
        {
            title: t('category'),
            key: 'category',
            type: 'object',
            modifier: (category) => category ? category.name : '-',
        },
        {
            title: t('serial_number'),
            key: 'serial_number',
            type: 'object',
            modifier: (value) => value ?? '-',
        },
        {
            title: t('purchase_date'),
            key: 'purchase_date',
            type: 'object',
            modifier: (date) => date ? formatDateOnly(date) : '-',
        },
        {
            title: t('purchase_cost'),
            key: 'purchase_cost',
            type: 'object',
            modifier: (value) => value ? numberWithCurrencySymbol(value) : '-',
        },
        {
            title: t('status'),
            key: 'status',
            type: 'custom-html',
            modifier: (status => {
                if (status === 'assigned') {
                    return `<span class="badge-square-light bg-success">${t('assigned')}</span>`
                } else {
                    return `<span class="badge-square-light bg-primary">${t('available')}</span>`
                }
            }),
        },
        {
            title: t('action'), type: 'action', align: 'right',
            permission: canAccess('update_assets') || canAccess('delete_assets')
                || canAccess('assign_assets') || canAccess('return_assets') || canAccess('history_assets'),
        },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_assets') },
        {
            title: t('assign_asset'), type: 'assign', icon: markRaw(UserPlus),
            modifier: (row) => canAccess('assign_assets') && row.status !== 'assigned',
        },
        {
            title: t('take_back'), type: 'take_back', icon: markRaw(Undo),
            modifier: (row) => canAccess('return_assets') && row.status === 'assigned',
        },
        { title: t('asset_history'), type: 'history', icon: markRaw(ClockRotateRight), modifier: () => canAccess('history_assets') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_assets') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const selectedAsset = ref(null);
const isAssignModalActive = ref(false);
const isTakeBackModalActive = ref(false);
const isHistoryModalActive = ref(false);

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_ASSETS}/${row.id}`;
    } else if (action.type === 'assign') {
        selectedAsset.value = row;
        isAssignModalActive.value = true;
    } else if (action.type === 'take_back') {
        selectedAsset.value = row;
        isTakeBackModalActive.value = true;
    } else if (action.type === 'history') {
        selectedAsset.value = row;
        isHistoryModalActive.value = true;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_ASSETS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

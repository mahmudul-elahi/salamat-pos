<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('leave_type')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_leave_type')"
            :button-visible="canAccess('create_leave_types')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <leave-type-modal v-if="isModalActive" :table-id="tableId" modal-id="leave-type-modal"
            :selected-url="selectedData" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { Edit, Trash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { HRM_LEAVE_TYPES } from "@/services/endpoints/hrm.js";
import LeaveTypeModal from "./LeaveTypeModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('leave-type-table');

const options = ref({
    url: HRM_LEAVE_TYPES,
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
        { title: t('name'), key: 'name', type: 'text' },
        {
            title: t('type'), key: 'type', type: 'custom-html',
            modifier: (value) => value === 'paid'
                ? '<span class="badge bg-success">Paid</span>'
                : '<span class="badge bg-warning text-dark">Unpaid</span>',
        },
        { title: t('day'), key: 'day', type: 'text' },
        {
            title: t('enable'), key: 'enable', type: 'custom-html',
            modifier: (value) => value
                ? '<span class="badge bg-success">Enabled</span>'
                : '<span class="badge bg-secondary">Disabled</span>',
        },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_leave_types') || canAccess('delete_leave_types') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_leave_types') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_leave_types') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_LEAVE_TYPES}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_LEAVE_TYPES}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

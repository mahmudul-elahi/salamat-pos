<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('employee_status')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_employee_status')"
            :button-visible="canAccess('create_employee_statuses')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <employee-status-modal v-if="isModalActive" :table-id="tableId" modal-id="employee-status-modal"
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
import { HRM_EMPLOYEE_STATUSES } from "@/services/endpoints/hrm.js";
import EmployeeStatusModal from "./EmployeeStatusModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('employee-status-table');

const options = ref({
    url: HRM_EMPLOYEE_STATUSES,
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
            title: t('color'),
            key: 'color',
            type: 'custom-html',
            modifier: (color) => `<span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:${color};border:1px solid #dee2e6;vertical-align:middle;"></span> <span style="vertical-align:middle;">${color}</span>`,
        },
        { title: t('description'), key: 'description', type: 'text' },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_employee_statuses') || canAccess('delete_employee_statuses') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_employee_statuses') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_employee_statuses') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_EMPLOYEE_STATUSES}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_EMPLOYEE_STATUSES}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

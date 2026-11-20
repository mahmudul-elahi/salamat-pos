<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('department')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_department')"
            :button-visible="canAccess('create_departments')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <department-modal v-if="isModalActive" :table-id="tableId" modal-id="department-modal"
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
import { HRM_DEPARTMENTS } from "@/services/endpoints/hrm.js";
import DepartmentModal from "./DepartmentModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('department-table');

const options = ref({
    url: HRM_DEPARTMENTS,
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
            title: t('department_head'),
            key: 'department_head',
            type: 'object',
            modifier: (head) => head ? head.name : '-',
        },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_departments') || canAccess('delete_departments') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_departments') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_departments') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_DEPARTMENTS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_DEPARTMENTS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

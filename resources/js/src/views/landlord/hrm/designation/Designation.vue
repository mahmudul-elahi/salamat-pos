<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('designation')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_designation')"
            :button-visible="canAccess('create_designations')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <designation-modal v-if="isModalActive" :table-id="tableId" modal-id="designation-modal"
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
import { HRM_DESIGNATIONS } from "@/services/endpoints/hrm.js";
import DesignationModal from "./DesignationModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('designation-table');

const options = ref({
    url: HRM_DESIGNATIONS,
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
        { title: t('description'), key: 'description', type: 'text' },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_designations') || canAccess('delete_designations') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_designations') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_designations') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_DESIGNATIONS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_DESIGNATIONS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

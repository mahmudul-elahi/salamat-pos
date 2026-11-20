<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('work_shift')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_work_shift')"
            :button-visible="canAccess('create_work_shifts')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <work-shift-modal v-if="isModalActive" :table-id="tableId" modal-id="work-shift-modal"
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
import { HRM_WORK_SHIFTS } from "@/services/endpoints/hrm.js";
import WorkShiftModal from "./WorkShiftModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('work-shift-table');

const options = ref({
    url: HRM_WORK_SHIFTS,
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
        { title: t('shift_name'), key: 'shift_name', type: 'text' },
        {
            title: t('working_shift_type'), key: 'working_shift_type', type: 'custom-html',
            modifier: (value) => {
                const badge = value === 'regular'
                    ? '<span class="badge bg-primary">Regular</span>'
                    : '<span class="badge bg-info text-dark">Scheduled</span>';
                return badge;
            }
        },
        { title: t('start_time'), key: 'start_time', type: 'text' },
        { title: t('end_time'), key: 'end_time', type: 'text' },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_work_shifts') || canAccess('delete_work_shifts') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_work_shifts') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_work_shifts') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_WORK_SHIFTS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_WORK_SHIFTS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

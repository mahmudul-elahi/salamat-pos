<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('holiday')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_holiday')"
            :button-visible="canAccess('create_holidays')" @buttonAction="isModalActive = true" @action="actionTiger" />

        <holiday-modal v-if="isModalActive" :table-id="tableId" modal-id="holiday-modal" :selected-url="selectedData"
            @close="closeModal" />

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
import { HRM_HOLIDAYS } from "@/services/endpoints/hrm.js";
import HolidayModal from "./HolidayModal.vue";
import { useTimezone } from "@/utils/useTimezone.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const { formatDateOnly } = useTimezone();

const tableId = ref('holiday-table');

const options = ref({
    url: HRM_HOLIDAYS,
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
            title: t('start_date'),
            key: 'start_date',
            type: 'object',
            modifier: (date) => formatDateOnly(date),
        },
        {
            title: t('end_date'),
            key: 'end_date',
            type: 'object',
            modifier: (date) => formatDateOnly(date),
        },
        {
            title: t('department'),
            key: 'department',
            type: 'object',
            modifier: (dept) => dept ? dept.name : t('all_departments'),
        },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_holidays') || canAccess('delete_holidays') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_holidays') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_holidays') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_HOLIDAYS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_HOLIDAYS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

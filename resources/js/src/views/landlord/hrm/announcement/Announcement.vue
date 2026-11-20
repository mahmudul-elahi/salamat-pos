<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('announcements')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_announcement')"
            :button-visible="canAccess('create_announcements')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <announcement-modal v-if="isModalActive" :table-id="tableId" modal-id="announcement-modal"
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
import { HRM_ANNOUNCEMENTS } from "@/services/endpoints/hrm.js";
import AnnouncementModal from "./AnnouncementModal.vue";
import { useTimezone } from "@/utils/useTimezone.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const { formatDateOnly } = useTimezone();
const tableId = ref('announcement-table');

const options = ref({
    url: HRM_ANNOUNCEMENTS,
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
        { title: t('title'), key: 'title', type: 'text' },
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
            title: t('departments'),
            key: 'departments',
            type: 'custom-html',
            modifier: (depts) => {
                if (!depts || !depts.length) return `<span class="text-muted">—</span>`;
                return depts.map(d =>
                    `<span class="badge bg-primary me-1">${d.name}</span>`
                ).join('');
            },
        },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_announcements') || canAccess('delete_announcements') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_announcements') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_announcements') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_ANNOUNCEMENTS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_ANNOUNCEMENTS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

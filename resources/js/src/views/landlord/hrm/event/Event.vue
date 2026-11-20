<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('events')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_event')"
            :button-visible="canAccess('create_events')" @buttonAction="isModalActive = true" @action="actionTiger" />

        <event-modal v-if="isModalActive" :table-id="tableId" modal-id="event-modal" :selected-url="selectedData"
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
import { HRM_EVENTS } from "@/services/endpoints/hrm.js";
import EventModal from "./EventModal.vue";
import { useTimezone } from "@/utils/useTimezone.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const { formatDateTime } = useTimezone();

const tableId = ref('event-table');

const options = ref({
    url: HRM_EVENTS,
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
        {
            title: t('event_type'),
            key: 'event_type',
            type: 'custom-html',
            modifier: (type) => type === 'bulk'
                ? `<span class="badge bg-primary">${t('bulk')}</span>`
                : `<span class="badge bg-success">${t('individual')}</span>`,
        },
        { title: t('title'), key: 'title', type: 'text' },
        {
            title: t('start_datetime'),
            key: 'start_datetime',
            type: 'object',
            modifier: (start_datetime) => formatDateTime(start_datetime),
        },
        {
            title: t('end_datetime'),
            key: 'end_datetime',
            type: 'object',
            modifier: (end_datetime) => formatDateTime(end_datetime),
        },
        { title: t('location'), key: 'location', type: 'text' },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_events') || canAccess('delete_events') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_events') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_events') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_EVENTS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_EVENTS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

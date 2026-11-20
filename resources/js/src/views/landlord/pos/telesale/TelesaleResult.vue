<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { Edit, Trash } from "@iconoir/vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { TELESALE_RESULTS } from "@/services/endpoints/landlord.js";
import TelesaleResultModal from "@/views/landlord/pos/telesale/TelesaleResultModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();

const tableId = ref('telesale-result-table');

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const options = ref({
    url: TELESALE_RESULTS,
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
            title: t('name'),
            type: 'custom-html',
            key: 'name',
            modifier: (name, row) =>
                `<span class="badge-square-light bg-${escapeHtml(row.color || 'secondary')}">${escapeHtml(name)}</span>`,
        },
        {
            title: t('sort_order'),
            type: 'text',
            key: 'sort_order',
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => status
                ? `<span class="badge-square-light bg-success">${t('active')}</span>`
                : `<span class="badge-square-light bg-secondary">${t('inactive')}</span>`,
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_telesale_results') || canAccess('delete_telesale_results'),
        },
    ],
    filters: [],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_telesale_results'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_telesale_results'),
        },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${TELESALE_RESULTS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${TELESALE_RESULTS}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('telesale_results')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_result')"
            :button-visible="canAccess('create_telesale_results')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <telesale-result-modal v-if="isModalActive" :table-id="tableId" modal-id="telesale-result-modal"
            :selected-url="selectedData" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss"></style>

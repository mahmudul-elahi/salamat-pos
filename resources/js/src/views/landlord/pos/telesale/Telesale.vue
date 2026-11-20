<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { Edit, Trash, Eye, CheckCircle } from "@iconoir/vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { TELESALES, SELECTED_TELESALE_RESULTS, SELECTED_SELLERS } from "@/services/endpoints/landlord.js";
import TelesaleModal from "@/views/landlord/pos/telesale/TelesaleModal.vue";
import TelesaleViewModal from "@/views/landlord/pos/telesale/TelesaleViewModal.vue";
import TelesaleResultUpdateModal from "@/views/landlord/pos/telesale/TelesaleResultUpdateModal.vue";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";

const { t } = useI18n();
const { canAccess } = usePermission();

const tableId = ref('telesale-table');

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const options = ref({
    url: TELESALES,
    pageSize: 10,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: true,
    columns: [
        {
            title: t('date'),
            type: 'text',
            key: 'date',
        },
        {
            title: t('customer_name'),
            type: 'object',
            key: 'customer_name',
            modifier: (name) => name || '-',
        },
        {
            title: t('customer_number'),
            type: 'custom-html',
            key: 'customer_number',
            // The existing/new flag is the whole point of the number column, so
            // it rides along with it rather than costing a column of its own.
            modifier: (number, row) => {
                const badge = row.customer_type === 'existing'
                    ? `<span class="badge-square-light bg-success">${t('existing')}</span>`
                    : `<span class="badge-square-light bg-primary">${t('new')}</span>`;
                return `<div class="text-nowrap">${escapeHtml(number)} ${badge}</div>`;
            },
        },
        {
            title: t('address'),
            type: 'component',
            key: 'address',
            componentName: h(AddressColumn),
        },
        {
            title: t('customer_demand'),
            type: 'object',
            key: 'demand_product',
            modifier: (product) => product || '-',
        },
        {
            title: t('media'),
            type: 'custom-html',
            key: 'media',
            modifier: (media) => media
                ? `<span class="badge-square-light bg-success">${escapeHtml(media)}</span>`
                : '-',
        },
        {
            title: t('note'),
            type: 'component',
            key: 'note',
            componentName: h(NoteColumn),
        },
        {
            title: t('result'),
            type: 'custom-html',
            key: 'result',
            modifier: (result) => result
                ? `<span class="badge-square-light bg-${escapeHtml(result.color || 'secondary')}">${escapeHtml(result.name)}</span>`
                : '-',
        },
        {
            title: t('salesman_name'),
            type: 'object',
            key: 'salesman',
            modifier: (name) => name || '-',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('view_telesales') || canAccess('update_telesales') || canAccess('delete_telesales'),
        },
    ],
    filters: [
        {
            label: t('date'),
            type: 'date-range',
            key: 'date',
            permission: true,
        },
        {
            label: t('result'),
            type: 'advance-search-select',
            key: 'result',
            fetchUrl: SELECTED_TELESALE_RESULTS,
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 100,
            permission: true,
        },
        {
            label: t('salesman_name'),
            type: 'advance-search-select',
            key: 'salesman',
            fetchUrl: SELECTED_SELLERS,
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            permission: true,
        },
        {
            label: t('customer_type'),
            type: 'search-select',
            key: 'customer_type',
            options: [
                { id: 'existing', name: t('existing') },
                { id: 'new', name: t('new') },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('view_details'),
            type: 'view_details',
            icon: markRaw(Eye),
            modifier: () => canAccess('view_telesales'),
        },
        {
            title: t('update_result'),
            type: 'update_result',
            icon: markRaw(CheckCircle),
            modifier: () => canAccess('update_telesales'),
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_telesales'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_telesales'),
        },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const isViewModal = ref(false);
const viewUrl = ref(null);

const isResultModal = ref(false);
const selectedForResult = ref(null);

const actionTiger = (row, action) => {
    if (action.type === 'view_details') {
        viewUrl.value = `${TELESALES}/${row.id}`;
        isViewModal.value = true;
    } else if (action.type === 'update_result') {
        selectedForResult.value = row;
        isResultModal.value = true;
    } else if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${TELESALES}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${TELESALES}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('telesales')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_telesale')"
            :button-visible="canAccess('create_telesales')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <telesale-modal v-if="isModalActive" :table-id="tableId" modal-id="telesale-modal"
            :selected-url="selectedData" @close="closeModal" />

        <telesale-view-modal v-if="isViewModal" :table-id="tableId" modal-id="telesale-view-modal"
            :selected-url="viewUrl" @close="isViewModal = false; viewUrl = null" />

        <telesale-result-update-modal v-if="isResultModal" :table-id="tableId" modal-id="telesale-result-update-modal"
            :telesale="selectedForResult" :selected-url="`${TELESALES}/${selectedForResult.id}/result`"
            @close="isResultModal = false; selectedForResult = null" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss"></style>

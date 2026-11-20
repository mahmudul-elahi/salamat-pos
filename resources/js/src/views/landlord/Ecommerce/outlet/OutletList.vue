<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {Edit, Trash} from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import router from "@/router/index.js";
import {OUTLETS} from "@/services/endpoints/Ecommerce.js";

const {t} = useI18n();
const {canAccess} = usePermission();
const tableId = ref('outlet-table');
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal();

const options = ref({
    url: OUTLETS,
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
            type: 'text',
            key: 'name',
        },
        {
            title: t('phone'),
            type: 'text',
            key: 'phone',
        },
        {
            title: t('opens'),
            type: 'custom-html',
            key: 'opens',
            modifier: (opens, row) => `${opens ?? '-'} <span class="text-muted">→</span> ${row.closes ?? '-'}`,
        },
        {
            title: t('close_day'),
            type: 'custom-html',
            key: 'close_day',
            modifier: (day) => day ? t(day) : '-',
        },
        {
            title: t('outlet_images'),
            type: 'text',
            key: 'images_count',
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => status
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Inactive</span>',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_outlets') || canAccess('delete_outlets'),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_outlets'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_outlets'),
        },
    ],
});

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push({name: 'editOutlet', params: {id: row.id}});
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${OUTLETS}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('outlets')"/>

        <app-datatable
            :id="tableId"
            :options="options"
            :add-button-text="$t('add_outlet')"
            :button-visible="canAccess('create_outlets')"
            @buttonAction="router.push({name: 'createOutlet'})"
            @action="actionTiger"/>

        <app-delete-modal
            v-if="isDeleteModal"
            :table-id="tableId"
            :selected-url="deleteUrl"
            @cancelled="cancelled"/>
    </div>
</template>

<style scoped lang="scss"></style>


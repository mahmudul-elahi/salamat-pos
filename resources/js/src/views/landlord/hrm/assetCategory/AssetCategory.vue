<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('asset_category')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_asset_category')"
            :button-visible="canAccess('create_asset_categories')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <asset-category-modal v-if="isModalActive" :table-id="tableId" modal-id="asset-category-modal"
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
import { HRM_ASSET_CATEGORIES } from "@/services/endpoints/hrm.js";
import AssetCategoryModal from "./AssetCategoryModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('asset-category-table');

const options = ref({
    url: HRM_ASSET_CATEGORIES,
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
            title: t('status'),
            key: 'status',
            type: 'custom-html',
            modifier: (status => {
                if (status) {
                    return `<span class="badge-square-light bg-success">Active</span>`
                } else {
                    return `<span class="badge-square-light bg-danger">Inactive</span>`
                }
            }),
        },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_asset_categories') || canAccess('delete_asset_categories') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_asset_categories') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_asset_categories') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${HRM_ASSET_CATEGORIES}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${HRM_ASSET_CATEGORIES}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

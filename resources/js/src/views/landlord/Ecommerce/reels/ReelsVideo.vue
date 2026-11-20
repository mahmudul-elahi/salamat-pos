<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('reels_video')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_reels_video')"
            :button-visible="canAccess('create_reels_videos')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <reels-video-modal v-if="isModalActive" :table-id="tableId" modal-id="reels-video-modal"
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
import { REELS_VIDEOS } from "@/services/endpoints/Ecommerce.js";
import ReelsVideoModal from "./ReelsVideoModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('reels-video-table');

const options = ref({
    url: REELS_VIDEOS,
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
        { title: t('product'), key: 'product', type: 'object', modifier: (product) => product ? product.product_name : '-' },
        { title: t('status'), key: 'status', type: 'custom-html', modifier: (val) => val ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' },
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_reels_videos') || canAccess('delete_reels_videos') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_reels_videos') },
    ],
});
const { isModalActive, selectedData, closeModal } = useOpenModal();

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();



const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${REELS_VIDEOS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${REELS_VIDEOS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

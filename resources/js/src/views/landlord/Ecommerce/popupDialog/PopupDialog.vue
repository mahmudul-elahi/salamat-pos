<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('popup_dialog')" />

        <app-datatable :id="tableId" :options="options" :add-button-text="$t('add_popup_dialog')"
            :button-visible="canAccess('create_popup_dialogs')" @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <popup-dialog-modal v-if="isModalActive" :table-id="tableId" modal-id="popup-dialog-modal"
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
import { POPUP_DIALOGS } from "@/services/endpoints/Ecommerce.js";
import PopupDialogModal from "./PopupDialogModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('popup-dialog-table');

const options = ref({
    url: POPUP_DIALOGS,
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
            title: t('image'),
            key: 'image',
            type: 'custom-html',
            modifier: (image => {
                return `<img src="${image}" width="50" height="50" />`
            }),
        },
        {
            title: t('mobile_image'),
            key: 'mobile_image',
            type: 'custom-html',
            modifier: (image => {
                return image
                    ? `<img src="${image}" width="50" height="50" />`
                    : '<span style="color:#9ca3af;">—</span>';
            }),
        },
        {
            title: t('url'),
            key: 'url',
            type: 'custom-html',
            modifier: (url => {
                return url
                    ? `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`
                    : '<span style="color:#9ca3af;">—</span>';
            }),
        },
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
        { title: t('action'), type: 'action', align: 'right', permission: canAccess('update_popup_dialogs') || canAccess('delete_popup_dialogs') },
    ],
    actions: [
        { title: t('edit'), type: 'edit', icon: markRaw(Edit), modifier: () => canAccess('update_popup_dialogs') },
        { title: t('delete'), type: 'delete', icon: markRaw(Trash), modifier: () => canAccess('delete_popup_dialogs') },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${POPUP_DIALOGS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${POPUP_DIALOGS}/${row.id}`;
    }
};
</script>

<style scoped lang="scss"></style>

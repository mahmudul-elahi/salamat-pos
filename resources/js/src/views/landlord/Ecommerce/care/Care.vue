<script setup>
import {markRaw, ref} from "vue";
import {useI18n} from "vue-i18n";
import {CARES} from "@/services/endpoints/landlord.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import {Edit, Trash} from "@iconoir/vue";
import router from "@/router/index.js";

const {t} = useI18n();

const tableId = ref('care-table');

const options = ref({
    url: CARES,
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
            title: 'Value (%)',
            type: 'text',
            key: 'value',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: true,
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => true,
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => true,
        },
    ],
});

const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push(`/edit-care/${row.id}`);
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${CARES}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Care"/>

        <app-datatable :id="tableId"
                       add-button-text="Add Care"
                       :button-visible="true"
                       @buttonAction="router.push('/create-care')"
                       :options="options"
                       @action="actionTiger"/>

        <app-delete-modal v-if="isDeleteModal"
                          :table-id="tableId"
                          :selected-url="deleteUrl"
                          @cancelled="cancelled"/>
    </div>
</template>

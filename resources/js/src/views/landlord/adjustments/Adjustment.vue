<script setup>
import {markRaw, ref} from "vue";
import {useI18n} from "vue-i18n";
import router from "@/router";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import {ADJUSTMENTS} from "@/services/endpoints/landlord.js";
import { Edit, Eye, Trash } from "@iconoir/vue";

// const {canAccess} = usePermission();

const {t} = useI18n();

const tableId = ref('adjustment-table')

const options = ref({
    url: ADJUSTMENTS,
    pageSize: 5,
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
            title: t('date'),
            type: 'text',
            key: 'date',
        },
        {
            title: t('reference'),
            type: 'text',
            key: 'reference',
        },
        {
            title: t('warehouse'),
            type: 'text',
            key: 'warehouse',
        },
        {
            title: t('total_product'),
            type: 'text',
            key: 'total_product',
        },

        {
            title: t('action'),
            type: 'action',
            align: 'right'
        },
    ],
    filters: [],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => true,
        },
        {
            title: t('view'),
            type: 'view',
            icon: markRaw(Eye),
            modifier: () => true,
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => true,
        }
    ],
})
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push({ name: 'editAdjustment', params: { id: row.id } })
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${ADJUSTMENTS}/${row.id}`
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('adjustment_list')">
            <template #breadcrumb-actions>
                <router-link to="create-adjustment" class="btn btn-primary text-white">
                    {{ $t('add_adjustment') }}
                </router-link>
            </template>
        </app-breadcrumb>

        <app-datatable :id="tableId"
                       :options="options"
                       :add-button-text="$t('add_adjustment')"
                       @buttonAction="isModalActive = true"
                       :button-visible=false
                       @action="actionTiger"/>

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled"/>

    </div>
</template>

<style scoped lang="scss">
</style>
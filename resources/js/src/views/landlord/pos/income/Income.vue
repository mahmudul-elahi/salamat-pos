<script setup>
import { h, markRaw, ref } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import { INCOMES, SELECTED_DIRECTORS, SELECTED_INCOME_CATEGORY, SELECTED_SHOPS } from "@/services/endpoints/landlord.js";
import { formatDateToLocal, numberWithCurrencySymbol } from "@/utils/helper.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import { Edit, Trash } from "@iconoir/vue";
import AttachmentsColumn from "@/views/landlord/common/component/AttachmentsColumn.vue";
import DirectorProfile from "@/views/landlord/pos/HelperComponent/DirectorProfile.vue";
import router from "@/router/index.js";

const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();

const { t } = useI18n();

const tableId = ref('income-table')


const options = ref({
    url: INCOMES,
    pageSize: 5,
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
            title: 'Director',
            type: 'component',
            key: 'user',
            componentName: h(DirectorProfile)
        },
        {
            title: t('shop_name'),
            type: 'object',
            key: 'tenant',
            modifier: (tenant => tenant?.name)
        },
        {
            title: t('title'),
            type: 'text',
            key: 'title',
        },

        {
            title: t('date'),
            type: 'text',
            key: 'date',
            modifier: (date => formatDateToLocal(date))
        },

        {
            title: t('contact'),
            type: 'object',
            key: 'contact',
            modifier: (contact => contact ? contact.name : '-')
        },
        {
            title: t('attachments'),
            type: 'component',
            key: 'attachments',
            componentName: h(AttachmentsColumn)
        },
        {
            title: t('note'),
            type: 'component',
            key: 'note',
            componentName: h(NoteColumn)
        },
        {
            title: t('amount'),
            type: 'object',
            key: 'amount',
            modifier: (amount => numberWithCurrencySymbol(amount))
        },
        {
            title: 'Created By',
            type: 'object',
            key: 'created_by',
            modifier: (val) => val?.name ?? '-',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_incomes") || canAccess("delete_incomes"),
        },
    ],
    filters: [
        {
            type: 'advance-search-select',
            key: 'director',
            label: 'Director',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_DIRECTORS}`,
            permission: canFilterByShop(),
        },
        {
            label: t('warehouse_or_store_name'),
            type: 'advance-search-select',
            key: 'warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
        },
        {
            label: t('date'),
            type: 'date-range',
            key: 'date',
            placeholder: t('select_a_range'),
            permission: true,
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess("update_incomes"),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess("delete_incomes"),
        }
    ],
})

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()
const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push(`/edit-income/${row.id}`)
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${INCOMES}/${row.id}`
    }
}
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('incomes')" />

        <app-datatable :id="tableId" :options="options" @action="actionTiger" />


        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
    </div>
</template>

<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { Edit, Eye, Trash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import {SUPPLIERS } from "@/services/endpoints/landlord.js";
import SupplierModal from "@/views/landlord/pos/people/suppliers/SupplierModal.vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import AddressColumn from "@/views/landlord/common/component/AddressColumn.vue";
import router from "@/router/index.js";

const { canAccess } = usePermission();

const store = useProfileStore();
const { profileData } = storeToRefs(store);

const { t } = useI18n();

const tableId = ref('supplier-table')

const options = ref({
    url: SUPPLIERS,
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
            title: t('name'),
            type: 'text',
            key: 'name',
        },
        {
            title: t('business_name'),
            type: 'text',
            key: 'business_name',
        },
        {
            title: t('phone_number'),
            type: 'text',
            key: 'phone',
        },
        {
            title: t('email'),
            type: 'text',
            key: 'email',
        },
        {
            title: t('address'),
            type: 'component',
            key: 'address',
            componentName: h(AddressColumn)
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_suppliers') || canAccess('delete_suppliers')
        },
    ],
    filters: [
        // {
        //     label: t('warehouse_or_store_name'),
        //     type: 'advance-search-select',
        //     key: 'shop',
        //     listKeyField: 'id',
        //     listValueField: 'name',
        //     rowPerLoad: 10,
        //     fetchUrl: `${SELECTED_SHOPS}`,
        //     permission: canFilterByShop(),
        // },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_suppliers'),
        },
        {
            title: t('view_details'),
            type: 'supplier_report_details',
            icon: markRaw(Eye),
            modifier: () => canAccess('view_supplier_purchase_reports') ||
                canAccess('view_supplier_purchase_return_reports') ||
                canAccess('view_supplier_purchase_transaction_reports') ||
                canAccess('view_supplier_purchase_return_transaction_reports') ||
                canAccess('view_supplier_ledger_reports'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_suppliers'),
        }
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${SUPPLIERS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${SUPPLIERS}/${row.id}`
    } else if (action.type === 'supplier_report_details') {
        router.push(`/supplier-report-details/${row.id}`)
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('offline_customers')" />

        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

        <supplier-modal v-if="isModalActive" :table-id="tableId" modal-id="supplier-modal" :selected-url="selectedData"
            :tenant="profileData?.tenant" @close="closeModal" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

    </div>
</template>

<style scoped lang="scss"></style>

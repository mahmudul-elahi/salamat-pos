<script setup>
import { markRaw, ref } from "vue";
import { useI18n } from "vue-i18n";

import { PRE_OWANED_PRODUCT_SEOS } from "@/services/endpoints/Ecommerce.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { formatDateToLocal } from "@/utils/helper.js"
import usePermission from "@/composable/permission/usePermission.js";
import { Settings } from "@iconoir/vue";
import ProductAttachment from "@/views/landlord/pos/product/ProductAttachment.vue";
import UsedProductSeoModal from "@/views/landlord/Ecommerce/SEO/TekzoPage/PreOwned/UsedProductSeoModal.vue";

const { canAccess } = usePermission();

const { t } = useI18n();

const tableId = ref('used-product-table')

const options = ref({
    url: PRE_OWANED_PRODUCT_SEOS,
    pageSize: 5,
    rowLimit: 10,
    orderBy: 'desc',
    boxShadow: false,
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
            type: 'component',
            key: 'product_name',
            componentName: markRaw(ProductAttachment),
        },
        {
            title: t('category'),
            type: 'object',
            key: 'category',
            modifier: (category => category?.name)
        },
        {
            title: t('brand'),
            type: 'object',
            key: 'brand',
            modifier: (brand => brand?.name)
        },
        {
            title: t('created_at'),
            type: 'object',
            key: 'created_at',
            modifier: (created_at => created_at ? formatDateToLocal(created_at) : '-')
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_products") || canAccess("delete_products")
        },
    ],
    filters: [],
    actions: [
        {
            title: t('manage_seo'),
            type: 'manage_seo',
            icon: markRaw(Settings),
            modifier: () => true,
        },
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()

const actionTiger = (row, action) => {
    if (action.type === 'manage_seo') {
        isModalActive.value = true
        selectedData.value = `${PRE_OWANED_PRODUCT_SEOS}/${row.id}`
    }
}


</script>

<template>
    <div>
        <app-datatable :id="tableId" :options="options" :button-visible="false" @action="actionTiger" />


        <used-product-seo-modal v-if="isModalActive" :table-id="tableId" modal-id="product-seo-modal"
            :selected-url="selectedData" @close="closeModal" />

    </div>
</template>
<style scoped lang="scss"></style>

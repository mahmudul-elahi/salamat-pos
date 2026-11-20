<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { SELECTED_SHOPS, SELECTED_PRODUCT_LIST } from "@/services/endpoints/landlord.js";
const { t } = useI18n();

const tableId = ref('purchase-stock-table')

const options = ref({
    url: 'admin/pos/product-stock-calculations',
    pageSize: 5,
    rowLimit: 20,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: true,
    columns: [
        // {
        //     title: t('warehouse'),
        //     type: 'object',
        //     key: 'tenant',
        //     modifier: (tenant => tenant?.name)
        // },
        {
            title: t('product_name'),
            type: 'text',
            key: 'product_name',
        },
        {
            title: t('variant_attribute'),
            type: 'text',
            key: 'variant_attributes',
        },
        {
            title: t('variant_sku'),
            type: 'text',
            key: 'variant_sku',
        },

        {
            title: t('total_purchase'),
            type: 'text',
            key: 'total_purchase',
        },
        {
            title: t('p.return'),
            type: 'text',
            key: 'total_purchase_return',
        },
        {
            title: t('total_sale'),
            type: 'text',
            key: 'total_sale',
        },
        {
            title: t('s.return'),
            type: 'text',
            key: 'total_sale_return',
        },
        {
            title: t('total_out'),
            type: 'text',
            key: 'total_out',
        },
        {
            title: t('current_stock'),
            type: 'text',
            key: 'net_stock',
        },
        // {
        //     title: t('total_unit_price'),
        //     type: 'object',
        //     key: 'total_unit_price',
        //     modifier: (total_unit_price => numberWithCurrencySymbol(total_unit_price)),
        // },

    ],
    filters: [
        {
            label: t('product'),
            type: 'advance-search-select',
            key: 'product',
            listKeyField: 'id',
            listValueField: 'product_name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_PRODUCT_LIST}`,
            permission: true,
        },
        {
            label: t('warehouse'),
            type: 'advance-search-select',
            key: 'warehouse',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: true,
        },
    ],
    actions: [

    ],
})



const actionTiger = (row, action) => {

}



</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('purchase_stocks')" />
        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

    </div>
</template>

<style scoped lang="scss"></style>

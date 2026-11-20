<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { PURCHASES_STOCKS } from "@/services/endpoints/pos.js";
import { numberWithCurrencySymbol } from "@/utils/helper";
import WareHouseProfile from "@/views/landlord/pos/HelperComponent/WareHouseProfile.vue";

const { t } = useI18n();

const tableId = ref('purchase-stock-table')

const options = ref({
    url: PURCHASES_STOCKS,
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
            title: t('warehouse'),
            type: 'component',
            key: 'tenant',
            componentName: h(WareHouseProfile),
        },
        {
            title: t('total_quantity'),
            type: 'text',
            key: 'total_qty',
        },
        {
            title: t('total_unit_price'),
            type: 'object',
            key: 'total_unit_price',
            accountAble: true,
            modifier: (total_unit_price => numberWithCurrencySymbol(total_unit_price)),
        },
        {
            title: t('total_sale_price'),
            type: 'object',
            key: 'total_sale',
            accountAble: true,
            modifier: (total_sale => numberWithCurrencySymbol(total_sale)),
        },
        {
            title: t('investment'),
            type: 'object',
            key: 'total_balance',
            accountAble: true,
            modifier: (total_balance => numberWithCurrencySymbol(total_balance)),
        },
        {
            title: t('total_expense'),
            type: 'object',
            key: 'total_expense',
            accountAble: true,
            modifier: (total_expense => numberWithCurrencySymbol(total_expense)),
        },
        {
            title: t('profit_loss'),
            type: 'object',
            key: 'profit_loss',
            accountAble: true,
            modifier: (profit_loss => numberWithCurrencySymbol(profit_loss)),
        },

    ],
    filters: [

    ],
    actions: [

    ],
})



const actionTiger = (row, action) => {

}



</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('shop_investment')" />
        <app-datatable :id="tableId" :options="options" @action="actionTiger" />
    </div>
</template>

<style scoped lang="scss"></style>

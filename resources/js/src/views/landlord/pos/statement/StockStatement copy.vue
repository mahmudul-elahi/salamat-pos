<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import { CLOSING_STATEMENTS, STOCK_STATEMENT_PDF } from "@/services/endpoints/landlord.js";
import SkuColumn from "@/views/landlord/common/component/SkuColumn.vue";
import PurchaseColumn from "@/views/landlord/common/component/PurchaseColumn.vue";
import SaleColumn from "@/views/landlord/common/component/SaleColumn.vue";
import OpeningStockColumn from "@/views/landlord/common/component/OpeningStockColumn.vue";
import ClosingStockColumn from "@/views/landlord/common/component/ClosingStockColumn.vue";
import PurchaseReturnColumn from "@/views/landlord/common/component/PurchaseReturnColumn.vue";
import SaleReturnColumn from "@/views/landlord/common/component/SaleReturnColumn.vue";
import { useToast } from "vue-toastification";
import Axios from "@/services/axios/index.js";
const { t } = useI18n();
const toast = useToast();
const tableId = ref('stock-statement-table')

const options = ref({
    url: CLOSING_STATEMENTS,
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
            title: t('sl'),
            type: 'text',
            key: 'index',
        },
        {
            title: t('product'),
            type: 'text',
            key: 'product',
        },
        {
            title: t('sku'),
            type: 'component',
            key: 'sku',
            accountAble: true,
            isCount: true,
            componentName: h(SkuColumn),
        },
        {
            title: t('opening_stock'),
            type: 'component',
            key: 'opening_stock',
            accountAble: true,
            isCount: true,
            componentName: h(OpeningStockColumn),
        },
        {
            title: t('purchase'),
            type: 'component',
            key: 'purchase',
            accountAble: true,
            isCount: true,
            componentName: h(PurchaseColumn),
        },
        {
            title: t('purchase_return'),
            type: 'component',
            key: 'purchase_return',
            accountAble: true,
            isCount: true,
            componentName: h(PurchaseReturnColumn),
        },
        {
            title: t('sale'),
            type: 'component',
            key: 'sale',
            accountAble: true,
            isCount: true,
            componentName: h(SaleColumn),
        },
        {
            title: t('sale_return'),
            type: 'component',
            key: 'sale_return',
            accountAble: true,
            isCount: true,
            componentName: h(SaleReturnColumn),
        },
        {
            title: t('closing_stock'),
            type: 'component',
            key: 'closing_stock',
            accountAble: true,
            isCount: true,
            componentName: h(ClosingStockColumn),
        },
    ],
    actions: [],
    filters: [
        {
            label: t('date'),
            type: 'date-range',
            key: 'date',
            placeholder: t('select_a_range'),
            permission: true,
        },
    ]
})

const actionTiger = (row, action) => {

}
const printStockStatement = async () => {
    try {
        const response = await Axios.post(
            STOCK_STATEMENT_PDF,
            {},
            { responseType: "blob" }
        );

        // Open PDF
        const file = new Blob([response.data], { type: "application/pdf" });
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);
    } catch (error) {
        console.error(error);
        toast.error(error?.response?.data?.message);
    }
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('stock_statements')">
            <template #breadcrumb-actions>
                <button class="btn btn-primary text-white" @click="printStockStatement">
                    {{ $t('generate_print') }}
                </button>
            </template>
        </app-breadcrumb>

        <app-datatable :id="tableId" :options="options" :button-visible=false @action="actionTiger" />

    </div>
</template>
<style scoped lang="scss"></style>

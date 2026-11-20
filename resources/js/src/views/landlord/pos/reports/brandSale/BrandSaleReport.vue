<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { BRAND_SALE_REPORTS, SELECTED_CATEGORY, SELECTED_BRAND, SELECTED_SHOPS } from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";

const { t } = useI18n();
const { canAccess } = usePermission();


const tableId = ref('stock-report-table')

const options = ref({
    url: BRAND_SALE_REPORTS,
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
            title: t('brand_name'),
            type: 'text',
            key: 'brand_name',
        },
        {
            title: t('total_sales'),
            type: 'object',
            key: 'total_sales',
            modifier: (total_sales => numberWithCurrencySymbol(total_sales))
        },

    ],
    actions: [],

})

const actionTiger = (row, action) => {

}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('brand_sale_reports')" />

        <app-datatable :id="tableId"
            :options="options"
            :button-visible=false
            @action="actionTiger" />

    </div>
</template>
<style scoped lang="scss"></style>

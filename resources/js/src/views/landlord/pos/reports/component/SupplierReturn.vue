<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import {SELECTED_SHOPS, SUPPLIER_PURCHASE_RETURN_REPORTS} from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol, formatDateToLocal } from "@/utils/helper";

const { isSupperAdmin, canFilterByShop } = usePermission();

const { t } = useI18n();
const props = defineProps({
    supplierId: {
        type: [String, Number],
        required: true
    }
})
const tableId = ref('supplier-reports-table')

const options = ref({
    url: `${SUPPLIER_PURCHASE_RETURN_REPORTS}/${props.supplierId}`,
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
    boxShadow: false,
    columns: [
        {
            title: t('supplier_name'),
            type: 'text',
            key: 'supplier_name',
        },
        {
            title: t('shop_name'),
            type: 'text',
            key: 'shop_name',
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'invoice_number',
        },
        {
            title: t('date'),
            type: 'object',
            key: 'date',
            modifier: (date => formatDateToLocal(date))
        },
        {
            title: t('grand_total'),
            type: 'object',
            key: 'grand_total',
            accountAble: true,
            modifier: (grand_total => numberWithCurrencySymbol(grand_total))
        },
        {
            title: t('paid'),
            type: 'object',
            key: 'paid',
            accountAble: true,
            modifier: (paid => numberWithCurrencySymbol(paid))
        },
        {
            title: t('due'),
            type: 'object',
            key: 'due',
            accountAble: true,
            modifier: (due => numberWithCurrencySymbol(due))
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => {
                if (status === 'due')
                    return `<div class="mb-2"><span class="badge-square-light bg-danger text-capitalize">${status}</span></div>`;
                else if (status === 'partially_paid')
                    return `<div class="mb-2"><span class="badge-square-light bg-info text-capitalize">${status}</span></div>`;
                else
                    return `<div class="mb-2"><span class="badge-square-light bg-success text-capitalize">${status}</span></div>`;
            }
        },
    ],
    filters: [
        {
            label: t('status'),
            type: 'search-select',
            key: 'status',
            options: [
                { id: 'paid', name: t('paid') },
                { id: 'partially_paid', name: t('partially_paid') }
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
        {
            label: t('date'),
            type: 'date-range',
            key: 'date',
            placeholder: t('select_a_range'),
            permission: true,
        },
        {
            label: t('warehouse_or_store_name'),
            type: 'advance-search-select',
            key: 'shop',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
        },
    ],
    actions: [
    ],
})


const actionTiger = (row, action) => {

}
const printReturnPurchase = async () => {
    try {
        const payload = {
            date: currentFilters.value.date || [],
            brand: currentFilters.value.brand || ''
        };
        const response = await Axios.post(
            STOCK_STATEMENT_PDF,
            payload,
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
    <app-breadcrumb :page-title="$t('supplier_return_report')">
        <template #breadcrumb-actions>
            <!-- <button class="btn btn-primary text-white" @click="printReturnPurchase">
                {{ $t('generate_print') }}
            </button> -->
        </template>
    </app-breadcrumb>

    <app-datatable :id="tableId" :options="options" @action="actionTiger" />
</template>

<style scoped lang="scss"></style>

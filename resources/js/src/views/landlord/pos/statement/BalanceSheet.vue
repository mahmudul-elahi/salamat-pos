<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import {
    SELECTED_SHOPS,
    BALANCE_SHEETS,
} from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { useToast } from "vue-toastification";
import Axios from "@/services/axios/index.js";
import usePermission from "@/composable/permission/usePermission.js";
import BalanceSheetsCustomerProfile from "../HelperComponent/BalanceSheetsCustomerProfile.vue";
const { isSupperAdmin, canFilterByShop } = usePermission();

const { t } = useI18n();

const toast = useToast();
const tableId = ref('daily-closing-table')
const preloader = ref(false);

const options = ref({
    url: BALANCE_SHEETS,
    pageSize: 5,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    hideTotal: true,
    showSearch: true,
    showFilter: true,
    columns: [
        {
            title: t('name'),
            type: 'component',
            key: 'name',
            componentName: h(BalanceSheetsCustomerProfile),
        },
        {
            title: t('business_name'),
            type: 'text',
            key: 'business_name',
        },
        {
            title: t('email'),
            type: 'text',
            key: 'email',
        },
        {
            title: t('phone'),
            type: 'text',
            key: 'phone',
        },
        {
            title: t('receivable'),
            type: 'object',
            key: 'total_debit',
            accountAble: true,
            modifier: (total_debit => numberWithCurrencySymbol(total_debit))
        },
        {
            title: t('payable'),
            type: 'object',
            key: 'total_credit',
            accountAble: true,
            modifier: (total_credit => numberWithCurrencySymbol(total_credit))
        },
        {
            title: t('balance'),
            type: 'object',
            key: 'balance',
            accountAble: true,
            modifier: (balance => numberWithCurrencySymbol(balance))
        },
    ],
    actions: [],
    filters: [
        {
            label: t('warehouse'),
            type: 'advance-search-select',
            key: 'shop',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
        },
        {
            // Receivable = they owe us, payable = we owe them.
            label: t('balance'),
            type: 'search-select',
            key: 'balance_type',
            options: [
                { id: 'receivable', name: t('receivable') },
                { id: 'payable', name: t('payable') },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
    ]
})

const actionTiger = (row, action) => {

}
const currentFilters = ref({});
const onFilterChange = (filters) => {
    currentFilters.value = filters;
};
const buttonAction = () => {
    preloader.value = true;
    const filters = {
        warehouse: currentFilters.value.warehouse || '',
        date: currentFilters.value.date || '',
    };
    const queryString = new URLSearchParams(filters).toString();

    Axios.get(`${DAILY_CLOSING_PDF_DOWNLOAD}?${queryString}`, { responseType: 'blob' })
        .then(response => {
            const file = new Blob([response.data], { type: 'application/pdf' });
            const fileURL = URL.createObjectURL(file);
            window.open(fileURL);
        })
        .catch(error => {
            toast.error("Failed to generate daily closing PDF");
        })
        .finally(() => {
            preloader.value = false;
        });
}
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('balance_sheets')" />
        <app-datatable :id="tableId" :options="options" @action="actionTiger" @filter-change="onFilterChange" />
    </div>
</template>
<style scoped lang="scss"></style>
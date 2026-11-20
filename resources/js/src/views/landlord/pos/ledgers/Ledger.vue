<script setup>
import { ref, h, onMounted, onBeforeUnmount } from "vue";
import { useI18n } from "vue-i18n";
import { Edit } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { LEDGERS, SELECTED_SHOPS } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import router from "@/router/index.js";
import LeadgerContactProfile from "@/views/landlord/pos/ledgers/LeadgerContactProfile.vue";

const { canAccess, canFilterByShop } = usePermission();

const { t } = useI18n();

const tableId = ref('ledger-table')

const options = ref({
    url: LEDGERS,
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
            title: t('name'),
            type: 'component',
            key: 'name',
            componentName: h(LeadgerContactProfile),

        },

        {
            title: t('business_name'),
            type: 'text',
            key: 'business_name'
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
            title: t('debit'),
            type: 'object',
            key: 'debit',
            modifier: (debit => numberWithCurrencySymbol(debit)),
        },
        {
            title: t('credit'),
            type: 'object',
            key: 'credit',
            modifier: (credit => numberWithCurrencySymbol(credit)),
        },
        {
            title: t('balance'),
            type: 'object',
            key: 'balance',
            modifier: (balance => numberWithCurrencySymbol(balance)),
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("view_customer_ledger_reports")
        },
    ],
    filters: [
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
        {
            // Which side the contact's net balance falls on.
            label: t('balance'),
            type: 'search-select',
            key: 'balance_type',
            options: [
                { id: 'debit', name: t('debit') },
                { id: 'credit', name: t('credit') },
            ],
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: false,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('details'),
            type: 'details',
            icon: h(Edit),
            modifier: () => canAccess('view_customer_ledger_reports'),
        },

    ],
})


const actionTiger = (row, action) => {
    if (action.type === 'details') {
        router.push(`/ledger-details/${row.id}`)
    }
}

const handleClick = (e) => {
    const el = e.target.closest('.customer-details-link')

    if (el) {
        const id = el.dataset.id
        router.push(`/ledger-details/${id}`)
    }
}

onMounted(() => {
    document.addEventListener('click', handleClick)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClick)
})

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('ledgers')" />
        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

    </div>
</template>

<style scoped lang="scss"></style>

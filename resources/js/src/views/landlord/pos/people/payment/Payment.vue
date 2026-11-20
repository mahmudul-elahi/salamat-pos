<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { CreditCard } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { PAYMENTS, SELECTED_SHOPS } from "@/services/endpoints/landlord.js";
import CustomerPaymentModal from "@/views/landlord/pos/people/customers/CustomerPaymentModal.vue";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import SupplierPaymentModal from "@/views/landlord/pos/people/suppliers/SupplierPaymentModal.vue";
import AvatarProfile from "@/views/landlord/pos/people/customers/avater/AvatarProfile.vue";

const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();

const { t } = useI18n();

const tableId = ref('payments-table')

const options = ref({
    url: PAYMENTS,
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
            componentName: h(AvatarProfile),
        },
        {
            title: t('business_name'),
            type: 'text',
            key: 'business_name',
        },
        {
            title: t('business_logo'),
            type: 'custom-html',
            key: 'business_logo',
            modifier: (business_logo => business_logo
                ? `<img src="${business_logo}" style="width:40px;height:40px;border-radius:6px;object-fit:cover;border:1px solid #e5e7eb;background:#f9fafb;" />`
                : '<span style="color:#9ca3af;">—</span>'),
        },
        {
            title: t('phone_number'),
            type: 'text',
            key: 'phone',
        },
        {
            title: t('debit'),
            type: 'object',
            key: 'total_debit',
            modifier: (total_debit => numberWithCurrencySymbol(total_debit))
        },
        {
            title: t('credit'),
            type: 'object',
            key: 'total_credit',
            modifier: (total_credit => numberWithCurrencySymbol(total_credit))
        },
        {
            title: t('balance'),
            type: 'object',
            key: 'balance',
            modifier: (balance => numberWithCurrencySymbol(balance))
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: true
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
    ],
    actions: [

        {
            //create_sales_payment
            title: t('receive_payment'),
            type: 'receive_payment',
            icon: markRaw(CreditCard),
            modifier: (row => canAccess('receive_customer_payment')),
        },
        {
            title: t('send_payment'),
            type: 'send_payment',
            icon: markRaw(CreditCard),
            modifier: (row => canAccess('send_supplier_payment')),
        },

    ],
})


// Additional state for payment modal
const isPaymentModalActive = ref(false);
const selectedCustomerForPayment = ref(null);

const closePaymentModal = () => {
    isPaymentModalActive.value = false;
    selectedCustomerForPayment.value = null;
}

const isSupplierPaymentModalActive = ref(false);
const selectedSupplierForPayment = ref(null);

const closeSupplierPaymentModal = () => {
    isSupplierPaymentModalActive.value = false;
    selectedSupplierForPayment.value = null;
}

const actionTiger = (row, action) => {
    if (action.type === 'receive_payment') {
        selectedCustomerForPayment.value = row; // Pass the whole row object to get name and ID
        isPaymentModalActive.value = true;
    } else if (action.type === 'send_payment') {
        selectedSupplierForPayment.value = row;
        isSupplierPaymentModalActive.value = true;
    }
}


</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('payments')" />

        <app-datatable :id="tableId" :options="options" @action="actionTiger" />


        <customer-payment-modal v-if="isPaymentModalActive" modal-id="customer-payment-modal" :table-id="tableId"
            :row-data="selectedCustomerForPayment" @close="closePaymentModal" />

        <supplier-payment-modal v-if="isSupplierPaymentModalActive" modal-id="supplier-payment-modal"
            :table-id="tableId" :row-data="selectedSupplierForPayment" @close="closeSupplierPaymentModal" />


    </div>
</template>

<style scoped lang="scss"></style>

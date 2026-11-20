<script setup>
import {h, ref} from "vue"
import {useI18n} from 'vue-i18n';
import SupplierPurchase from "./component/SupplierPurchase.vue";
import SupplierReturn from "./component/SupplierReturn.vue";
import SupplierPurchasePayment from "./component/SupplierPurchasePayment.vue";
import SupplierPurchaseReturnPayment from "./component/SupplierPurchaseReturnPayment.vue";
import SupplierLedgerReport from "@/views/landlord/pos/reports/component/SupplierLedgerReport.vue";
import usePermission from "@/composable/permission/usePermission.js";

const props = defineProps({
    id: {},
})

const {canAccess} = usePermission();

const {t} = useI18n();
const tabs = ref([
    {
        title: t('purchase'),
        key: 'purchase',
        component: h('div', {id: 'purchase'}, [h(SupplierPurchase, {supplierId: props.id})]),
        permission: canAccess('view_supplier_purchase_reports'),
    },
    {
        title: t('return'),
        key: 'return',
        component: h('div', {id: 'return'}, [h(SupplierReturn, {supplierId: props.id})]),
        permission: canAccess('view_supplier_purchase_return_reports'),
    },
    {
        title: t('purchase_payment'),
        key: 'purchase_payment',
        component: h('div', {id: 'purchase-payment'}, [h(SupplierPurchasePayment, {supplierId: props.id})]),
        permission: canAccess('view_supplier_purchase_transaction_reports'),
    },
    {
        title: t('purchase_return_payment'),
        key: 'purchase_return_payment',
        component: h('div', {id: 'purchase-return-payment'}, [h(SupplierPurchaseReturnPayment, {supplierId: props.id})]),
        permission: canAccess('view_supplier_purchase_return_transaction_reports'),
    },
    // {
    //     title: t('ledgers'),
    //     key: 'ledgers',
    //     component: h('div', {id: 'ledgers'}, [h(SupplierLedgerReport, {supplierId: props.id})]),
    //     permission: canAccess('view_supplier_ledger_reports'),
    // },
]);


</script>
<template>
    <div class="content-wrapper">
        <app-tab id="supplier=details-tab" :tabs="tabs" aria-label="horizontal" type="horizontal"/>
    </div>

</template>
<style scoped lang="scss"></style>

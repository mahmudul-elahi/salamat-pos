<script setup>
import { h, ref } from "vue"
import { useI18n } from 'vue-i18n';
import usePermission from "@/composable/permission/usePermission.js";
import CustomerLedgerReport from "@/views/landlord/pos/reports/customer/CustomerLedgerReport.vue";

const props = defineProps({
    id: {},
})

const { canAccess } = usePermission();

const { t } = useI18n();
const tabs = ref([
    {
        title: t('ledgers'),
        key: 'ledgers',
        component: h('div', { id: 'ledger-details-report' }, [h(CustomerLedgerReport, {
            contactId: props.id,
        })]),
        permission: canAccess('view_customer_ledger_reports'),
    },
]);


</script>
<template>
    <div class="content-wrapper">
        <app-tab id="customer-details-tab" :tabs="tabs" aria-label="horizontal" type="horizontal" />
    </div>

</template>
<style scoped lang="scss"></style>

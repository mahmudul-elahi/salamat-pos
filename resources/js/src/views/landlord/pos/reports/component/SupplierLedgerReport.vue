<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {Eye, Trash} from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import usePermission from "@/composable/permission/usePermission.js";
import {
    SELECTED_SHOPS,
    SUPPLIER_LEDGER_REPORTS,
    SUPPLIER_LEDGER_REPORTS_PDF,
    SUPPLIER_PURCHASE_RETURN_REPORTS
} from "@/services/endpoints/landlord.js";
import {formatDateToLocal, numberWithCurrencySymbol} from "@/utils/helper";
const toast = useToast();
const {canAccess} = usePermission();
const {isSupperAdmin, canFilterByShop } = usePermission();

const {t} = useI18n();
const props = defineProps({
    supplierId: {
        type: [String, Number],
        required: true
    }
})
const tableId = ref('supplier-reports-table')

const options = ref({
    url: `${SUPPLIER_LEDGER_REPORTS}/${props.supplierId}`,
    pageSize: 5,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: isSupperAdmin(),
    boxShadow: false,
    hideTotal: true,
    columns: [
        {
            title: t('date'),
            type: 'object',
            key: 'date',
            modifier: (date => formatDateToLocal(date))
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'full_invoice_number',
        },
        {
            title: t('type'),
            type: 'text',
            key: 'type',
        },
        {
            title: t('debit'),
            type: 'object',
            key: 'debit',
            accountAble: true,
            modifier: (debit => numberWithCurrencySymbol(debit))
        },
        {
            title: t('credit'),
            type: 'object',
            key: 'credit',
            accountAble: true,
            modifier: (credit => numberWithCurrencySymbol(credit))
        },
        {
            title: t('balance'),
            type: 'object',
            key: 'balance',
            accountAble: true,
            modifier: (balance => numberWithCurrencySymbol(balance))
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
    actions: [],
})


const actionTiger = (row, action) => {

}
const preloader = ref(false);
const invoiceURL = ref(null);
const previewSupplierLedger = () => {
    preloader.value = true;

    Axios.get(
        `${SUPPLIER_LEDGER_REPORTS_PDF}/${props.supplierId}?preview=preview`,
        { responseType: 'arraybuffer' }
    )
        .then(response => {
            const blob = new Blob([response.data], { type: 'application/pdf' });
            invoiceURL.value = URL.createObjectURL(blob);
        })
        .catch(() => {
            toast.error(t('supplier_ledger_preview_failed'));
        })
        .finally(() => preloader.value = false);
};


</script>

<template>
    <app-breadcrumb :page-title="$t('supplier_ledger_report')">
            <template #breadcrumb-actions>
                <button class="btn btn-primary text-white" v-if="canAccess('generate_supplier_ledger_report_pdf')" @click="previewSupplierLedger">
                    {{ $t('generate_print') }}
                </button>
            </template>
        </app-breadcrumb>

    <app-datatable :id="tableId" :options="options" @action="actionTiger"/>
    <div v-if="invoiceURL" style="margin-top: 20px;">
    <iframe
      :src="invoiceURL"
      width="100%"
      height="600px"
      frameborder="0"
    ></iframe>
  </div>
</template>

<style scoped lang="scss"></style>

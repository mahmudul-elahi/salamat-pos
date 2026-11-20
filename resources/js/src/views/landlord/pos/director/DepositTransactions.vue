<script setup>
import { h, ref } from "vue";
import NoteColumn from "@/views/landlord/common/component/NoteColumn.vue";
import { formatDateToLocal, numberWithCurrencySymbol } from "@/utils/helper.js";
import { useI18n } from "vue-i18n";
import { DIRECTORS } from "@/services/endpoints/landlord.js";

const props = defineProps({ directorId: {} });

const { t } = useI18n();


const options = ref({
    url: `${DIRECTORS}/${props.directorId}/deposits`,
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
            title: t('shop_name'),
            type: 'object',
            key: 'tenant',
            modifier: (tenant => tenant?.name)
        },
        {
            title: t('title'),
            type: 'text',
            key: 'title',
        },
        {
            title: t('date'),
            type: 'text',
            key: 'date',
            modifier: (date => formatDateToLocal(date))
        },


        {
            title: t('note'),
            type: 'component',
            key: 'note',
            componentName: h(NoteColumn)
        },
        {
            title: t('amount'),
            type: 'object',
            key: 'amount',
            accountAble: true,
            modifier: (amount => numberWithCurrencySymbol(amount))
        },

    ],
    filters: [

    ],
    actions: [

    ],


})

const actionTiger = (action, data) => {

}
</script>

<template>
    <div class="section-card mb-4">
        <div class="section-title mb-3">Deposit</div>
        <app-datatable id="deposit_transactions" :options="options" @action="actionTiger" />
    </div>
</template>
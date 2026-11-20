<script setup>
import { ref, h } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import { PRODUCT_BARCODE_GENERATES, PRODUCT_SERIAL_HISTORY_DELETE } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import { Trash } from "@iconoir/vue";
import BarcodeProductProfile from "@/views/landlord/pos/HelperComponent/BarcodeProductProfile.vue";

const { canAccess } = usePermission();
const { t } = useI18n();

const tableId = ref('barcode-table');


const options = ref({
    url: PRODUCT_BARCODE_GENERATES,
    pageSize: 10,
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
            title: t('product_name'),
            type: 'component',
            key: 'product_name',
            componentName: h(BarcodeProductProfile),
        },
        {
            title: t('serial_number'),
            type: 'text',
            key: 'serial_number'
        },
        {
            title: t('mrp'),
            type: 'object',
            key: 'price',
            modifier: (price => price ? numberWithCurrencySymbol(price) : '-')
        },
        {
            title: t('warehouse'),
            type: 'object',
            key: 'tenant',
            modifier: (tenant => tenant ? tenant.name : '-')
        },
        {
            title: t('generated_by'),
            type: 'object',
            key: 'generated_by',
            modifier: (generatedBy => generatedBy ? generatedBy.name : '-')
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('delete_product_barcodes')
        }

    ],
    filters: [],
    actions: [
        {
            title: t('delete'),
            type: 'delete',
            icon: h(Trash),
            modifier: (row => canAccess('delete_product_barcodes')),
        }

    ]
});

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const actionTiger = (row, action) => {
    if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${PRODUCT_SERIAL_HISTORY_DELETE}/${row.id}`
    }
};

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Generate Barcodes" />
        <!-- Datatable -->
        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />

    </div>
</template>

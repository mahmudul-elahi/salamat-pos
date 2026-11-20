<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import PaymentMethodModal from "@/views/landlord/setting/paymentMethod/PaymentMethodModal.vue";
import {useOpenModal} from "@/composable/modal/useOpenModal.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import {LANDLORD_PAYMENT_METHODS} from "@/services/endpoints/landlord.js";
import { Edit, Trash } from "@iconoir/vue";
const {canAccess} = usePermission();

const {isModalActive, selectedData, closeModal} = useOpenModal()
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal();


const tableId = ref('payment-method-table')
const {t} = useI18n();
const options = ref({
    url: LANDLORD_PAYMENT_METHODS,
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
    boxShadow: false,
    columns: [
        {
            title: 'Logo',
            type: 'custom-html',
            key: 'logo',
            modifier: (logo) => logo
                ? `<img src="${logo}" style="width:36px;height:36px;object-fit:contain;border-radius:6px;border:1px solid #e5e7eb;background:#f9fafb;padding:2px;" />`
                : `<span style="color:#9ca3af;font-size:12px;">—</span>`
        },
        {
            title: t('name'),
            type: 'text',
            key: 'name'
        },
        {
            title: 'Director',
            type: 'object',
            key: 'director',
            modifier: (director => director?.name)
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_payment_methods") || canAccess("delete_payment_methods")
        },
    ],
    actions: [
        {
            title: t('edit'),
            icon: markRaw(Edit),
            type: 'edit',
            modifier: () => canAccess("update_payment_methods")
        },
        {
            title: t('delete'),
            icon: markRaw(Trash),
            type: 'delete',
            modifier: () => canAccess("delete_payment_methods")
        }
    ],
})

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${LANDLORD_PAYMENT_METHODS}/${row.id}`
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${LANDLORD_PAYMENT_METHODS}/${row.id}`
    }
}

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('payment_method')" />
        <app-datatable :id="tableId" :options="options"
            :add-button-text="$t('add_payment_method')"
            :button-visible="canAccess('create_payment_methods')"
            @buttonAction="isModalActive = true"
            @action="actionTiger"/>

    <payment-method-modal v-if="isModalActive"
                          modal-id="payment-method-modal"
                          :table-id="tableId"
                          :selected-url="selectedData"
                          @close="closeModal"/>

        <app-delete-modal v-if="isDeleteModal"
                      :selected-url="deleteUrl"
                      :table-id="tableId"
                      @cancelled="cancelled"/>
    </div>
</template>

<style scoped lang="scss">

</style>



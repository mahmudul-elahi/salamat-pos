<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {Edit, Trash} from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import {useOpenModal} from "@/composable/modal/useOpenModal.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import {FAQS} from "@/services/endpoints/Ecommerce.js";
import FaqModal from "@/views/landlord/Ecommerce/faq/FaqModal.vue";

const {t} = useI18n();
const {canAccess} = usePermission();
const tableId = ref('faq-table');

const options = ref({
    url: FAQS,
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
            title: t('tenant'),
            type: 'object',
            key: 'tenant',
            modifier: (tenant) => tenant?.name ?? '-',
        },
        {
            title: t('question'),
            type: 'custom-html',
            key: 'question',
            modifier: (row) => row
                ? `<span class="d-inline-block text-truncate" style="max-width: 260px;">${row}</span>`
                : '-',
        },
        {
            title: 'Lang',
            type: 'custom-html',
            key: 'lang',
            modifier: (lang) => lang === 'bn'
                ? `<span class="badge" style="background:#f3f0ff;color:#5b50f0;font-weight:600;">BN</span>`
                : `<span class="badge" style="background:#ecfdf5;color:#059669;font-weight:600;">EN</span>`,
        },
        {
            title: t('order'),
            type: 'text',
            key: 'order',
        },
        {
            title: t('views'),
            type: 'text',
            key: 'views',
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (status) => status
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Inactive</span>',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_faqs') || canAccess('delete_faqs'),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_faqs'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_faqs'),
        },
    ],
});

const {isModalActive, selectedData, closeModal} = useOpenModal();
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${FAQS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${FAQS}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('faqs')"/>

        <app-datatable
            :id="tableId"
            :options="options"
            :add-button-text="$t('add_faq')"
            :button-visible="canAccess('create_faqs')"
            @buttonAction="isModalActive = true"
            @action="actionTiger"/>

        <faq-modal
            v-if="isModalActive"
            :table-id="tableId"
            modal-id="faq-modal"
            :selected-url="selectedData"
            @close="closeModal"/>

        <app-delete-modal
            v-if="isDeleteModal"
            :table-id="tableId"
            :selected-url="deleteUrl"
            @cancelled="cancelled"/>
    </div>
</template>

<style scoped lang="scss"></style>


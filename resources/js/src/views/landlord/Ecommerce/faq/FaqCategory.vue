<script setup>
import {ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {Edit, Trash} from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import {useOpenModal} from "@/composable/modal/useOpenModal.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import FaqCategoryModal from "@/views/landlord/Ecommerce/faq/FaqCategoryModal.vue";
import {FAQ_CATEGORIES} from "@/services/endpoints/Ecommerce.js";

const {t} = useI18n();
const {canAccess} = usePermission();
const tableId = ref('faq-category-table');

const options = ref({
    url: FAQ_CATEGORIES,
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
            title: t('name'),
            type: 'text',
            key: 'name',
        },
        {
            title: t('slug'),
            type: 'text',
            key: 'slug',
        },
        {
            title: t('order'),
            type: 'text',
            key: 'order',
        },
        {
            title: t('faqs'),
            type: 'text',
            key: 'faqs_count',
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
            permission: canAccess('update_faq_categories') || canAccess('delete_faq_categories'),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_faq_categories'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_faq_categories'),
        },
    ],
});

const {isModalActive, selectedData, closeModal} = useOpenModal();
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${FAQ_CATEGORIES}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${FAQ_CATEGORIES}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('faq_categories')"/>

        <app-datatable
            :id="tableId"
            :options="options"
            :add-button-text="$t('add_faq_category')"
            :button-visible="canAccess('create_faq_categories')"
            @buttonAction="isModalActive = true"
            @action="actionTiger"/>

        <faq-category-modal
            v-if="isModalActive"
            :table-id="tableId"
            modal-id="faq-category-modal"
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


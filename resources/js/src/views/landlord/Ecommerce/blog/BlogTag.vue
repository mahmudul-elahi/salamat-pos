<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { Edit, Trash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import BlogTagModal from "@/views/landlord/Ecommerce/blog/BlogTagModal.vue";
import { BLOG_TAGS } from "@/services/endpoints/Ecommerce.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('blog-tag-table');

const options = ref({
    url: BLOG_TAGS,
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
            title: t('posts'),
            type: 'text',
            key: 'posts_count',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_blog_tags') || canAccess('delete_blog_tags'),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_blog_tags'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_blog_tags'),
        },
    ],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        isModalActive.value = true;
        selectedData.value = `${BLOG_TAGS}/${row.id}`;
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${BLOG_TAGS}/${row.id}`;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('blog_tags')" />

        <app-datatable
            :id="tableId"
            :options="options"
            :add-button-text="$t('add_blog_tag')"
            :button-visible="canAccess('create_blog_tags')"
            @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <blog-tag-modal
            v-if="isModalActive"
            :table-id="tableId"
            modal-id="blog-tag-modal"
            :selected-url="selectedData"
            @close="closeModal" />

        <app-delete-modal
            v-if="isDeleteModal"
            :table-id="tableId"
            :selected-url="deleteUrl"
            @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss"></style>

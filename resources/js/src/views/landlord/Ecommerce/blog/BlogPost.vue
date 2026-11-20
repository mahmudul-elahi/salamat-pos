<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { Edit, Trash, Send, Star, GraphUp, ThumbsUp, MinusCircle, Pin, PinSlash } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import Axios from "@/services/axios/index.js";
import { formatDateToLocal } from "@/utils/helper.js";
import { BLOG_POSTS, BLOG_POST_TOGGLE } from "@/services/endpoints/Ecommerce.js";

const { t } = useI18n();
const { canAccess } = usePermission();
const router = useRouter();
const toast = useToast();
const emitter = useEmitter();
const tableId = ref('blog-post-table');

const flagBadge = (label, active) => active
    ? `<span class="badge bg-success me-1">${label}</span>`
    : '';

const options = ref({
    url: BLOG_POSTS,
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
            title: t('title'),
            type: 'text',
            key: 'title',
        },
        {
            title: t('category'),
            type: 'custom-html',
            key: 'category',
            modifier: (value, row) => row.category?.name ?? '-',
        },
        {
            title: t('author'),
            type: 'custom-html',
            key: 'author',
            modifier: (value, row) => row.author?.name ?? '-',
        },
        {
            title: t('views'),
            type: 'text',
            key: 'views',
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'is_published',
            modifier: (isPublished) => isPublished
                ? '<span class="badge bg-success">Published</span>'
                : '<span class="badge bg-secondary">Draft</span>',
        },
        {
            title: t('published_at'),
            type: 'object',
            key: 'published_at',
            modifier: (publishedAt) => publishedAt ? formatDateToLocal(publishedAt, true) : '-',
        },
        {
            title: t('flags'),
            type: 'custom-html',
            key: 'is_featured',
            modifier: (value, row) => [
                flagBadge(t('featured'), row.is_featured),
                flagBadge(t('trending'), row.is_trending),
                flagBadge(t('popular'), row.is_popular),
                flagBadge(t('pinned'), row.is_pinned),
            ].join('') || '-',
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_blog_posts') || canAccess('delete_blog_posts') || canAccess('toggle_blog_posts'),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_blog_posts'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_blog_posts'),
        },
        {
            title: t('publish'),
            type: 'toggle_published',
            icon: markRaw(Send),
            modifier: (row) => !row.is_published && canAccess('toggle_blog_posts'),
        },
        {
            title: t('unpublish'),
            type: 'toggle_published',
            icon: markRaw(MinusCircle),
            modifier: (row) => !!row.is_published && canAccess('toggle_blog_posts'),
        },
        {
            title: t('mark_as_featured'),
            type: 'toggle_featured',
            icon: markRaw(Star),
            modifier: (row) => !row.is_featured && canAccess('toggle_blog_posts'),
        },
        {
            title: t('remove_from_featured'),
            type: 'toggle_featured',
            icon: markRaw(Star),
            modifier: (row) => !!row.is_featured && canAccess('toggle_blog_posts'),
        },
        {
            title: t('mark_as_trending'),
            type: 'toggle_trending',
            icon: markRaw(GraphUp),
            modifier: (row) => !row.is_trending && canAccess('toggle_blog_posts'),
        },
        {
            title: t('remove_from_trending'),
            type: 'toggle_trending',
            icon: markRaw(GraphUp),
            modifier: (row) => !!row.is_trending && canAccess('toggle_blog_posts'),
        },
        {
            title: t('mark_as_popular'),
            type: 'toggle_popular',
            icon: markRaw(ThumbsUp),
            modifier: (row) => !row.is_popular && canAccess('toggle_blog_posts'),
        },
        {
            title: t('remove_from_popular'),
            type: 'toggle_popular',
            icon: markRaw(ThumbsUp),
            modifier: (row) => !!row.is_popular && canAccess('toggle_blog_posts'),
        },
        {
            // Pinned posts lead both the Recent Posts block and the first page
            // of All Posts on the storefront.
            title: t('pin_post'),
            type: 'toggle_pinned',
            icon: markRaw(Pin),
            modifier: (row) => !row.is_pinned && canAccess('toggle_blog_posts'),
        },
        {
            title: t('unpin_post'),
            type: 'toggle_pinned',
            icon: markRaw(PinSlash),
            modifier: (row) => !!row.is_pinned && canAccess('toggle_blog_posts'),
        },
    ],
});

const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const goToCreate = () => {
    router.push({ name: 'createBlogPost' });
};

const goToPinnedOrder = () => {
    router.push({ name: 'pinnedPostOrder' });
};

const TOGGLE_FIELDS = {
    toggle_published: 'is_published',
    toggle_featured: 'is_featured',
    toggle_trending: 'is_trending',
    toggle_popular: 'is_popular',
    toggle_pinned: 'is_pinned',
};

const toggleFlag = (row, field) => {
    Axios.patch(BLOG_POST_TOGGLE(row.id, field)).then(({ data }) => {
        toast.success(data.message);
        emitter.emit('reload-' + tableId.value);
    }).catch(({ response }) => {
        if (response?.data?.message) toast.error(response.data.message);
    });
};

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        router.push({ name: 'editBlogPost', params: { id: row.id } });
    } else if (action.type === 'delete') {
        isDeleteModal.value = true;
        deleteUrl.value = `${BLOG_POSTS}/${row.id}`;
    } else if (TOGGLE_FIELDS[action.type]) {
        toggleFlag(row, TOGGLE_FIELDS[action.type]);
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('blog_posts')" />

        <app-datatable
            :id="tableId"
            :options="options"
            :add-button-text="$t('add_blog_post')"
            :button-visible="canAccess('create_blog_posts')"
            @buttonAction="goToCreate"
            @action="actionTiger">
            <template #extra-actions>
                <button v-if="canAccess('update_blog_posts')" type="button" class="btn btn-primary-outline"
                    @click="goToPinnedOrder">
                    {{ $t('reorder_pinned_posts') }}
                </button>
            </template>
        </app-datatable>

        <app-delete-modal
            v-if="isDeleteModal"
            :table-id="tableId"
            :selected-url="deleteUrl"
            @cancelled="cancelled" />
    </div>
</template>

<style scoped lang="scss"></style>

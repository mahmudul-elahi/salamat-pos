<script setup>
import {ref, h, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import {REVIEWS} from "@/services/endpoints/Ecommerce.js";
import {SELECTED_PRODUCT} from "@/services/endpoints/landlord.js";
import usePermission from "@/composable/permission/usePermission.js";
import {CheckCircle, XmarkCircle, Trash} from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import {useOpenModal} from "@/composable/modal/useOpenModal.js";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import ReviewModal from "./ReviewModal.vue";
import ProductColumn from "./ProductColumn.vue";
import CommentColumn from "./CommentColumn.vue";

const {t} = useI18n();
const {canAccess} = usePermission();
const tableId = ref('review-table');
const toast = useToast();
const emitter = useEmitter();
const options = ref({
    url: REVIEWS,
    pageSize: 5,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: true,
    columns: [
        {
            title: t('product'),
            type: 'component',
            key: 'product',
            componentName: h(ProductColumn),
        },
        {
            title: t('comment'),
            type: 'component',
            key: 'comment',
            componentName: h(CommentColumn),
        },
        {
            title: t('customer'),
            type: 'custom-html',
            key: 'contact',
            modifier: (contact, row) => {
                if (row.is_admin_review) {
                    return row.customer_name ?? '-';
                }
                return contact ? contact.name : '-';
            }
        },
        {
            title: 'Type',
            type: 'custom-html',
            key: 'is_admin_review',
            modifier: (isAdminReview) => {
                if (isAdminReview) {
                    return `<span class="badge-square-light bg-info">Admin</span>`;
                }
                return `<span class="badge-square-light bg-warning">Customer</span>`;
            }
        },
        {
            title: t('rating'),
            type: 'custom-html',
            key: 'rating',
            modifier: (rating) => {
                if (!rating) return '-';
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += `<span style="color:${i <= rating ? '#f5b301' : '#d9d9d9'};font-size:16px;">&#9733;</span>`;
                }
                return `<span class="text-nowrap">${stars}</span>`;
            },
        },

        {
            title: t('status'),
            type: 'custom-html',
            key: 'status',
            modifier: (value) => {
                if (value == 1 || value === "1") {
                    return `<span class="badge-square-light bg-success text-capitalize">Approved</span>`
                } else if (value == 0 || value === "0") {
                    return `<span class="badge-square-light bg-danger text-capitalize">Rejected</span>`
                }
            }
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess("update_reviews") || canAccess("delete_reviews"),
        },
    ],
    filters: [
        {
            label: t('product'),
            type: 'advance-search-select',
            key: 'product',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_PRODUCT}`,
            permission: true,
        },
    ],
    actions: [
        {
            title: t('approved'),
            type: 'approved',
            icon: markRaw(CheckCircle),
            modifier: (row => row.status == 0 && canAccess('update_reviews')),
        },
        {
            title: t('rejected'),
            type: 'rejected',
            icon: markRaw(XmarkCircle),
            modifier: (row => row.status == 1 && canAccess('update_reviews')),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: (() => canAccess('delete_reviews')),
        },
    ],
});

const {isModalActive, closeModal} = useOpenModal();
const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal();

const manageStatus = (row, status) => {
    Axios.patch(`${REVIEWS}/${row.id}`, {
        status: status
    }).then(({data}) => {
        toast.success(data.message);
        emitter.emit('reload-' + tableId.value);
    }).catch(({response}) => {
        if (response?.data?.message) toast.error(response.data.message);
    });
}


const actionTiger = (row, action) => {
    if (action.type === 'approved') {
        manageStatus(row, 1);
    } else if (action.type === 'rejected') {
        manageStatus(row, 0);
    } else if (action.type === 'delete') {
        deleteUrl.value = `${REVIEWS}/${row.id}`;
        isDeleteModal.value = true;
    }
};
</script>


<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('reviews')"/>
        <app-datatable
            :id="tableId"
            :options="options"
            :button-visible="canAccess('create_reviews')"
            add-button-text="Add Review"
            @buttonAction="isModalActive = true"
            @action="actionTiger"/>

        <review-modal v-if="isModalActive" :table-id="tableId" modal-id="review-modal" @close="closeModal"/>

        <app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled"/>
    </div>
</template>

<style scoped lang="scss"></style>

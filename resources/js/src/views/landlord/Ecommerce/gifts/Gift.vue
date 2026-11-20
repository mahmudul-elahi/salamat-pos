<script setup>
import { ref, markRaw } from 'vue'
import { useI18n } from 'vue-i18n'
import { GIFTS, GIFT_STATUS } from '@/services/endpoints/Ecommerce.js'
import GiftModal from '@/views/landlord/Ecommerce/gifts/GiftModal.vue'
import ConfirmationModal from '@/views/global/modal/ConfirmationModal.vue'
import { useOpenModal } from '@/composable/modal/useOpenModal.js'
import { useDeleteModal } from '@/composable/modal/useDeleteModal.js'
import usePermission from '@/composable/permission/usePermission.js'
import { Edit, Trash, Eye, CheckCircle, MinusCircle, InfoCircle } from '@iconoir/vue'
import Axios from '@/services/axios/index.js'
import { useToast } from 'vue-toastification'
import useEmitter from '@/composable/emitter/useEmitter.js'

const { t } = useI18n()
const { canAccess } = usePermission()
const toast = useToast()
const emitter = useEmitter()

const tableId = ref('gift-table')
const statusChangeModal = ref(false)
const statusChangeData = ref(null)

const options = ref({
    url: GIFTS,
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
            title: 'Type',
            type: 'custom-html',
            key: 'pre_owned_id',
            modifier: (preOwnedId) => preOwnedId
                ? `<span class="badge bg-warning text-dark">Pre-Owned</span>`
                : `<span class="badge bg-info text-white">Regular</span>`,
        },
        {
            title: t('product'),
            type: 'custom-html',
            key: 'product',
            modifier: (product, row) => {
                if (row.pre_owned_id) {
                    const name = row.pre_owned?.product?.product_name ?? 'Pre-Owned'
                    const serial = row.pre_owned?.serial_number ? ` <span class="text-muted small">(${row.pre_owned.serial_number})</span>` : ''
                    return name + serial
                }
                return product?.product_name ?? '-'
            },
        },
        {
            title: t('gift_items'),
            type: 'custom-html',
            key: 'gift_variants',
            modifier: (variants) => {
                const count = variants?.length ?? 0
                return count
                    ? `<span class="badge bg-primary">${count} item${count > 1 ? 's' : ''}</span>`
                    : `<span class="badge bg-secondary">None</span>`
            },
        },
        {
            title: t('status'),
            type: 'custom-html',
            key: 'is_active',
            modifier: (value) => value == 1
                ? `<span class="badge-square-light bg-success text-capitalize">Active</span>`
                : `<span class="badge-square-light bg-danger text-capitalize">Inactive</span>`,
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_gift_products') || canAccess('delete_gift_products'),
        },
    ],
    actions: [
        {
            title: t('view'),
            type: 'view',
            icon: markRaw(Eye),
            modifier: () => true,
        },
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_gift_products'),
        },
        {
            title: t('activate'),
            type: 'active',
            icon: markRaw(CheckCircle),
            modifier: (row) => row.is_active == 0 && canAccess('update_gift_products'),
        },
        {
            title: t('inactive'),
            type: 'inactive',
            icon: markRaw(MinusCircle),
            modifier: (row) => row.is_active == 1 && canAccess('update_gift_products'),
        },
        {
            title: t('delete'),
            type: 'delete',
            icon: markRaw(Trash),
            modifier: () => canAccess('delete_gift_products'),
        },
    ],
})

const { isModalActive, selectedData, closeModal } = useOpenModal()
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal()

const confirmStatusChange = () => {
    const { row } = statusChangeData.value
    Axios.patch(GIFT_STATUS(row.id)).then(({ data }) => {
        toast.success(data.message)
        emitter.emit('reload-' + tableId.value)
    }).catch(({ response }) => {
        if (response?.data?.message) toast.error(response.data.message)
    }).finally(() => {
        statusChangeModal.value = false
    })
}

const detailModal = ref(false)
const detailData = ref(null)

const openDetail = async (row) => {
    detailData.value = null
    detailModal.value = true
    try {
        const { data } = await Axios.get(`${GIFTS}/${row.id}`)
        detailData.value = data
    } catch {
        toast.error('Failed to load gift details.')
        detailModal.value = false
    }
}

const closeDetail = () => {
    detailModal.value = false
    detailData.value = null
}

const actionTiger = (row, action) => {
    if (action.type === 'view') {
        openDetail(row)
    } else if (action.type === 'edit') {
        isModalActive.value = true
        selectedData.value = `${GIFTS}/${row.id}`
    } else if (action.type === 'active' || action.type === 'inactive') {
        statusChangeData.value = { row }
        statusChangeModal.value = true
    } else if (action.type === 'delete') {
        isDeleteModal.value = true
        deleteUrl.value = `${GIFTS}/${row.id}`
    }
}
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('gift_list')" />

        <app-datatable
            :id="tableId"
            :options="options"
            :add-button-text="$t('add_gift')"
            :button-visible="canAccess('create_gift_products')"
            @buttonAction="isModalActive = true"
            @action="actionTiger" />

        <gift-modal
            v-if="isModalActive"
            :table-id="tableId"
            modal-id="gift-modal"
            :selected-url="selectedData"
            @close="closeModal" />

        <app-delete-modal
            v-if="isDeleteModal"
            :table-id="tableId"
            :selected-url="deleteUrl"
            @cancelled="cancelled" />

        <confirmation-modal
            v-if="statusChangeModal"
            modal-id="giftStatusChange"
            modal-class="text-warning"
            :icon="InfoCircle"
            :title="$t('are_you_sure')"
            :message="statusChangeData?.row?.is_active == 1
                ? $t('do_you_want_to_inactive_this')
                : $t('do_you_want_to_active_this')"
            :preloader="false"
            :first-button-name="$t('yes')"
            :second-button-name="$t('cancel')"
            @cancel="statusChangeModal = false"
            @confirm="confirmStatusChange" />

        <!-- Detail Modal -->
        <div v-if="detailModal" class="modal fade show d-block" tabindex="-1"
            style="background:rgba(0,0,0,.45);" @click.self="closeDetail">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $t('gift_details') }}</h5>
                        <button type="button" class="btn-close" @click="closeDetail" />
                    </div>
                    <div class="modal-body">
                        <div v-if="!detailData" class="text-center py-4">
                            <span class="spinner-border text-primary" />
                        </div>
                        <template v-else>
                            <div class="mb-3 d-flex gap-4">
                                <div>
                                    <small class="text-muted d-block">
                                        {{ detailData.pre_owned_id ? 'Pre-Owned Product' : $t('product') }}
                                    </small>
                                    <template v-if="detailData.pre_owned_id">
                                        <strong>{{ detailData.pre_owned?.product?.product_name ?? '—' }}</strong>
                                        <span v-if="detailData.pre_owned?.serial_number" class="text-muted small ms-1">
                                            ({{ detailData.pre_owned.serial_number }})
                                        </span>
                                    </template>
                                    <strong v-else>{{ detailData.product?.product_name ?? '—' }}</strong>
                                </div>
                                <div>
                                    <small class="text-muted d-block">{{ $t('status') }}</small>
                                    <span :class="detailData.is_active ? 'badge bg-success' : 'badge bg-danger'">
                                        {{ detailData.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <h6 class="mb-2">{{ $t('gift_items') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>{{ $t('product') }}</th>
                                            <th>{{ $t('variant') }}</th>
                                            <th>{{ $t('qty') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!detailData.gift_variants?.length">
                                            <td colspan="4" class="text-center text-muted">
                                                No gift items configured.
                                            </td>
                                        </tr>
                                        <tr v-for="(item, idx) in detailData.gift_variants" :key="item.id">
                                            <td>{{ idx + 1 }}</td>
                                            <td>{{ item.product?.product_name }}</td>
                                            <td>
                                                <template v-if="item.product_variant?.attribute_values?.length">
                                                    <span
                                                        v-for="av in item.product_variant.attribute_values"
                                                        :key="av.id"
                                                        class="badge bg-secondary me-1">
                                                        {{ av.value }}
                                                    </span>
                                                </template>
                                                <span v-else class="text-muted">—</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">× {{ item.qty }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="closeDetail">{{ $t('close') }}</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped lang="scss"></style>

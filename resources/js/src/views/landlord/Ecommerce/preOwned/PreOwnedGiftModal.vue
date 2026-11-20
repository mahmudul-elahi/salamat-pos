<script setup>
import { ref, watch } from 'vue'
import { GIFTS } from '@/services/endpoints/Ecommerce.js'
import { SELECTED_PRODUCT_VARIANTS } from '@/services/endpoints/landlord.js'
import Axios from '@/services/axios/index.js'
import { useToast } from 'vue-toastification'
import useEmitter from '@/composable/emitter/useEmitter.js'
import { Modal } from 'bootstrap'

const props = defineProps({
    modalId: String,
    tableId: String,
    preOwnedId: [String, Number],
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const toast = useToast()
const emitter = useEmitter()

const preloader = ref(false)
const pageLoader = ref(false)

const formData = ref({ is_active: 1 })
const errors = ref({})

const selectedGiftProductIds = ref([])
const selectedProductOptions = ref([])
const giftRows = ref([])
const selectedRowKeys = ref([])
const preLoadedOptions = ref(null)
let blockWatcher = false

const rowKey = (row) => `${row.product_id}_${row.product_variant_id ?? 'none'}`

const loadProductOptions = async (ids = []) => {
    if (!ids.length) return
    const { data } = await Axios.get(SELECTED_PRODUCT_VARIANTS, {
        params: { selected: ids.join(','), per_page: Math.max(ids.length, 10) },
    })
    selectedProductOptions.value = data?.data ?? []
}

const syncGiftRows = () => {
    const existingMap = new Map(giftRows.value.map(r => [rowKey(r), r]))
    const newRows = []

    selectedProductOptions.value.forEach(product => {
        const variants = product.variants ?? []
        if (!variants.length) {
            const row = { product_id: product.id, product_name: product.name, product_variant_id: null, variant_label: '-', qty: 1 }
            newRows.push({ ...row, qty: existingMap.get(rowKey(row))?.qty ?? 1 })
        } else {
            variants.forEach(variant => {
                const row = { product_id: product.id, product_name: product.name, product_variant_id: variant.id, variant_label: variant.attribute_values || '-', qty: 1 }
                newRows.push({ ...row, qty: existingMap.get(rowKey(row))?.qty ?? 1 })
            })
        }
    })

    const newKeys = new Set(newRows.map(rowKey))
    selectedRowKeys.value = selectedRowKeys.value.filter(k => newKeys.has(k))
    giftRows.value = newRows
}

watch(selectedGiftProductIds, async (ids) => {
    if (blockWatcher) return
    await loadProductOptions(ids)
    syncGiftRows()
}, { deep: true })

const isRowSelected = (row) => selectedRowKeys.value.includes(rowKey(row))

const toggleRow = (row) => {
    const key = rowKey(row)
    const idx = selectedRowKeys.value.indexOf(key)
    if (idx === -1) selectedRowKeys.value.push(key)
    else selectedRowKeys.value.splice(idx, 1)
}

const loadEditData = async () => {
    pageLoader.value = true
    blockWatcher = true
    try {
        const { data } = await Axios.get(props.selectedUrl)
        formData.value.is_active = data.is_active ? 1 : 0

        const giftVariants = data.gift_variants ?? []

        giftRows.value = giftVariants.map(gv => ({
            product_id: gv.product_id,
            product_name: gv.product?.product_name ?? '',
            product_variant_id: gv.product_variant_id ?? null,
            variant_label: gv.product_variant?.attribute_values?.map(av => av.value)?.join('/') || '-',
            qty: gv.qty,
        }))

        selectedRowKeys.value = giftVariants.map(gv => `${gv.product_id}_${gv.product_variant_id ?? 'none'}`)

        const productIds = [...new Set(giftVariants.map(v => v.product_id))]

        preLoadedOptions.value = productIds.map(id => {
            const gv = giftVariants.find(g => g.product_id === id)
            return { id, name: gv?.product?.product_name ?? '' }
        })

        selectedGiftProductIds.value = productIds
    } catch {
        toast.error('Failed to load gift data.')
    } finally {
        blockWatcher = false
        pageLoader.value = false
    }
}

if (props.selectedUrl) {
    loadEditData()
}

const submit = async () => {
    errors.value = {}

    const selectedRows = giftRows.value.filter(r => isRowSelected(r))
    if (!selectedRows.length) {
        toast.error('Please select at least one gift item.')
        return
    }

    const payload = {
        pre_owned_id: props.preOwnedId,
        is_active: Number(formData.value.is_active),
        variants: selectedRows.map(r => ({
            product_id: r.product_id,
            product_variant_id: r.product_variant_id,
            qty: Number(r.qty) || 1,
        })),
    }

    preloader.value = true
    try {
        if (props.selectedUrl) {
            await Axios.patch(props.selectedUrl, payload)
        } else {
            await Axios.post(GIFTS, payload)
        }
        toast.success('Gift saved successfully.')
        emitter.emit('reload-' + props.tableId)
        closeModal()
    } catch ({ response }) {
        if (response?.status === 422) {
            errors.value = response.data.errors ?? {}
            toast.error('Please fix the errors below.')
        } else {
            toast.error(response?.data?.message ?? 'Something went wrong.')
        }
    } finally {
        preloader.value = false
    }
}

const closeModal = () => {
    const modal = Modal.getInstance(document.getElementById(props.modalId))
    modal?.hide()
    emit('close')
}
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large"
        :title="selectedUrl ? 'Update Pre-Owned Gift' : 'Add Pre-Owned Gift'"
        :preloader="preloader"
        @submit="submit"
        @close="closeModal">

        <template v-slot:body>
            <app-loader v-if="pageLoader" />
            <template v-else>

                <!-- Status -->
                <div class="mb-4">
                    <app-input
                        id="is_active"
                        type="select"
                        label="Status"
                        :options="[{ id: 1, name: 'Active' }, { id: 0, name: 'Inactive' }]"
                        list-value-field="name"
                        v-model="formData.is_active"
                        :selected-disable="false" />
                </div>

                <!-- Gift products selector -->
                <div class="mb-4">
                    <app-input
                        type="advance-multi-select"
                        id="gift_products"
                        list-value-field="name"
                        list-key-field="id"
                        label="Gift Items"
                        v-model="selectedGiftProductIds"
                        :loading-length="10"
                        :fetch-url="SELECTED_PRODUCT_VARIANTS"
                        choose-label="Select gift products"
                        :pre-loaded-options="preLoadedOptions" />
                </div>

                <!-- Variants table -->
                <template v-if="selectedGiftProductIds.length">
                    <div v-if="giftRows.length" class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:42px" class="text-center">#</th>
                                    <th>Product</th>
                                    <th>Variant</th>
                                    <th style="width:110px">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in giftRows" :key="rowKey(row)"
                                    :class="{ 'table-primary': isRowSelected(row) }">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input"
                                            :checked="isRowSelected(row)" @change="toggleRow(row)" />
                                    </td>
                                    <td>{{ row.product_name }}</td>
                                    <td>
                                        <span v-if="row.variant_label && row.variant_label !== '-'"
                                            class="badge bg-secondary text-white">{{ row.variant_label }}</span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm"
                                            v-model.number="row.qty" min="1" :disabled="!isRowSelected(row)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="text-muted small mt-2">{{ selectedRowKeys.length }} item(s) selected as gift</p>
                    </div>
                    <div v-else class="text-center py-3 text-muted">
                        <span class="spinner-border spinner-border-sm me-2" />Loading variants...
                    </div>
                </template>
                <div v-else class="alert alert-light text-muted text-center mt-2">
                    Select gift products above to configure variants and quantities.
                </div>

            </template>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
